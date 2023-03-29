<?php

declare(strict_types=1);

namespace Dynamite\Tests\Unit\AsyncAws;

use AsyncAws\Core\Response;
use AsyncAws\Core\Test\Http\SimpleMockedResponse;
use AsyncAws\DynamoDb\DynamoDbClient;
use AsyncAws\DynamoDb\Input\BatchWriteItemInput;
use AsyncAws\DynamoDb\Input\PutItemInput;
use AsyncAws\DynamoDb\Result\BatchWriteItemOutput;
use AsyncAws\DynamoDb\Result\PutItemOutput;
use AsyncAws\DynamoDb\ValueObject\AttributeValue;
use AsyncAws\DynamoDb\ValueObject\PutRequest;
use AsyncAws\DynamoDb\ValueObject\WriteRequest;
use Dynamite\AbstractFixture;
use Dynamite\Client\AsyncAwsClient;
use Dynamite\Exception\ValidationException;
use Dynamite\FixtureInterface;
use Dynamite\Schema\Record;
use Dynamite\Schema\Value;
use Dynamite\Tests\Unit\UnitTestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\MockHttpClient;

class FixtureTest extends UnitTestCase
{
    public function testLoadWithoutTableNameSet(): void
    {
        $validator = $this->createValidator();
        $dynamoDbClientMock = $this->createMock(DynamoDbClient::class);
        $logger = $this->createTestLogger();

        $client = new AsyncAwsClient(
            $dynamoDbClientMock,
            $this->createSerializer(),
            $logger
        );

        $fixture = new class() extends AbstractFixture implements FixtureInterface {
            public function configure(): void
            {
                $this->addRecord(
                    new Record([
                        Value::stringValue('Id', '5957ddc9-6039-4e76-85e7-3d759a9d819c'),
                        Value::numericValue('Balance', 11.35),
                        Value::boolValue('Active', true),
                        Value::binaryValue('Avatar', 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8/5+hHgAHggJ/PchI7wAAAABJRU5ErkJggg=='),
                    ])
                );
            }
        };

        try {
            $fixture->setValidator($validator);
            $fixture->load($client, $logger);

            self::fail('Exception is not thrown');
        } catch (\Throwable $e) {
            $expectedErrors = [
                'tableName' => [
                    'Table name is not defined',
                ],
            ];

            self::assertInstanceOf(ValidationException::class, $e);
            self::assertSame('Validation failed', $e->getMessage());
            self::assertSame($expectedErrors, $e->getErrors());
        }

        $logger->cleanLogs();
    }

    public function testLoadSingleRecord(): void
    {
        $validator = $this->createValidator();
        $dynamoDbClientMock = $this->getMockBuilder(DynamoDbClient::class)
            ->onlyMethods(['putItem'])
            ->getMock()
        ;
        $dynamoDbClientMock
            ->expects(self::once())
            ->method('putItem')
            ->with(
                new PutItemInput([
                    'TableName' => 'Users',
                    'Item' => [
                        'Id' => [
                            'S' => '5957ddc9-6039-4e76-85e7-3d759a9d819c',
                        ],
                    ],
                ])
            )
            ->willReturn(new PutItemOutput($this->createMockedResponse()))
        ;
        $logger = $this->createTestLogger();

        $client = new AsyncAwsClient(
            $dynamoDbClientMock,
            $this->createSerializer(),
            $logger
        );

        $fixture = new class() extends AbstractFixture implements FixtureInterface {
            public function configure(): void
            {
                $this
                    ->setTableName('Users')
                    ->addRecord(
                        new Record([
                            Value::stringValue('Id', '5957ddc9-6039-4e76-85e7-3d759a9d819c'),
                        ])
                    )
                ;
            }
        };
        $fixture->setValidator($validator);
        $fixture->load($client, $logger);

        $expectedLogs = [
            [
                'debug',
                'Single record loaded',
                [
                    'table' => 'Users',
                ],
            ],
        ];

        self::assertSame($expectedLogs, $logger->cleanLogs());
    }

    public function testLoadBatchRecords(): void
    {
        $validator = $this->createValidator();
        $dynamoDbClientMock = $this->getMockBuilder(DynamoDbClient::class)
            ->onlyMethods(['batchWriteItem'])
            ->getMock()
        ;
        $dynamoDbClientMock
            ->expects(self::once())
            ->method('batchWriteItem')
            ->with(
                new BatchWriteItemInput([
                    'RequestItems' => [
                        'Users' => [
                            new WriteRequest([
                                'PutRequest' => new PutRequest([
                                    'Item' => [
                                        'Id' => new AttributeValue([
                                            'S' => 'dbd4b1c9-4b63-4660-a99c-37cfaf4d98ca',
                                        ]),
                                    ],
                                ]),
                            ]),
                            new WriteRequest([
                                'PutRequest' => new PutRequest([
                                    'Item' => [
                                        'Id' => new AttributeValue([
                                            'S' => '4a830c9f-1d8c-4e6f-aa89-ba67c06360f2',
                                        ]),
                                    ],
                                ]),
                            ]),
                        ],
                    ],
                ])
            )
            ->willReturn(new BatchWriteItemOutput($this->createMockedResponse()))
        ;
        $logger = $this->createTestLogger();

        $client = new AsyncAwsClient(
            $dynamoDbClientMock,
            $this->createSerializer(),
            $logger
        );

        $fixture = new class() extends AbstractFixture implements FixtureInterface {
            public function configure(): void
            {
                $this
                    ->setTableName('Users')
                    ->addRecords([
                        new Record([
                            Value::stringValue('Id', 'dbd4b1c9-4b63-4660-a99c-37cfaf4d98ca'),
                        ]),
                        new Record([
                            Value::stringValue('Id', '4a830c9f-1d8c-4e6f-aa89-ba67c06360f2'),
                        ]),
                    ])
                ;
            }
        };
        $fixture->setValidator($validator);
        $fixture->load($client, $logger);

        $expectedLogs = [
            [
                'debug',
                'Data batch executed',
                [
                    'table' => 'Users',
                    'batch' => '#1',
                ],
            ],
            [
                'debug',
                'Batch records loaded',
                [
                    'table' => 'Users',
                ],
            ],
        ];

        self::assertSame($expectedLogs, $logger->cleanLogs());
    }

    private function createMockedResponse(): Response
    {
        $client = new MockHttpClient(new SimpleMockedResponse('{}', [], 200));

        return new Response(
            $client->request('POST', 'http://localhost'),
            $client,
            new NullLogger()
        );
    }
}
