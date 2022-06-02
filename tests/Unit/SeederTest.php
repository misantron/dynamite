<?php

declare(strict_types=1);

namespace Dynamite\Tests\Unit;

use AsyncAws\DynamoDb\DynamoDbClient;
use AsyncAws\DynamoDb\Input\BatchWriteItemInput;
use AsyncAws\DynamoDb\Input\DescribeTableInput;
use AsyncAws\DynamoDb\Input\PutItemInput;
use AsyncAws\DynamoDb\Result\BatchWriteItemOutput;
use AsyncAws\DynamoDb\Result\DescribeTableOutput;
use AsyncAws\DynamoDb\Result\PutItemOutput;
use AsyncAws\DynamoDb\ValueObject\AttributeValue;
use AsyncAws\DynamoDb\ValueObject\PutRequest;
use AsyncAws\DynamoDb\ValueObject\WriteRequest;
use Dynamite\AbstractSeeder;
use Dynamite\Exception\TableException;
use Dynamite\Exception\ValidationException;

class SeederTest extends UnitTestCase
{
    public function testSeedWithoutTableNameSet(): void
    {
        $validator = $this->createValidator();
        $dynamoDbClientMock = $this->createMock(DynamoDbClient::class);

        $class = new class($dynamoDbClientMock, $validator) extends AbstractSeeder {
            public function seed(): void
            {
                $this->save();
            }
        };

        try {
            $class->seed();

            self::fail('Exception is not thrown');
        } catch (\Throwable $e) {
            $expectedErrors = [
                'tableName' => [
                    'Table name is not defined',
                ],
                'records' => [
                    'At least 1 record is required',
                ],
            ];

            self::assertInstanceOf(ValidationException::class, $e);
            self::assertSame('Validation failed', $e->getMessage());
            self::assertSame($expectedErrors, $e->getErrors());
        }
    }

    public function testSeedWithNotExistsTable(): void
    {
        $this->expectException(TableException::class);
        $this->expectExceptionMessage('Table `Users` does not exist');

        $validator = $this->createValidator();
        $dynamoDbClientMock = $this->getMockBuilder(DynamoDbClient::class)
            ->onlyMethods(['describeTable'])
            ->getMock()
        ;
        $dynamoDbClientMock
            ->expects(self::once())
            ->method('describeTable')
            ->with(new DescribeTableInput([
                'TableName' => 'Users',
            ]))
            ->willThrowException($this->createResourceNotFoundException())
        ;

        $class = new class($dynamoDbClientMock, $validator) extends AbstractSeeder {
            public function seed(): void
            {
                $this
                    ->setTableName('Users')
                    ->addItem([
                        'Id' => [
                            'S' => '5957ddc9-6039-4e76-85e7-3d759a9d819c',
                        ],
                    ])
                    ->save()
                ;
            }
        };
        $class->seed();
    }

    public function testSeedSingleRecord(): void
    {
        $validator = $this->createValidator();
        $dynamoDbClientMock = $this->getMockBuilder(DynamoDbClient::class)
            ->onlyMethods(['describeTable', 'putItem'])
            ->getMock()
        ;
        $dynamoDbClientMock
            ->expects(self::once())
            ->method('describeTable')
            ->with(new DescribeTableInput([
                'TableName' => 'Users',
            ]))
            ->willReturn(new DescribeTableOutput($this->createMockedResponse()))
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

        $class = new class($dynamoDbClientMock, $validator) extends AbstractSeeder {
            public function seed(): void
            {
                $this
                    ->setTableName('Users')
                    ->addItem([
                        'Id' => [
                            'S' => '5957ddc9-6039-4e76-85e7-3d759a9d819c',
                        ],
                    ])
                    ->save()
                ;
            }
        };
        $class->seed();
    }

    public function testSeedBatchRecords(): void
    {
        $validator = $this->createValidator();
        $dynamoDbClientMock = $this->getMockBuilder(DynamoDbClient::class)
            ->onlyMethods(['describeTable', 'batchWriteItem'])
            ->getMock()
        ;
        $dynamoDbClientMock
            ->expects(self::once())
            ->method('describeTable')
            ->with(new DescribeTableInput([
                'TableName' => 'Users',
            ]))
            ->willReturn(new DescribeTableOutput($this->createMockedResponse()))
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

        $class = new class($dynamoDbClientMock, $validator) extends AbstractSeeder {
            public function seed(): void
            {
                $this
                    ->setTableName('Users')
                    ->addItems([
                        [
                            'Id' => [
                                'S' => 'dbd4b1c9-4b63-4660-a99c-37cfaf4d98ca',
                            ],
                        ],
                        [
                            'Id' => [
                                'S' => '4a830c9f-1d8c-4e6f-aa89-ba67c06360f2',
                            ],
                        ],
                    ])
                    ->save()
                ;
            }
        };
        $class->seed();
    }
}
