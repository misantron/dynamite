<?php

declare(strict_types=1);

namespace Dynamite\Tests\Unit;

use AsyncAws\Core\Test\ResultMockFactory;
use AsyncAws\DynamoDb\DynamoDbClient;
use AsyncAws\DynamoDb\Enum\KeyType;
use AsyncAws\DynamoDb\Enum\ProjectionType;
use AsyncAws\DynamoDb\Enum\ScalarAttributeType;
use AsyncAws\DynamoDb\Input\CreateTableInput;
use AsyncAws\DynamoDb\Result\CreateTableOutput;
use Dynamite\AbstractTable;
use Dynamite\Exception\SchemaException;
use Dynamite\Exception\ValidationException;
use Dynamite\TableInterface;
use Psr\Log\LogLevel;

class TableTest extends UnitTestCase
{
    public function testLoadWithoutTableNameSet(): void
    {
        $validator = $this->createValidator();
        $serializer = $this->createSerializer();
        $dynamoDbClientMock = $this->createMock(DynamoDbClient::class);
        $logger = $this->createTestLogger();

        $table = new class() extends AbstractTable implements TableInterface {
            public function configure(): void
            {
                $this->addAttributes([
                    ['Id', ScalarAttributeType::S],
                    ['Email', ScalarAttributeType::S],
                ]);
            }
        };

        try {
            $table->setValidator($validator);
            $table->setNormalizer($serializer);
            $table->create($dynamoDbClientMock, $logger);

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
    }

    public function testAddAttributeWithUnexpectedType(): void
    {
        $this->expectException(SchemaException::class);
        $this->expectExceptionMessage('Unexpected attribute type `U` provided');

        $validator = $this->createValidator();
        $serializer = $this->createSerializer();
        $dynamoDbClient = $this->createMock(DynamoDbClient::class);
        $logger = $this->createTestLogger();

        $table = new class() extends AbstractTable implements TableInterface {
            public function configure(): void
            {
                $this
                    ->setTableName('Users')
                    ->addAttribute('Id', 'U')
                ;
            }
        };
        $table->setValidator($validator);
        $table->setNormalizer($serializer);

        $table->create($dynamoDbClient, $logger);
    }

    public function testAddGlobalSecondaryIndexWithUnexpectedProjectionType(): void
    {
        $this->expectException(SchemaException::class);
        $this->expectExceptionMessage('Unexpected projection type `EXCLUDE` provided');

        $validator = $this->createValidator();
        $serializer = $this->createSerializer();
        $dynamoDbClient = $this->createMock(DynamoDbClient::class);
        $logger = $this->createTestLogger();

        $table = new class() extends AbstractTable implements TableInterface {
            public function configure(): void
            {
                $this
                    ->setTableName('Users')
                    ->addGlobalSecondaryIndex('Index', 'EXCLUDE', 'Id')
                ;
            }
        };
        $table->setValidator($validator);
        $table->setNormalizer($serializer);

        $table->create($dynamoDbClient, $logger);
    }

    public function testAddLocalSecondaryIndex(): void
    {
        $dynamoDbClientMock = $this->createMock(DynamoDbClient::class);
        $dynamoDbClientMock
            ->expects(self::once())
            ->method('createTable')
            ->with(
                new CreateTableInput([
                    'TableName' => 'Users',
                    'AttributeDefinitions' => [
                        [
                            'AttributeName' => 'Id',
                            'AttributeType' => ScalarAttributeType::S,
                        ],
                        [
                            'AttributeName' => 'Email',
                            'AttributeType' => ScalarAttributeType::S,
                        ],
                    ],
                    'KeySchema' => [
                        [
                            'AttributeName' => 'Id',
                            'KeyType' => KeyType::HASH,
                        ],
                        [
                            'AttributeName' => 'Email',
                            'KeyType' => KeyType::RANGE,
                        ],
                    ],
                    'LocalSecondaryIndexes' => [
                        [
                            'IndexName' => 'Emails',
                            'KeySchema' => [
                                [
                                    'AttributeName' => 'Email',
                                    'KeyType' => KeyType::HASH,
                                ],
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

        $table = new class() extends AbstractTable implements TableInterface {
            public function configure(): void
            {
                $this
                    ->setTableName('Users')
                    ->addAttribute('Id', ScalarAttributeType::S)
                    ->addAttribute('Email', ScalarAttributeType::S)
                    ->addHashKey('Id')
                    ->addRangeKey('Email')
                    ->addLocalSecondaryIndex('Emails', 'Email')
                ;
            }
        };
        $table->setValidator($validator);
        $table->setNormalizer($serializer);

        $table->create($dynamoDbClientMock, $logger);
    }

    public function testCreate(): void
    {
        $dynamoDbClientMock = $this->createMock(DynamoDbClient::class);
        $dynamoDbClientMock
            ->expects(self::once())
            ->method('createTable')
            ->with(
                new CreateTableInput([
                    'TableName' => 'Users',
                    'AttributeDefinitions' => [
                        [
                            'AttributeName' => 'Id',
                            'AttributeType' => ScalarAttributeType::S,
                        ],
                        [
                            'AttributeName' => 'Email',
                            'AttributeType' => ScalarAttributeType::S,
                        ],
                        [
                            'AttributeName' => 'Active',
                            'AttributeType' => ScalarAttributeType::B,
                        ],
                        [
                            'AttributeName' => 'CreatedAt',
                            'AttributeType' => ScalarAttributeType::N,
                        ],
                    ],
                    'KeySchema' => [
                        [
                            'AttributeName' => 'Id',
                            'KeyType' => KeyType::HASH,
                        ],
                        [
                            'AttributeName' => 'CreatedAt',
                            'KeyType' => KeyType::RANGE,
                        ],
                    ],
                    'GlobalSecondaryIndexes' => [
                        [
                            'IndexName' => 'Emails',
                            'KeySchema' => [
                                [
                                    'AttributeName' => 'Email',
                                    'KeyType' => KeyType::HASH,
                                ],
                                [
                                    'AttributeName' => 'Id',
                                    'KeyType' => KeyType::RANGE,
                                ],
                            ],
                            'Projection' => [
                                'ProjectionType' => ProjectionType::KEYS_ONLY,
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

        $table = new class() extends AbstractTable implements TableInterface {
            public function configure(): void
            {
                $this
                    ->setTableName('Users')
                    ->addAttributes([
                        ['Id', ScalarAttributeType::S],
                        ['Email', ScalarAttributeType::S],
                        ['Active', ScalarAttributeType::B],
                        ['CreatedAt', ScalarAttributeType::N],
                    ])
                    ->addHashKey('Id')
                    ->addRangeKey('CreatedAt')
                    ->addGlobalSecondaryIndex(
                        'Emails',
                        ProjectionType::KEYS_ONLY,
                        'Email',
                        'Id'
                    )
                    ->setProvisionedThroughput(1, 1)
                ;
            }
        };
        $table->setValidator($validator);
        $table->setNormalizer($serializer);

        $table->create($dynamoDbClientMock, $logger);

        self::assertTrue(
            $logger->hasRecord(
                [
                    'message' => 'Table created',
                    'context' => [
                        'table' => 'Users',
                    ],
                ],
                LogLevel::DEBUG
            )
        );
    }
}
