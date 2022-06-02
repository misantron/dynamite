<?php

declare(strict_types=1);

namespace Dynamite\Tests\Unit\Schema;

use AsyncAws\DynamoDb\Enum\KeyType;
use AsyncAws\DynamoDb\Enum\ProjectionType;
use AsyncAws\DynamoDb\Enum\ScalarAttributeType;
use Dynamite\Exception\SchemaException;
use Dynamite\Schema\Table;
use Dynamite\Tests\Unit\UnitTestCase;

class TableTest extends UnitTestCase
{
    public function testAddGlobalSecondaryIndexWithoutProvisionedThroughput(): void
    {
        $this->expectException(SchemaException::class);
        $this->expectExceptionMessage('Table provisioned throughput not set');

        $schema = new Table();
        $schema->addGlobalSecondaryIndex(
            'Index',
            ProjectionType::KEYS_ONLY,
            'Id',
            null,
            null,
            null
        );
        $schema->getGlobalSecondaryIndexes();
    }

    public function testAddGlobalSecondaryIndexWithInvalidKeySchemaAttribute(): void
    {
        $this->expectException(SchemaException::class);
        $this->expectExceptionMessage('Attribute `Email` is not defined');

        $schema = new Table();
        $schema->setProvisionedThroughput(1, 1);
        $schema->addAttribute('Id', ScalarAttributeType::S);
        $schema->addGlobalSecondaryIndex(
            'Index',
            ProjectionType::KEYS_ONLY,
            'Email',
            null,
            null,
            null
        );
        $schema->getGlobalSecondaryIndexes();
    }

    public function testAddLocalSecondaryIndexWithInvalidKeySchemaAttribute(): void
    {
        $this->expectException(SchemaException::class);
        $this->expectExceptionMessage('Attribute `Email` is not defined');

        $schema = new Table();
        $schema->setProvisionedThroughput(1, 1);
        $schema->addAttribute('Id', ScalarAttributeType::S);
        $schema->addLocalSecondaryIndex('Index', 'Email', null);
        $schema->getLocalSecondaryIndexes();
    }

    public function testAddKeyElementWithInvalidAttribute(): void
    {
        $this->expectException(SchemaException::class);
        $this->expectExceptionMessage('Attribute `Email` is not defined');

        $schema = new Table();
        $schema->setProvisionedThroughput(1, 1);
        $schema->addAttribute('Id', ScalarAttributeType::S);
        $schema->addKeyElement('Email', KeyType::HASH);
        $schema->getKeySchema();
    }

    public function testAssertHashKeySet(): void
    {
        $this->expectException(SchemaException::class);
        $this->expectExceptionMessage('Table key require at least one hash attribute');

        $schema = new Table();
        $schema->setProvisionedThroughput(1, 1);
        $schema->assertHashKeySet();
    }

    public function testAddAttribute(): void
    {
        $schema = new Table();
        $schema->addAttribute('Id', ScalarAttributeType::S);
        $schema->addAttribute('Active', ScalarAttributeType::B);

        $expected = [
            [
                'AttributeName' => 'Id',
                'AttributeType' => ScalarAttributeType::S,
            ],
            [
                'AttributeName' => 'Active',
                'AttributeType' => ScalarAttributeType::B,
            ],
        ];

        self::assertSame($expected, $schema->getAttributeDefinitions());
    }
}
