<?php

declare(strict_types=1);

namespace Dynamite\Tests\Unit;

use AsyncAws\Core\Test\ResultMockFactory;
use AsyncAws\DynamoDb\DynamoDbClient;
use AsyncAws\DynamoDb\Enum\KeyType;
use AsyncAws\DynamoDb\Enum\ProjectionType;
use AsyncAws\DynamoDb\Enum\ScalarAttributeType;
use AsyncAws\DynamoDb\Input\CreateTableInput;
use AsyncAws\DynamoDb\Input\DeleteTableInput;
use AsyncAws\DynamoDb\Input\DescribeTableInput;
use AsyncAws\DynamoDb\Input\UpdateTableInput;
use AsyncAws\DynamoDb\Result\CreateTableOutput;
use AsyncAws\DynamoDb\Result\DeleteTableOutput;
use AsyncAws\DynamoDb\Result\DescribeTableOutput;
use AsyncAws\DynamoDb\Result\UpdateTableOutput;
use AsyncAws\DynamoDb\ValueObject\GlobalSecondaryIndexDescription;
use AsyncAws\DynamoDb\ValueObject\TableDescription;
use Dynamite\AbstractMigration;
use Dynamite\Exception\IndexException;
use Dynamite\Exception\SchemaException;
use Dynamite\Exception\TableException;

class MigrationTest extends UnitTestCase
{
    public function testAddAttributeWithUnexpectedType(): void
    {
        $this->expectException(SchemaException::class);
        $this->expectExceptionMessage('Unexpected attribute type `U` provided');

        $validator = $this->createValidator();
        $serializer = $this->createSerializer();
        $dynamoDbClient = $this->createMock(DynamoDbClient::class);

        $migration = new class($dynamoDbClient, $serializer, $validator) extends AbstractMigration {
            public function up(): void
            {
                $this
                    ->setTableName('Users')
                    ->addAttribute('Id', 'U')
                    ->create()
                ;
            }
        };
        $migration->up();
    }

    public function testAddGlobalSecondaryIndexWithUnexpectedProjectionType(): void
    {
        $this->expectException(SchemaException::class);
        $this->expectExceptionMessage('Unexpected projection type `EXCLUDE` provided');

        $validator = $this->createValidator();
        $serializer = $this->createSerializer();
        $dynamoDbClient = $this->createMock(DynamoDbClient::class);

        $migration = new class($dynamoDbClient, $serializer, $validator) extends AbstractMigration {
            public function up(): void
            {
                $this
                    ->setTableName('Users')
                    ->addGlobalSecondaryIndex('Index', 'EXCLUDE', 'Id')
                    ->create()
                ;
            }
        };
        $migration->up();
    }

    public function testAddLocalSecondaryIndex(): void
    {
        $dynamoDbClientMock = $this->createMock(DynamoDbClient::class);
        $dynamoDbClientMock
            ->expects(self::once())
            ->method('describeTable')
            ->with(
                new DescribeTableInput([
                    'TableName' => 'Users',
                ])
            )
            ->willThrowException($this->createResourceNotFoundException())
        ;
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

        $migration = new class($dynamoDbClientMock, $serializer, $validator) extends AbstractMigration {
            public function up(): void
            {
                $this
                    ->setTableName('Users')
                    ->addAttribute('Id', ScalarAttributeType::S)
                    ->addAttribute('Email', ScalarAttributeType::S)
                    ->addHashKey('Id')
                    ->addRangeKey('Email')
                    ->addLocalSecondaryIndex('Emails', 'Email')
                    ->create()
                ;
            }
        };
        $migration->up();
    }

    public function testCreateWithAlreadyExistTable(): void
    {
        $this->expectException(TableException::class);
        $this->expectErrorMessage('Table `Users` already exists');

        $dynamoDbClientMock = $this->createMock(DynamoDbClient::class);
        $dynamoDbClientMock
            ->expects(self::once())
            ->method('describeTable')
            ->with(
                new DescribeTableInput([
                    'TableName' => 'Users',
                ])
            )
            ->willReturn(
                ResultMockFactory::create(DescribeTableOutput::class)
            )
        ;

        $validator = $this->createValidator();
        $serializer = $this->createSerializer();

        $migration = new class($dynamoDbClientMock, $serializer, $validator) extends AbstractMigration {
            public function up(): void
            {
                $this
                    ->setTableName('Users')
                    ->addAttributes([
                        ['Id', ScalarAttributeType::S],
                        ['Email', ScalarAttributeType::S],
                    ])
                    ->addHashKey('Id')
                    ->create()
                ;
            }
        };
        $migration->up();
    }

