<?php

declare(strict_types=1);

namespace Dynamite\Schema;

use Dynamite\Enum\KeyTypeEnum;
use Dynamite\Enum\ProjectionTypeEnum;
use Dynamite\Enum\ScalarAttributeTypeEnum;
use Dynamite\Exception\SchemaException;
use Dynamite\Validator\Constraints as Assert;

final class Table
{
    #[Assert\TableOrIndexName]
    private ?string $tableName = null;

    /**
     * @var array<int, array{AttributeName: string, AttributeType: ScalarAttributeTypeEnum}>|null
     */
    #[Assert\AttributeDefinitions]
    private ?array $attributeDefinitions = null;

    /**
     * @var array<int, array{AttributeName: string, KeyType: KeyTypeEnum}>|null
     */
    #[Assert\KeySchema]
    private ?array $keySchema = null;

    /**
     * @var array<int, array{
     *     IndexName: string,
     *     KeySchema: array<int, array{
     *          AttributeName: string,
     *          KeyType: KeyTypeEnum
     *     }>
     * }>|null
     */
    #[Assert\LocalSecondaryIndexes]
    private ?array $localSecondaryIndexes = null;

    /**
     * @var array<int, array{
     *     IndexName: string,
     *     KeySchema: array<int, array{
     *          AttributeName: string,
     *          KeyType: KeyTypeEnum
     *     }>,
     *     Projection: array{ProjectionType: ProjectionTypeEnum},
     *     ProvisionedThroughput: array{ReadCapacityUnits: int, WriteCapacityUnits: int}|null
     * }>|null
     */
    #[Assert\GlobalSecondaryIndexes]
    private ?array $globalSecondaryIndexes = null;

    /**
     * @var array{ReadCapacityUnits: int, WriteCapacityUnits: int}|null
     */
    #[Assert\ProvisionedThroughput]
    private ?array $provisionedThroughput = null;

    public function setTableName(string $tableName): void
    {
        $this->tableName = $tableName;
    }

    public function getTableName(): ?string
    {
        return $this->tableName;
    }

    public function addAttribute(string $name, ScalarAttributeTypeEnum $type, ?KeyTypeEnum $keyType = null): void
    {
        if ($this->attributeDefinitions === null) {
            $this->attributeDefinitions = [];
        }

        $this->attributeDefinitions[] = [
            'AttributeName' => $name,
            'AttributeType' => $type,
        ];

        if ($keyType === null) {
            return;
        }

        $this->addKeyElement($name, $keyType);
    }

    /**
     * @return array<int, array{AttributeName: string, AttributeType: ScalarAttributeTypeEnum}>|null
     */
    public function getAttributeDefinitions(): ?array
    {
        return $this->attributeDefinitions;
    }

    /**
     * @return array<int, array{AttributeName: string, KeyType: KeyTypeEnum}>|null
     */
    public function getKeySchema(): ?array
    {
        return $this->keySchema;
    }

    public function addGlobalSecondaryIndex(
        string $name,
        ProjectionTypeEnum $projectionType,
        string $hashAttribute,
        ?string $rangeAttribute,
        ?int $writeCapacity,
        ?int $readCapacity
    ): void {
        if ($this->globalSecondaryIndexes === null) {
            $this->globalSecondaryIndexes = [];
        }

        $keySchema = [
            [
                'AttributeName' => $hashAttribute,
                'KeyType' => KeyTypeEnum::Hash,
            ],
        ];

        if ($rangeAttribute !== null) {
            $keySchema[] = [
                'AttributeName' => $rangeAttribute,
                'KeyType' => KeyTypeEnum::Range,
            ];
        }

        $provisionedThroughput = null;
        if ($readCapacity !== null && $writeCapacity !== null) {
            $provisionedThroughput = [
                'ReadCapacityUnits' => $readCapacity,
                'WriteCapacityUnits' => $writeCapacity,
            ];
        }

        $this->globalSecondaryIndexes[] = [
            'IndexName' => $name,
            'KeySchema' => $keySchema,
            'Projection' => [
                'ProjectionType' => $projectionType,
            ],
            'ProvisionedThroughput' => $provisionedThroughput,
        ];
    }

