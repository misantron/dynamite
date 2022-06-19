<?php

declare(strict_types=1);

namespace Dynamite\Tests\Unit;

use AsyncAws\Core\Test\ResultMockFactory;
use AsyncAws\DynamoDb\DynamoDbClient;
use AsyncAws\DynamoDb\Enum\KeyType;
use AsyncAws\DynamoDb\Enum\ProjectionType;
use AsyncAws\DynamoDb\Enum\ScalarAttributeType;
use AsyncAws\DynamoDb\Input\CreateTableInput;
use AsyncAws\DynamoDb\Input\DescribeTableInput;
use AsyncAws\DynamoDb\Result\CreateTableOutput;
use AsyncAws\DynamoDb\Result\DescribeTableOutput;
use Dynamite\AbstractMigration;
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
}
