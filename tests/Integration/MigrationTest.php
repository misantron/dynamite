<?php

declare(strict_types=1);

namespace Dynamite\Tests\Integration;

use AsyncAws\DynamoDb\Exception\ResourceNotFoundException;
use Dynamite\Tests\Integration\Mock\CreateTableMigration;
use Dynamite\Tests\Integration\Mock\DeleteTableMigration;
use Dynamite\Tests\Integration\Mock\UpdateTableMigration;

class MigrationTest extends IntegrationTestCase
{
    protected function tearDown(): void
    {
        try {
            $this->dynamoDbClient->deleteTable(['TableName' => 'Users'])->resolve();
        } catch (ResourceNotFoundException) {

        }
    }

    public function testCreateTable(): void
    {
        $migration = new CreateTableMigration($this->dynamoDbClient, $this->serializer, $this->validator);
        $migration->up();

        $response = $this->dynamoDbClient->tableExists(['TableName' => 'Users']);
        $response->resolve();

        self::assertTrue($response->isSuccess());
    }

    public function testUpdateTable(): void
    {
        $migration = new CreateTableMigration($this->dynamoDbClient, $this->serializer, $this->validator);
        $migration->up();

        $response = $this->dynamoDbClient->tableExists(['TableName' => 'Users']);
        $response->resolve();

        $migration = new UpdateTableMigration($this->dynamoDbClient, $this->serializer, $this->validator);
        $migration->up();

        $response = $this->dynamoDbClient->describeTable(['TableName' => 'Users']);
        $response->resolve();

        self::assertSame('5', $response->getTable()->getProvisionedThroughput()->getReadCapacityUnits());
        self::assertSame('5', $response->getTable()->getProvisionedThroughput()->getWriteCapacityUnits());
    }

    public function testDeleteTable(): void
    {
        $this->expectException(ResourceNotFoundException::class);

        $migration = new CreateTableMigration($this->dynamoDbClient, $this->serializer, $this->validator);
        $migration->up();

        $response = $this->dynamoDbClient->tableExists(['TableName' => 'Users']);
        $response->resolve();

        $migration = new DeleteTableMigration($this->dynamoDbClient, $this->serializer, $this->validator);
        $migration->up();

        $this->dynamoDbClient->describeTable(['TableName' => 'Users'])->resolve();
    }
}
