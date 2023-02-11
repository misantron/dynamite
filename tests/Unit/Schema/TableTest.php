<?php

declare(strict_types=1);

namespace Dynamite\Tests\Unit\Schema;

use Dynamite\Enum\KeyTypeEnum;
use Dynamite\Enum\ProjectionTypeEnum;
use Dynamite\Enum\ScalarAttributeTypeEnum;
use Dynamite\Exception\SchemaException;
use Dynamite\Schema\Table;
use Dynamite\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Constraint\Constraint;
use PHPUnit\Framework\Constraint\IsEqual;
use PHPUnit\Framework\Constraint\IsNull;

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

    public function testAddGlobalSecondaryIndex(): void
    {
        $schema = new Table();
        $schema->addAttribute('Id', ScalarAttributeTypeEnum::String);
        $schema->addAttribute('Type', ScalarAttributeTypeEnum::String, KeyTypeEnum::Hash);
        $schema->addAttribute('Email', ScalarAttributeTypeEnum::String, KeyTypeEnum::Range);
        $schema->addGlobalSecondaryIndex(
            'Index',
            ProjectionTypeEnum::KeysOnly,
            'Type',
            'Email',
            1,
            1
        );

        $indexes = $schema->getGlobalSecondaryIndexes();

        self::assertSame([
            [
                'IndexName' => 'Index',
                'KeySchema' => [
                    [
                        'AttributeName' => 'Type',
                        'KeyType' => KeyTypeEnum::Hash->value,
                    ],
                    [
                        'AttributeName' => 'Email',
                        'KeyType' => KeyTypeEnum::Range->value,
                    ],
                ],
                'Projection' => [
                    'ProjectionType' => ProjectionTypeEnum::KeysOnly->value,
                ],
                'ProvisionedThroughput' => [
                    'ReadCapacityUnits' => 1,
                    'WriteCapacityUnits' => 1,
                ],
            ]
        ], $indexes);
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

    public function testAddLocalSecondaryIndex(): void
    {
        $schema = new Table();
        $schema->setProvisionedThroughput(1, 1);
        $schema->addAttribute('Id', ScalarAttributeTypeEnum::String);
        $schema->addAttribute('Type', ScalarAttributeTypeEnum::String);
        $schema->addAttribute('Email', ScalarAttributeTypeEnum::String);
        $schema->addLocalSecondaryIndex(
            'Index',
            'Type',
            'Email'
        );

        self::assertSame([
            [
                'IndexName' => 'Index',
                'KeySchema' => [
                    [
                        'AttributeName' => 'Type',
                        'KeyType' => KeyTypeEnum::Hash->value,
                    ],
                    [
                        'AttributeName' => 'Email',
                        'KeyType' => KeyTypeEnum::Range->value,
                    ],
                ],
            ]
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

    #[DataProvider('setProvisionedThroughputDataProvider')]
    public function testSetProvisionedThroughput(
        ?int $writeCapacity,
        ?int $readCapacity,
        Constraint $expected
    ): void {
        $schema = new Table();
        if ($writeCapacity !== null && $readCapacity !== null) {
            $schema->setProvisionedThroughput($writeCapacity, $readCapacity);
        }

        self::assertThat($schema->getProvisionedThroughput(), $expected);
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
