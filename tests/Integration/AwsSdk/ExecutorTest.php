<?php

declare(strict_types=1);

namespace Dynamite\Tests\Integration\AwsSdk;

use Dynamite\Executor;
use Dynamite\Loader;
use Dynamite\Schema\Record;
use Dynamite\Schema\Value;
use Dynamite\Tests\Integration\AwsSdkIntegrationTrait;
use Dynamite\Tests\Integration\IntegrationTestCase;
use PHPUnit\Framework\Attributes\Group;

#[Group('AwsSdk')]
class ExecutorTest extends IntegrationTestCase
{
    use AwsSdkIntegrationTrait;

    public function testExecute(): void
    {
        $table = $this->createFixtureTable();
        $fixture = $this->createFixture([
            new Record([
                Value::stringValue('Id', 'e5502ec2-42a7-408b-9f03-f8e162b6257e'),
                Value::stringValue('Email', 'test.one@example.com'),
            ]),
            new Record([
                Value::stringValue('Id', 'f0cf458c-4fc0-4dd8-ba5b-eca6dba9be63'),
                Value::stringValue('Email', 'test.two@example.com'),
            ]),
        ]);

        $loader = new Loader($this->validator, $this->serializer);
        $loader->addTable($table);
        $loader->addFixture($fixture);

        $executor = new Executor($this->client, logger: $this->logger);
        $executor->execute($loader->getFixtures(), $loader->getTables());

        $response = $this->dynamoDbClient->describeTable([
            'TableName' => self::TABLE_NAME,
        ]);

        self::assertSame(self::TABLE_NAME, $response['Table']['TableName']);

        $response = $this->dynamoDbClient->scan([
            'TableName' => self::TABLE_NAME,
        ]);

        self::assertSame(2, $response['Count']);
    }
}