    /**
     * @return array<int, array{
     *     IndexName: string,
     *     KeySchema: array<int, array{
     *          AttributeName: string,
     *          KeyType: KeyTypeEnum
     *     }>,
     *     Projection: array{ProjectionType: ProjectionTypeEnum},
     *     ProvisionedThroughput: array{ReadCapacityUnits: int, WriteCapacityUnits: int}
     * }>|null
     */
    public function getGlobalSecondaryIndexes(): ?array
    {
        if ($this->globalSecondaryIndexes === null) {
            return $this->globalSecondaryIndexes;
        }

        return $this->normalizeGlobalSecondaryIndexes();
    }

    public function addLocalSecondaryIndex(string $name, string $hashAttribute, ?string $rangeAttribute): void
    {
        if ($this->localSecondaryIndexes === null) {
            $this->localSecondaryIndexes = [];
        }

        $keySchema = [
            [
                'AttributeName' => $hashAttribute,
                'KeyType' => KeyTypeEnum::Hash,
            ],
        ];

        if ($rangeAttribute !== null) {
            $keySchema[] = [
                'AttributeName' => $rangeAttribute,
                'KeyType' => KeyTypeEnum::Range,
            ];
        }

        $this->localSecondaryIndexes[] = [
            'IndexName' => $name,
            'KeySchema' => $keySchema,
        ];
    }

    /**
     * @return array<int, array{
     *     IndexName: string,
     *     KeySchema: array<int, array{
     *          AttributeName: string,
     *          KeyType: KeyTypeEnum
     *     }>
     * }>|null
     */
    public function getLocalSecondaryIndexes(): ?array
    {
        if ($this->localSecondaryIndexes === null) {
            return $this->localSecondaryIndexes;
        }

        $indexes = $this->localSecondaryIndexes;
        array_walk($indexes, function (array $index): void {
            $this->assertKeySchemaAttributesDefined($index['KeySchema']);
        });

        return $this->localSecondaryIndexes;
    }

    public function setProvisionedThroughput(int $writeCapacity, int $readCapacity): void
    {
        $this->provisionedThroughput = [
            'ReadCapacityUnits' => $readCapacity,
            'WriteCapacityUnits' => $writeCapacity,
        ];
    }

    /**
     * @return array{ReadCapacityUnits: int, WriteCapacityUnits: int}|null
     */
    public function getProvisionedThroughput(): ?array
    {
        return $this->provisionedThroughput;
    }

    public function assertHashKeySet(): void
    {
        $hashKeys = [];
        foreach ($this->keySchema ?? [] as $key) {
            if ($key['KeyType'] === KeyTypeEnum::Hash) {
                $hashKeys[] = $key;
            }
        }

        if (\count($hashKeys) < 1) {
            throw SchemaException::hashKeyNotSet();
        }
    }

    private function addKeyElement(string $name, KeyTypeEnum $type): void
    {
        if ($this->keySchema === null) {
            $this->keySchema = [];
        }

        $this->keySchema[] = [
            'AttributeName' => $name,
            'KeyType' => $type,
        ];
    }

    /**
     * @param array<int, array{AttributeName: string, KeyType: KeyTypeEnum}> $keySchema
     */
    private function assertKeySchemaAttributesDefined(array $keySchema): void
    {
        array_walk($keySchema, function (array $element): void {
            foreach ($this->attributeDefinitions ?? [] as $definition) {
                if ($definition['AttributeName'] === $element['AttributeName']) {
                    return;
                }
            }

            throw SchemaException::notDefinedAttribute($element['AttributeName']);
        });
    }

    /**
     * @return array<int, array{
     *     IndexName: string,
     *     KeySchema: array<int, array{
     *          AttributeName: string,
     *          KeyType: KeyTypeEnum
     *     }>,
     *     Projection: array{ProjectionType: ProjectionTypeEnum},
     *     ProvisionedThroughput: array{ReadCapacityUnits: int, WriteCapacityUnits: int}
     * }>
     */
    private function normalizeGlobalSecondaryIndexes(): array
    {
        return array_map(
            function (array $index): array {
                // try to use global provisioned throughput value if index value not set
                if ($index['ProvisionedThroughput'] === null) {
                    if ($this->provisionedThroughput === null) {
                        throw SchemaException::provisionedThroughputNotSet();
                    }

                    $index['ProvisionedThroughput'] = $this->provisionedThroughput;
                }

                $this->assertKeySchemaAttributesDefined($index['KeySchema']);

                return $index;
            },
            $this->globalSecondaryIndexes ?? []
        );
    }
}