    public function testCreate(): void
    {
        $dynamoDbClientMock = $this->createMock(DynamoDbClient::class);
        $dynamoDbClientMock
            ->expects(self::once())
            ->method('describeTable')
            ->with(
                new DescribeTableInput([
                    'TableName' => 'Users',
                ])
            )
            ->willThrowException($this->createResourceNotFoundException())
        ;
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
                ])
            )
            ->willReturn(
                ResultMockFactory::create(CreateTableOutput::class)
            )
        ;

        $validator = $this->createValidator();
        $serializer = $this->createSerializer();

        $migration = new class($dynamoDbClientMock, $serializer, $validator) extends AbstractMigration {
            public function up(): void
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
                        'Id',
                        1,
                        1
                    )
                    ->create()
                ;
            }
        };
        $migration->up();
    }

    public function testUpdateWithNotExistTable(): void
    {
        $this->expectException(TableException::class);
        $this->expectErrorMessage('Table `Users` does not exist');

        $dynamoDbClientMock = $this->createMock(DynamoDbClient::class);
        $dynamoDbClientMock
            ->expects(self::once())
            ->method('describeTable')
            ->with(
                new DescribeTableInput([
                    'TableName' => 'Users',
                ])
            )
            ->willThrowException($this->createResourceNotFoundException())
        ;

        $validator = $this->createValidator();
        $serializer = $this->createSerializer();

        $migration = new class($dynamoDbClientMock, $serializer, $validator) extends AbstractMigration {
            public function up(): void
            {
                $this
                    ->setTableName('Users')
                    ->setProvisionedThroughput(5, 5)
                    ->update()
                ;
            }
        };
        $migration->up();
    }

    public function testUpdateWithNotExistGlobalSecondaryIndex(): void
    {
        $this->expectException(IndexException::class);
        $this->expectExceptionMessage('Index `Emails` does not exist');

        $dynamoDbClientMock = $this->createMock(DynamoDbClient::class);
        $dynamoDbClientMock
            ->expects(self::once())
            ->method('describeTable')
            ->with(
                new DescribeTableInput([
                    'TableName' => 'Users',
                ])
            )
            ->willReturn(
                ResultMockFactory::create(DescribeTableOutput::class, [
                    'Table' => new TableDescription([
                        'GlobalSecondaryIndexes' => null,
                    ]),
                ])
            )
        ;

        $validator = $this->createValidator();
        $serializer = $this->createSerializer();

        $migration = new class($dynamoDbClientMock, $serializer, $validator) extends AbstractMigration {
            public function up(): void
            {
                $this
                    ->setTableName('Users')
                    ->addAttribute('Email', ScalarAttributeType::S)
                    ->deleteGlobalSecondaryIndex('Emails')
                    ->update()
                ;
            }
        };
        $migration->up();
    }

    public function testUpdateWithAlreadyExistGlobalSecondaryIndex(): void
    {
        $this->expectException(IndexException::class);
        $this->expectExceptionMessage('Index `Emails` already exists');

        $dynamoDbClientMock = $this->createMock(DynamoDbClient::class);
        $dynamoDbClientMock
            ->expects(self::once())
            ->method('describeTable')
            ->with(
                new DescribeTableInput([
                    'TableName' => 'Users',
                ])
            )
            ->willReturn(
                ResultMockFactory::create(DescribeTableOutput::class, [
                    'Table' => new TableDescription([
                        'GlobalSecondaryIndexes' => [
                            new GlobalSecondaryIndexDescription([
                                'IndexName' => 'Emails',
                            ]),
                        ],
                    ]),
                ])
            )
        ;

        $validator = $this->createValidator();
        $serializer = $this->createSerializer();

        $migration = new class($dynamoDbClientMock, $serializer, $validator) extends AbstractMigration {
            public function up(): void
            {
                $this
                    ->setTableName('Users')
                    ->addAttribute('Email', ScalarAttributeType::S)
                    ->createGlobalSecondaryIndex('Emails', ProjectionType::KEYS_ONLY, 'Email')
                    ->setProvisionedThroughput(5, 5)
                    ->update()
                ;
            }
        };
        $migration->up();
    }

    public function testUpdateWithCreateGlobalIndexWithInvalidProjectionType(): void
    {
        $this->expectException(SchemaException::class);
        $this->expectExceptionMessage('Unexpected projection type `EXCLUDE` provided');

        $dynamoDbClientMock = $this->createMock(DynamoDbClient::class);
        $validator = $this->createValidator();
        $serializer = $this->createSerializer();

        $migration = new class($dynamoDbClientMock, $serializer, $validator) extends AbstractMigration {
            public function up(): void
            {
                $this
                    ->setTableName('Users')
                    ->addAttribute('Email', ScalarAttributeType::S)
                    ->createGlobalSecondaryIndex('Emails', 'EXCLUDE', 'Email')
                    ->setProvisionedThroughput(5, 5)
                    ->update()
                ;
            }
        };
        $migration->up();
    }

    public function testUpdate(): void
    {
        $dynamoDbClientMock = $this->createMock(DynamoDbClient::class);
        $dynamoDbClientMock
            ->expects(self::once())
            ->method('describeTable')
            ->with(
                new DescribeTableInput([
                    'TableName' => 'Users',
                ])
            )
            ->willReturn(
                ResultMockFactory::create(DescribeTableOutput::class, [
                    'Table' => new TableDescription([
                        'GlobalSecondaryIndexes' => [
                            new GlobalSecondaryIndexDescription([
                                'IndexName' => 'Emails',
                            ]),
                        ],
                    ]),
                ])
            )
        ;
        $dynamoDbClientMock
            ->expects(self::once())
            ->method('updateTable')
            ->with(
                new UpdateTableInput([
                    'TableName' => 'Users',
                    'AttributeDefinitions' => [
                        [
                            'AttributeName' => 'Email',
                            'AttributeType' => ScalarAttributeType::S,
                        ],
                    ],
                    'GlobalSecondaryIndexUpdates' => [
                        'Update' => [
                            'IndexName' => 'Emails',
                            'ProvisionedThroughput' => [
                                'ReadCapacityUnits' => 5,
                                'WriteCapacityUnits' => 5,
                            ],
                        ],
                    ],
                    'ProvisionedThroughput' => [
                        'ReadCapacityUnits' => 5,
                        'WriteCapacityUnits' => 5,
                    ],
                ])
            )
            ->willReturn(
                ResultMockFactory::create(UpdateTableOutput::class)
            )
        ;

        $validator = $this->createValidator();
        $serializer = $this->createSerializer();

        $migration = new class($dynamoDbClientMock, $serializer, $validator) extends AbstractMigration {
            public function up(): void
            {
                $this
                    ->setTableName('Users')
                    ->addAttribute('Email', ScalarAttributeType::S)
                    ->updateGlobalSecondaryIndex('Emails')
                    ->setProvisionedThroughput(5, 5)
                    ->update()
                ;
            }
        };
        $migration->up();
    }

    public function testDeleteWithNotExistTable(): void
    {
        $this->expectException(TableException::class);
        $this->expectErrorMessage('Table `Users` does not exist');

        $dynamoDbClientMock = $this->createMock(DynamoDbClient::class);
        $dynamoDbClientMock
            ->expects(self::once())
            ->method('describeTable')
            ->with(
                new DescribeTableInput([
                    'TableName' => 'Users',
                ])
            )
            ->willThrowException($this->createResourceNotFoundException())
        ;

        $validator = $this->createValidator();
        $serializer = $this->createSerializer();

        $migration = new class($dynamoDbClientMock, $serializer, $validator) extends AbstractMigration {
            public function up(): void
            {
                $this
                    ->setTableName('Users')
                    ->delete()
                ;
            }
        };
        $migration->up();
    }

    public function testDelete(): void
    {
        $dynamoDbClientMock = $this->createMock(DynamoDbClient::class);
        $dynamoDbClientMock
            ->expects(self::once())
            ->method('describeTable')
            ->with(
                new DescribeTableInput([
                    'TableName' => 'Users',
                ])
            )
            ->willReturn(
                ResultMockFactory::create(DescribeTableOutput::class)
            )
        ;
        $dynamoDbClientMock
            ->expects(self::once())
            ->method('deleteTable')
            ->with(
                new DeleteTableInput([
                    'TableName' => 'Users',
                ])
            )
            ->willReturn(
                ResultMockFactory::create(DeleteTableOutput::class)
            )
        ;

        $validator = $this->createValidator();
        $serializer = $this->createSerializer();

        $migration = new class($dynamoDbClientMock, $serializer, $validator) extends AbstractMigration {
            public function up(): void
            {
                $this
                    ->setTableName('Users')
                    ->delete()
                ;
            }
        };
        $migration->up();
    }
}
