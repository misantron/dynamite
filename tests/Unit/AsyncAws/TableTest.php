<?php

declare(strict_types=1);

namespace Dynamite\Tests\Unit\AsyncAws;

use AsyncAws\Core\Test\ResultMockFactory;
use AsyncAws\DynamoDb\DynamoDbClient;
use AsyncAws\DynamoDb\Input\CreateTableInput;
use AsyncAws\DynamoDb\Result\CreateTableOutput;
use Dynamite\AbstractTable;
use Dynamite\Client\AsyncAwsClient;
use Dynamite\Enum\KeyType;
use Dynamite\Enum\ProjectionType;
use Dynamite\Enum\ScalarAttributeType;
use Dynamite\Exception\ValidationException;
use Dynamite\Schema\Attribute;
use Dynamite\TableInterface;
use Dynamite\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

#[Group('unit')]
class TableTest extends UnitTestCase
{
    public function testLoadWithoutTableNameSet(): void
    {
        $validator = $this->createValidator();
        $serializer = $this->createSerializer();
        $dynamoDbClientMock = $this->createMock(DynamoDbClient::class);
        $logger = $this->createTestLogger();

        $client = new AsyncAwsClient(
            $dynamoDbClientMock,
            $serializer,
            $logger
        );

        $table = new class() extends AbstractTable implements TableInterface {
            public function configure(): void
            {
                $this->addAttributes([
                    new Attribute('Id', ScalarAttributeType::String),
                    new Attribute('Email', ScalarAttributeType::String),
                ]);
            }
        };

        try {
            $table->setValidator($validator);
            $table->setNormalizer($serializer);

            $table->create($client, $logger);

            self::fail('Exception is not thrown');
        } catch (\Throwable $e) {
            $expectedErrors = [
                'tableName' => [
                    'Table name is not defined',
                ],
            ];

            $this->assertInstanceOf(ValidationException::class, $e);
            $this->assertSame('Validation failed', $e->getMessage());
            $this->assertSame($expectedErrors, $e->getErrors());
        }

        $logger->cleanLogs();
    }

    public function testAddLocalSecondaryIndex(): void
    {
        $dynamoDbClientMock = $this->createMock(DynamoDbClient::class);
        $dynamoDbClientMock
            ->expects($this->once())
            ->method('createTable')
            ->with(
                new CreateTableInput([
                    'TableName' => 'Users',
                    'AttributeDefinitions' => [
                        [
                            'AttributeName' => 'Id',
                            'AttributeType' => ScalarAttributeType::String->value,
                        ],
                        [
                            'AttributeName' => 'Email',
                            'AttributeType' => ScalarAttributeType::String->value,
                        ],
                    ],
                    'KeySchema' => [
                        [
                            'AttributeName' => 'Id',
                            'KeyType' => KeyType::Hash->value,
                        ],
                        [
                            'AttributeName' => 'Email',
                            'KeyType' => KeyType::Range->value,
                        ],
                    ],
                    'LocalSecondaryIndexes' => [
                        [
                            'IndexName' => 'Emails',
                            'KeySchema' => [
                                [
                                    'AttributeName' => 'Email',
                                    'KeyType' => KeyType::Hash->value,
                                ],
                            ],
                            'Projection' => [
                                'ProjectionType' => ProjectionType::All->value,
                            ],
                        ],
                    ],
                ])
            )
            ->willReturn(
                ResultMockFactory::create(CreateTableOutput::class)
            )
        ;

        $validator = $this->createValidator();
        $serializer = $this->createSerializer();
        $logger = $this->createTestLogger();

        $client = new AsyncAwsClient(
            $dynamoDbClientMock,
            $serializer,
            $logger
        );

        $table = new class() extends AbstractTable implements TableInterface {
            public function configure(): void
            {
                $this
                    ->setTableName('Users')
                    ->addAttribute('Id', ScalarAttributeType::String, KeyType::Hash)
                    ->addAttribute('Email', ScalarAttributeType::String, KeyType::Range)
                    ->addLocalSecondaryIndex('Emails', ProjectionType::All, 'Email')
                ;
            }
        };
        $table->setValidator($validator);
        $table->setNormalizer($serializer);

        $table->create($client, $logger);

        $logger->cleanLogs();
    }

    public function testCreate(): void
    {
        $dynamoDbClientMock = $this->createMock(DynamoDbClient::class);
        $dynamoDbClientMock
            ->expects($this->once())
            ->method('createTable')
            ->with(
                new CreateTableInput([
                    'TableName' => 'Users',
                    'AttributeDefinitions' => [
                        [
                            'AttributeName' => 'Id',
                            'AttributeType' => ScalarAttributeType::String->value,
                        ],
                        [
                            'AttributeName' => 'Email',
                            'AttributeType' => ScalarAttributeType::String->value,
                        ],
                        [
                            'AttributeName' => 'Active',
                            'AttributeType' => ScalarAttributeType::Binary->value,
                        ],
                        [
                            'AttributeName' => 'CreatedAt',
                            'AttributeType' => ScalarAttributeType::Numeric->value,
                        ],
                    ],
                    'KeySchema' => [
                        [
                            'AttributeName' => 'Id',
                            'KeyType' => KeyType::Hash->value,
                        ],
                        [
                            'AttributeName' => 'CreatedAt',
                            'KeyType' => KeyType::Range->value,
                        ],
                    ],
                    'GlobalSecondaryIndexes' => [
                        [
                            'IndexName' => 'Emails',
                            'KeySchema' => [
                                [
                                    'AttributeName' => 'Email',
                                    'KeyType' => KeyType::Hash->value,
                                ],
                                [
                                    'AttributeName' => 'Id',
                                    'KeyType' => KeyType::Range->value,
                                ],
                            ],
                            'Projection' => [
                                'ProjectionType' => ProjectionType::KeysOnly->value,
                            ],
                            'ProvisionedThroughput' => [
                                'ReadCapacityUnits' => 1,
                                'WriteCapacityUnits' => 1,
                            ],
                        ],
                    ],
                    'ProvisionedThroughput' => [
                        'ReadCapacityUnits' => 1,
                        'WriteCapacityUnits' => 1,
                    ],
                ])
            )
            ->willReturn(
                ResultMockFactory::create(CreateTableOutput::class)
            )
        ;

        $validator = $this->createValidator();
        $serializer = $this->createSerializer();
        $logger = $this->createTestLogger();

        $client = new AsyncAwsClient(
            $dynamoDbClientMock,
            $serializer,
            $logger
        );

        $table = new class() extends AbstractTable implements TableInterface {
            public function configure(): void
            {
                $this
                    ->setTableName('Users')
                    ->addAttributes([
                        new Attribute('Id', ScalarAttributeType::String, KeyType::Hash),
                        new Attribute('Email', ScalarAttributeType::String),
                        new Attribute('Active', ScalarAttributeType::Binary),
                        new Attribute('CreatedAt', ScalarAttributeType::Numeric, KeyType::Range),
                    ])
                    ->addGlobalSecondaryIndex(
                        'Emails',
                        ProjectionType::KeysOnly,
                        'Email',
                        'Id'
                    )
                    ->setProvisionedThroughput(1, 1)
                ;
            }
        };
        $table->setValidator($validator);
        $table->setNormalizer($serializer);

        $table->create($client, $logger);

        $expectedLogs = [
            [
                'debug',
                'Table created',
                [
                    'table' => 'Users',
                ],
            ],
        ];

        $this->assertSame($expectedLogs, $logger->cleanLogs());
    }
}
