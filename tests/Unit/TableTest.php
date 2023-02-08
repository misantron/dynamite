<?php

declare(strict_types=1);

namespace Dynamite\Tests\Unit;

use AsyncAws\Core\Test\ResultMockFactory;
use AsyncAws\DynamoDb\DynamoDbClient;
use AsyncAws\DynamoDb\Input\CreateTableInput;
use AsyncAws\DynamoDb\Result\CreateTableOutput;
use Dynamite\AbstractTable;
use Dynamite\Client\AsyncAwsClient;
use Dynamite\Enum\KeyTypeEnum;
use Dynamite\Enum\ProjectionTypeEnum;
use Dynamite\Enum\ScalarAttributeTypeEnum;
use Dynamite\Exception\ValidationException;
use Dynamite\Schema\Attribute;
use Dynamite\TableInterface;

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
                    new Attribute('Id', ScalarAttributeTypeEnum::String),
                    new Attribute('Email', ScalarAttributeTypeEnum::String),
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

            self::assertInstanceOf(ValidationException::class, $e);
            self::assertSame('Validation failed', $e->getMessage());
            self::assertSame($expectedErrors, $e->getErrors());
        }

        $logger->cleanLogs();
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
                            'AttributeType' => ScalarAttributeTypeEnum::String->value,
                        ],
                        [
                            'AttributeName' => 'Email',
                            'AttributeType' => ScalarAttributeTypeEnum::String->value,
                        ],
                    ],
                    'KeySchema' => [
                        [
                            'AttributeName' => 'Id',
                            'KeyType' => KeyTypeEnum::Hash->value,
                        ],
                        [
                            'AttributeName' => 'Email',
                            'KeyType' => KeyTypeEnum::Range->value,
                        ],
                    ],
                    'LocalSecondaryIndexes' => [
                        [
                            'IndexName' => 'Emails',
                            'KeySchema' => [
                                [
                                    'AttributeName' => 'Email',
                                    'KeyType' => KeyTypeEnum::Hash->value,
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
                    ->addAttribute('Id', ScalarAttributeTypeEnum::String, KeyTypeEnum::Hash)
                    ->addAttribute('Email', ScalarAttributeTypeEnum::String, KeyTypeEnum::Range)
                    ->addLocalSecondaryIndex('Emails', 'Email')
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
            ->expects(self::once())
            ->method('createTable')
            ->with(
                new CreateTableInput([
                    'TableName' => 'Users',
                    'AttributeDefinitions' => [
                        [
                            'AttributeName' => 'Id',
                            'AttributeType' => ScalarAttributeTypeEnum::String->value,
                        ],
                        [
                            'AttributeName' => 'Email',
                            'AttributeType' => ScalarAttributeTypeEnum::String->value,
                        ],
                        [
                            'AttributeName' => 'Active',
                            'AttributeType' => ScalarAttributeTypeEnum::Binary->value,
                        ],
                        [
                            'AttributeName' => 'CreatedAt',
                            'AttributeType' => ScalarAttributeTypeEnum::Numeric->value,
                        ],
                    ],
                    'KeySchema' => [
                        [
                            'AttributeName' => 'Id',
                            'KeyType' => KeyTypeEnum::Hash->value,
                        ],
                        [
                            'AttributeName' => 'CreatedAt',
                            'KeyType' => KeyTypeEnum::Range->value,
                        ],
                    ],
                    'GlobalSecondaryIndexes' => [
                        [
                            'IndexName' => 'Emails',
                            'KeySchema' => [
                                [
                                    'AttributeName' => 'Email',
                                    'KeyType' => KeyTypeEnum::Hash->value,
                                ],
                                [
                                    'AttributeName' => 'Id',
                                    'KeyType' => KeyTypeEnum::Range->value,
                                ],
                            ],
                            'Projection' => [
                                'ProjectionType' => ProjectionTypeEnum::KeysOnly->value,
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
                        new Attribute('Id', ScalarAttributeTypeEnum::String, KeyTypeEnum::Hash),
                        new Attribute('Email', ScalarAttributeTypeEnum::String),
                        new Attribute('Active', ScalarAttributeTypeEnum::Binary),
                        new Attribute('CreatedAt', ScalarAttributeTypeEnum::Numeric, KeyTypeEnum::Range),
                    ])
                    ->addGlobalSecondaryIndex(
                        'Emails',
                        ProjectionTypeEnum::KeysOnly,
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

        self::assertSame($expectedLogs, $logger->cleanLogs());
    }
}
