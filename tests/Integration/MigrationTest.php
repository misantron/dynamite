<?php

declare(strict_types=1);

namespace Dynamite\Tests\Integration;

use AsyncAws\DynamoDb\Exception\ResourceNotFoundException;
use Dynamite\AbstractMigration;

class MigrationTest extends IntegrationTestCase
{
    public function testCreateTable(): void
    {
        $response = $this->dynamoDbClient->tableExists([
            'TableName' => 'Users',
        ]);
        $response->resolve();

        self::assertTrue($response->isSuccess());
    }

    public function testUpdateTable(): void
    {
        $migration = new class($this->dynamoDbClient, $this->serializer, $this->validator) extends AbstractMigration {
            public function up(): void
            {
                $this
                    ->setTableName('Users')
                    ->setProvisionedThroughput(5, 5)
                    ->update()
                ;
            }
        };
        $migration->up();

        $response = $this->dynamoDbClient->describeTable([
            'TableName' => 'Users',
        ]);
        $response->resolve();

        self::assertSame('5', $response->getTable()->getProvisionedThroughput()->getReadCapacityUnits());
        self::assertSame('5', $response->getTable()->getProvisionedThroughput()->getWriteCapacityUnits());
    }

    public function testDeleteTable(): void
    {
        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('Cannot do operations on a non-existent table');

        $migration = new class($this->dynamoDbClient, $this->serializer, $this->validator) extends AbstractMigration {
            public function up(): void
            {
                $this
                    ->setTableName('Users')
                    ->delete()
                ;
            }
        };
        $migration->up();

        $this->dynamoDbClient->describeTable([
            'TableName' => 'Users',
        ])->resolve();
    }
}
