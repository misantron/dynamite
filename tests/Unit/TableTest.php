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
use Dynamite\TableInterface;

class TableTest extends UnitTestCase
{
    public function testAddAttributeWithUnexpectedType(): void
    {
        $this->expectException(SchemaException::class);
        $this->expectExceptionMessage('Unexpected attribute type `U` provided');

        $validator = $this->createValidator();
        $serializer = $this->createSerializer();
        $dynamoDbClient = $this->createMock(DynamoDbClient::class);

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

        $table->create($dynamoDbClient);
    }

    public function testAddGlobalSecondaryIndexWithUnexpectedProjectionType(): void
    {
        $this->expectException(SchemaException::class);
        $this->expectExceptionMessage('Unexpected projection type `EXCLUDE` provided');

        $validator = $this->createValidator();
        $serializer = $this->createSerializer();
        $dynamoDbClient = $this->createMock(DynamoDbClient::class);

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

        $table->create($dynamoDbClient);
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

        $table->create($dynamoDbClientMock);
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

        $table->create($dynamoDbClientMock);
    }
}
