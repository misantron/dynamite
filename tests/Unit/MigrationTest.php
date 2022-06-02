<?php

declare(strict_types=1);

namespace Dynamite\Tests\Unit;

use AsyncAws\DynamoDb\DynamoDbClient;
use Dynamite\AbstractMigration;
use Dynamite\Exception\SchemaException;

class MigrationTest extends UnitTestCase
{
    public function testAddAttributeWithUnexpectedType(): void
    {
        $this->expectException(SchemaException::class);
        $this->expectExceptionMessage('Unexpected attribute type `U` provided');

        $validator = $this->createValidator();
        $serializer = $this->createSerializer();
        $dynamoDbClient = $this->createMock(DynamoDbClient::class);

        $migration = new class($dynamoDbClient, $serializer, $validator) extends AbstractMigration {
            public function up(): void
            {
                $this
                    ->setTableName('Users')
                    ->addAttribute('Id', 'U')
                    ->create()
                ;
            }
        };
        $migration->up();
    }

    public function testAddGlobalSecondaryIndexWithUnexpectedProjectionType(): void
    {
        $this->expectException(SchemaException::class);
        $this->expectExceptionMessage('Unexpected projection type `EXCLUDE` provided');

        $validator = $this->createValidator();
        $serializer = $this->createSerializer();
        $dynamoDbClient = $this->createMock(DynamoDbClient::class);

        $migration = new class($dynamoDbClient, $serializer, $validator) extends AbstractMigration {
            public function up(): void
            {
                $this
                    ->setTableName('Users')
                    ->addGlobalSecondaryIndex(
                        'Index',
                        'EXCLUDE',
                        'Id'
                    )
                    ->create()
                ;
            }
        };
        $migration->up();
    }
}
