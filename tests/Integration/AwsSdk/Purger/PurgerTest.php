<?php

declare(strict_types=1);

namespace Dynamite\Tests\Integration\AwsSdk\Purger;

use Aws\DynamoDb\Exception\DynamoDbException;
use Dynamite\Client\AwsSdkClient;
use Dynamite\Executor;
use Dynamite\Loader;
use Dynamite\Purger\Purger;
use Dynamite\Tests\Integration\AwsSdkIntegrationTrait;
use Dynamite\Tests\Integration\IntegrationTestCase;
use PHPUnit\Framework\Attributes\Group;

#[Group('AwsSdk')]
class PurgerTest extends IntegrationTestCase
{
    use AwsSdkIntegrationTrait;

    public function testPurge(): void
    {
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

        try {
            $this->dynamoDbClient->describeTable([
                'TableName' => self::TABLE_NAME,
            ]);
        } catch (DynamoDbException $e) {
            self::assertSame(AwsSdkClient::RESOURCE_NOT_FOUND_ERROR_CODE, $e->getAwsErrorCode());
            self::assertSame('Cannot do operations on a non-existent table', $e->getAwsErrorMessage());

            return;
        }

        self::fail('Exception is not thrown');
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

        self::assertSame(2, $response['Count']);

        $purger = new Purger($this->client);
        $purger->purge($fixtures, []);

        $response = $this->dynamoDbClient->scan([
            'TableName' => self::TABLE_NAME,
        ]);

        self::assertSame(0, $response['Count']);
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

        self::assertSame(70, $response['Count']);

        $purger = new Purger($this->client);
        $purger->purge($loader->getFixtures(), []);

        $response = $this->dynamoDbClient->scan([
            'TableName' => self::TABLE_NAME,
        ]);

        self::assertSame(0, $response['Count']);

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
