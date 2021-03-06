<?php

declare(strict_types=1);

namespace Dynamite\Tests\Integration\Purger;

use AsyncAws\DynamoDb\Enum\ScalarAttributeType;
use AsyncAws\DynamoDb\Exception\ResourceNotFoundException;
use Dynamite\AbstractFixture;
use Dynamite\AbstractTable;
use Dynamite\Executor;
use Dynamite\FixtureInterface;
use Dynamite\Loader;
use Dynamite\Purger\Purger;
use Dynamite\TableInterface;
use Dynamite\Tests\Integration\IntegrationTestCase;
use Faker\Factory;

class PurgerTest extends IntegrationTestCase
{
    public function testPurge(): void
    {
        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('Cannot do operations on a non-existent table');

        $this->createTable();

        $table = new class() extends AbstractTable implements TableInterface {
            protected function configure(): void
            {
                $this
                    ->setTableName('Users')
                    ->addAttributes([
                        ['Id', ScalarAttributeType::S],
                        ['Email', ScalarAttributeType::S],
                    ])
                    ->addHashKey('Id')
                    ->addRangeKey('Email')
                    ->setProvisionedThroughput(1, 1)
                ;
            }
        };
        $table->setValidator($this->validator);
        $table->setNormalizer($this->serializer);

        $fixture = new class() extends AbstractFixture implements FixtureInterface {
            protected function configure(): void
            {
                $this
                    ->setTableName('Users')
                    ->addItems([
                        [
                            'Id' => [
                                'S' => 'e5502ec2-42a7-408b-9f03-f8e162b6257e',
                            ],
                            'Email' => [
                                'S' => 'test.one@example.com',
                            ],
                        ],
                        [
                            'Id' => [
                                'S' => 'f0cf458c-4fc0-4dd8-ba5b-eca6dba9be63',
                            ],
                            'Email' => [
                                'S' => 'test.two@example.com',
                            ],
                        ],
                    ])
                ;
            }
        };
        $fixture->setValidator($this->validator);

        $purger = new Purger($this->dynamoDbClient, $this->logger);
        $purger->purge(
            [
                $fixture::class => $fixture,
            ],
            [
                $table::class => $table,
            ]
        );

        $response = $this->dynamoDbClient->describeTable([
            'TableName' => 'Users',
        ]);
        $response->resolve();
    }

    public function testPurgeOnlyFixtures(): void
    {
        $this->createTable();

        $fixture = new class() extends AbstractFixture implements FixtureInterface {
            protected function configure(): void
            {
                $this
                    ->setTableName('Users')
                    ->addItems([
                        [
                            'Id' => [
                                'S' => 'e5502ec2-42a7-408b-9f03-f8e162b6257e',
                            ],
                            'Email' => [
                                'S' => 'test.one@example.com',
                            ],
                        ],
                        [
                            'Id' => [
                                'S' => 'f0cf458c-4fc0-4dd8-ba5b-eca6dba9be63',
                            ],
                            'Email' => [
                                'S' => 'test.two@example.com',
                            ],
                        ],
                    ])
                ;
            }
        };
        $fixture->setValidator($this->validator);

        $fixtures = [
            $fixture::class => $fixture,
        ];

        $executor = new Executor($this->dynamoDbClient);
        $executor->execute($fixtures, []);

        $response = $this->dynamoDbClient->scan([
            'TableName' => 'Users',
        ]);
        $response->resolve();

        self::assertSame(2, $response->getCount());

        $purger = new Purger($this->dynamoDbClient, $this->logger);
        $purger->purge($fixtures, []);

        $response = $this->dynamoDbClient->scan([
            'TableName' => 'Users',
        ]);
        $response->resolve();

        self::assertSame(0, $response->getCount());
    }

    public function testPurgeLargeFixturesBatch(): void
    {
        $this->createTable();

        $fixture = new class() extends AbstractFixture implements FixtureInterface {
            protected function configure(): void
            {
                $this->setTableName('Users');
                $faker = Factory::create();

                for ($i = 0; $i < 70; ++$i) {
                    $this->addItem([
                        'Id' => [
                            ScalarAttributeType::S => $faker->uuid(),
                        ],
                        'Email' => [
                            ScalarAttributeType::S => $faker->email(),
                        ],
                    ]);
                }
            }
        };

        $loader = new Loader($this->validator, $this->serializer);
        $loader->addFixture($fixture);

        $executor = new Executor($this->dynamoDbClient);
        $executor->execute($loader->getFixtures(), []);

        $response = $this->dynamoDbClient->scan([
            'TableName' => 'Users',
        ]);
        $response->resolve();

        self::assertSame(70, $response->getCount());

        $purger = new Purger($this->dynamoDbClient, $this->logger);
        $purger->purge($loader->getFixtures(), []);

        $response = $this->dynamoDbClient->scan([
            'TableName' => 'Users',
        ]);
        $response->resolve();

        self::assertSame(0, $response->getCount());

        $expectedLogs = [
            [
                'level' => 'debug',
                'message' => 'Data batch deleted',
                'context' => [
                    'table' => 'Users',
                    'batch' => '#1',
                ],
            ],
            [
                'level' => 'debug',
                'message' => 'Data batch deleted',
                'context' => [
                    'table' => 'Users',
                    'batch' => '#2',
                ],
            ],
            [
                'level' => 'debug',
                'message' => 'Data batch deleted',
                'context' => [
                    'table' => 'Users',
                    'batch' => '#3',
                ],
            ],
            [
                'level' => 'debug',
                'message' => 'Table data truncated',
                'context' => [
                    'table' => 'Users',
                ],
            ],
        ];

        self::assertSame($expectedLogs, $this->logger->recordsByLevel['debug'] ?? []);
    }
}
