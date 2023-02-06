<?php

declare(strict_types=1);

namespace Dynamite\Tests\Unit\Schema;

use Dynamite\Enum\KeyTypeEnum;
use Dynamite\Enum\ProjectionTypeEnum;
use Dynamite\Enum\ScalarAttributeTypeEnum;
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
            ProjectionTypeEnum::KeysOnly,
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
        $schema->addAttribute('Id', ScalarAttributeTypeEnum::String);
        $schema->addGlobalSecondaryIndex(
            'Index',
            ProjectionTypeEnum::KeysOnly,
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
        $schema->addAttribute('Id', ScalarAttributeTypeEnum::String);
        $schema->addLocalSecondaryIndex('Index', 'Email', null);
        $schema->getLocalSecondaryIndexes();
    }

    public function testAssertHashKeySetWithoutHashKeyDefinition(): void
    {
        $this->expectException(SchemaException::class);
        $this->expectExceptionMessage('Table key require at least one hash attribute');

        $schema = new Table();
        $schema->setProvisionedThroughput(1, 1);
        $schema->assertHashKeySet();
    }

    public function testAssertHashKeySet(): void
    {
        $schema = new Table();
        $schema->addAttribute('Id', ScalarAttributeTypeEnum::String, KeyTypeEnum::Hash);
        $schema->assertHashKeySet();

        $this->expectNotToPerformAssertions();
    }

    public function testAddAttribute(): void
    {
        $schema = new Table();
        $schema->addAttribute('Id', ScalarAttributeTypeEnum::String, KeyTypeEnum::Hash);
        $schema->addAttribute('Active', ScalarAttributeTypeEnum::Binary);

        $expected = [
            [
                'AttributeName' => 'Id',
                'AttributeType' => ScalarAttributeTypeEnum::String->value,
            ],
            [
                'AttributeName' => 'Active',
                'AttributeType' => ScalarAttributeTypeEnum::Binary->value,
            ],
        ];

        self::assertSame($expected, $schema->getAttributeDefinitions());

        $expected = [
            [
                'AttributeName' => 'Id',
                'KeyType' => KeyTypeEnum::Hash->value,
            ],
        ];

        self::assertSame($expected, $schema->getKeySchema());
    }
}
