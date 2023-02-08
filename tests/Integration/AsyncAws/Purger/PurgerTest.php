<?php

declare(strict_types=1);

namespace Dynamite\Tests\Integration\AsyncAws\Purger;

use AsyncAws\DynamoDb\Exception\ResourceNotFoundException;
use Dynamite\AbstractFixture;
use Dynamite\AbstractTable;
use Dynamite\Enum\KeyTypeEnum;
use Dynamite\Enum\ScalarAttributeTypeEnum;
use Dynamite\Executor;
use Dynamite\FixtureInterface;
use Dynamite\Loader;
use Dynamite\Purger\Purger;
use Dynamite\Schema\Attribute;
use Dynamite\TableInterface;
use Dynamite\Tests\Integration\AsyncAwsIntegrationTrait;
use Dynamite\Tests\Integration\IntegrationTestCase;
use Faker\Factory;

class PurgerTest extends IntegrationTestCase
{
    use AsyncAwsIntegrationTrait;

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
                        new Attribute('Id', ScalarAttributeTypeEnum::String, KeyTypeEnum::Hash),
                        new Attribute('Email', ScalarAttributeTypeEnum::String, KeyTypeEnum::Range),
                    ])
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

        $purger = new Purger($this->client);
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

        $this->logger->cleanLogs();
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

        $executor = new Executor($this->client);
        $executor->execute($fixtures, []);

        $response = $this->dynamoDbClient->scan([
            'TableName' => 'Users',
        ]);
        $response->resolve();

        self::assertSame(2, $response->getCount());

        $purger = new Purger($this->client);
        $purger->purge($fixtures, []);

        $response = $this->dynamoDbClient->scan([
            'TableName' => 'Users',
        ]);
        $response->resolve();

        self::assertSame(0, $response->getCount());

        $this->logger->cleanLogs();
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
                            'S' => $faker->uuid(),
                        ],
                        'Email' => [
                            'S' => $faker->email(),
                        ],
                    ]);
                }
            }
        };

        $loader = new Loader($this->validator, $this->serializer);
        $loader->addFixture($fixture);

        $executor = new Executor($this->client);
        $executor->execute($loader->getFixtures(), []);

        $response = $this->dynamoDbClient->scan([
            'TableName' => 'Users',
        ]);
        $response->resolve();

        self::assertSame(70, $response->getCount());

        $purger = new Purger($this->client);
        $purger->purge($loader->getFixtures(), []);

        $response = $this->dynamoDbClient->scan([
            'TableName' => 'Users',
        ]);
        $response->resolve();

        self::assertSame(0, $response->getCount());

        $expectedLogs = [
            [
                'debug',
                'Table data truncate skipped - no data',
                [
                    'table' => 'Users',
                ],
            ],
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
                'Data batch executed',
                [
                    'table' => 'Users',
                    'batch' => '#2',
                ],
            ],
            [
                'debug',
                'Data batch executed',
                [
                    'table' => 'Users',
                    'batch' => '#3',
                ],
            ],
            [
                'debug',
                'Data batch deleted',
                [
                    'table' => 'Users',
                    'batch' => '#1',
                ],
            ],
            [
                'debug',
                'Data batch deleted',
                [
                    'table' => 'Users',
                    'batch' => '#2',
                ],
            ],
            [
                'debug',
                'Data batch deleted',
                [
                    'table' => 'Users',
                    'batch' => '#3',
                ],
            ],
            [
                'debug',
                'Table data truncated',
                [
                    'table' => 'Users',
                ],
            ],
        ];

        self::assertSame($expectedLogs, $this->logger->cleanLogs());
    }
}
