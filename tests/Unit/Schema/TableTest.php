<?php

declare(strict_types=1);

namespace Dynamite\Tests\Unit\Schema;

use Dynamite\Enum\KeyType;
use Dynamite\Enum\ProjectionType;
use Dynamite\Enum\ScalarAttributeType;
use Dynamite\Exception\SchemaException;
use Dynamite\Schema\Table;
use Dynamite\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Constraint\Constraint;
use PHPUnit\Framework\Constraint\IsEqual;
use PHPUnit\Framework\Constraint\IsNull;

#[Group('unit')]
class TableTest extends UnitTestCase
{
    public function testAddGlobalSecondaryIndexWithoutProvisionedThroughput(): void
    {
        $this->expectException(SchemaException::class);
        $this->expectExceptionMessage('Table provisioned throughput not set');

        $schema = new Table();
        $schema->addGlobalSecondaryIndex(
            'Index',
            ProjectionType::KeysOnly,
            'Id',
            null,
            null,
            null,
        );
        $schema->getGlobalSecondaryIndexes();
    }

    public function testAddGlobalSecondaryIndexWithInvalidKeySchemaAttribute(): void
    {
        $this->expectException(SchemaException::class);
        $this->expectExceptionMessage('Attribute `Email` is not defined');

        $schema = new Table();
        $schema->setProvisionedThroughput(1, 1);
        $schema->addAttribute('Id', ScalarAttributeType::String);
        $schema->addGlobalSecondaryIndex(
            'Index',
            ProjectionType::KeysOnly,
            'Email',
            null,
            null,
            null,
        );
        $schema->getGlobalSecondaryIndexes();
    }

    public function testAddGlobalSecondaryIndex(): void
    {
        $schema = new Table();
        $schema->addAttribute('Id', ScalarAttributeType::String);
        $schema->addAttribute('Type', ScalarAttributeType::String, KeyType::Hash);
        $schema->addAttribute('Email', ScalarAttributeType::String, KeyType::Range);
        $schema->addGlobalSecondaryIndex(
            'Index',
            ProjectionType::KeysOnly,
            'Type',
            'Email',
            1,
            1,
        );

        $indexes = $schema->getGlobalSecondaryIndexes();

        $this->assertSame([
            [
                'IndexName' => 'Index',
                'KeySchema' => [
                    [
                        'AttributeName' => 'Type',
                        'KeyType' => KeyType::Hash,
                    ],
                    [
                        'AttributeName' => 'Email',
                        'KeyType' => KeyType::Range,
                    ],
                ],
                'Projection' => [
                    'ProjectionType' => ProjectionType::KeysOnly,
                ],
                'ProvisionedThroughput' => [
                    'ReadCapacityUnits' => 1,
                    'WriteCapacityUnits' => 1,
                ],
            ],
        ], $indexes);
    }

    public function testAddLocalSecondaryIndexWithInvalidKeySchemaAttribute(): void
    {
        $this->expectException(SchemaException::class);
        $this->expectExceptionMessage('Attribute `Email` is not defined');

        $schema = new Table();
        $schema->setProvisionedThroughput(1, 1);
        $schema->addAttribute('Id', ScalarAttributeType::String);
        $schema->addLocalSecondaryIndex('Index', ProjectionType::KeysOnly, 'Email', null);
        $schema->getLocalSecondaryIndexes();
    }

    public function testAddLocalSecondaryIndex(): void
    {
        $schema = new Table();
        $schema->setProvisionedThroughput(1, 1);
        $schema->addAttribute('Id', ScalarAttributeType::String);
        $schema->addAttribute('Type', ScalarAttributeType::String);
        $schema->addAttribute('Email', ScalarAttributeType::String);
        $schema->addLocalSecondaryIndex(
            'Index',
            ProjectionType::KeysOnly,
            'Type',
            'Email',
        );

        $this->assertSame([
            [
                'IndexName' => 'Index',
                'KeySchema' => [
                    [
                        'AttributeName' => 'Type',
                        'KeyType' => KeyType::Hash,
                    ],
                    [
                        'AttributeName' => 'Email',
                        'KeyType' => KeyType::Range,
                    ],
                ],
                'Projection' => [
                    'ProjectionType' => ProjectionType::KeysOnly,
                ],
            ],
        ], $schema->getLocalSecondaryIndexes());
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
        $schema->addAttribute('Id', ScalarAttributeType::String, KeyType::Hash);
        $schema->assertHashKeySet();

        $this->expectNotToPerformAssertions();
    }

    public function testAddAttribute(): void
    {
        $schema = new Table();
        $schema->addAttribute('Id', ScalarAttributeType::String, KeyType::Hash);
        $schema->addAttribute('Active', ScalarAttributeType::Bool);

        $expected = [
            [
                'AttributeName' => 'Id',
                'AttributeType' => ScalarAttributeType::String,
            ],
            [
                'AttributeName' => 'Active',
                'AttributeType' => ScalarAttributeType::Bool,
            ],
        ];

        $this->assertSame($expected, $schema->getAttributeDefinitions());

        $expected = [
            [
                'AttributeName' => 'Id',
                'KeyType' => KeyType::Hash,
            ],
        ];

        $this->assertSame($expected, $schema->getKeySchema());
    }

    #[DataProvider('setProvisionedThroughputDataProvider')]
    public function testSetProvisionedThroughput(
        ?int $writeCapacity,
        ?int $readCapacity,
        Constraint $expected,
    ): void {
        $schema = new Table();
        if ($writeCapacity !== null && $readCapacity !== null) {
            $schema->setProvisionedThroughput($writeCapacity, $readCapacity);
        }

        $this->assertThat($schema->getProvisionedThroughput(), $expected);
    }

    /**
     * @return iterable<string, array{writeCapacity: ?int, readCapacity: ?int, expected: Constraint}>
     */
    public static function setProvisionedThroughputDataProvider(): iterable
    {
        yield 'value_not_set' => [
            'writeCapacity' => null,
            'readCapacity' => null,
            'expected' => new IsNull(),
        ];
        yield 'value_set' => [
            'writeCapacity' => 5,
            'readCapacity' => 10,
            'expected' => new IsEqual([
                'ReadCapacityUnits' => 10,
                'WriteCapacityUnits' => 5,
            ]),
        ];
    }
}
