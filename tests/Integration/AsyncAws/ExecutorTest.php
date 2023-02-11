<?php

declare(strict_types=1);

namespace Dynamite\Tests\Integration\AsyncAws;

use Dynamite\Executor;
use Dynamite\Loader;
use Dynamite\Tests\Integration\AsyncAwsIntegrationTrait;
use Dynamite\Tests\Integration\IntegrationTestCase;
use PHPUnit\Framework\Attributes\Group;

#[Group('AsyncAws')]
class ExecutorTest extends IntegrationTestCase
{
    use AsyncAwsIntegrationTrait;

    public function testExecute(): void
    {
        $table = $this->createFixtureTable();
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

        $loader = new Loader($this->validator, $this->serializer);
        $loader->addTable($table);
        $loader->addFixture($fixture);

        $executor = new Executor($this->client, logger: $this->logger);
        $executor->execute($loader->getFixtures(), $loader->getTables());

        $response = $this->dynamoDbClient->describeTable([
            'TableName' => self::TABLE_NAME,
        ]);
        $response->resolve();

        self::assertSame(self::TABLE_NAME, $response->getTable()?->getTableName());

        $response = $this->dynamoDbClient->scan([
            'TableName' => self::TABLE_NAME,
        ]);
        $response->resolve();

        self::assertSame(2, $response->getCount());
    }
}
