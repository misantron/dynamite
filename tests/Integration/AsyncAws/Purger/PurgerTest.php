<?php

declare(strict_types=1);

namespace Dynamite\Tests\Integration\AsyncAws\Purger;

use AsyncAws\DynamoDb\Exception\ResourceNotFoundException;
use Dynamite\Executor;
use Dynamite\Loader;
use Dynamite\Purger\Purger;
use Dynamite\Tests\Integration\AsyncAwsIntegrationTrait;
use Dynamite\Tests\Integration\IntegrationTestCase;
use PHPUnit\Framework\Attributes\Group;

#[Group('AsyncAws')]
class PurgerTest extends IntegrationTestCase
{
    use AsyncAwsIntegrationTrait;

    public function testPurge(): void
    {
        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('Cannot do operations on a non-existent table');

        $this->createTable();

        $table = $this->createFixtureTable();
        $table->setValidator($this->validator);
        $table->setNormalizer($this->serializer);

        $fixture = $this->createFixture([
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
        ]);
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
            'TableName' => self::TABLE_NAME,
        ]);
        $response->resolve();
    }

    public function testPurgeOnlyFixtures(): void
    {
        $this->createTable();

        $fixture = $this->createFixture([
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
        ]);
        $fixture->setValidator($this->validator);

        $fixtures = [
            $fixture::class => $fixture,
        ];

        $executor = new Executor($this->client);
        $executor->execute($fixtures, []);

        $response = $this->dynamoDbClient->scan([
            'TableName' => self::TABLE_NAME,
        ]);
        $response->resolve();

        self::assertSame(2, $response->getCount());

        $purger = new Purger($this->client);
        $purger->purge($fixtures, []);

        $response = $this->dynamoDbClient->scan([
            'TableName' => self::TABLE_NAME,
        ]);
        $response->resolve();

        self::assertSame(0, $response->getCount());
    }

    public function testPurgeLargeFixturesBatch(): void
    {
        $this->createTable();

        $faker = $this->createFakerFactory();

        $rows = [];
        $i = 0;
        do {
            $rows[] = [
                'Id' => [
                    'S' => $faker->uuid(),
                ],
                'Email' => [
                    'S' => $faker->email(),
                ],
            ];
        } while (++$i < 70);

        $fixture = $this->createFixture($rows);

        $loader = new Loader($this->validator, $this->serializer);
        $loader->addFixture($fixture);

        $executor = new Executor($this->client);
        $executor->execute($loader->getFixtures(), []);

        $response = $this->dynamoDbClient->scan([
            'TableName' => self::TABLE_NAME,
        ]);
        $response->resolve();

        self::assertSame(70, $response->getCount());

        $purger = new Purger($this->client);
        $purger->purge($loader->getFixtures(), []);

        $response = $this->dynamoDbClient->scan([
            'TableName' => self::TABLE_NAME,
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
