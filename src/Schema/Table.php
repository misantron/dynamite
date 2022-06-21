<?php

declare(strict_types=1);

namespace Dynamite\Schema;

use AsyncAws\DynamoDb\Enum\KeyType;
use Dynamite\Exception\SchemaException;
use Dynamite\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

final class Table
{
    #[
        Groups(['create', 'update', 'delete']),
        SerializedName('TableName'),
        Assert\TableOrIndexName
    ]
    private ?string $tableName = null;

    #[
        Groups(['create', 'update']),
        SerializedName('AttributeDefinitions'),
        Assert\AttributeDefinitions
    ]
    private ?array $attributeDefinitions = null;

    #[
        Groups(['create']),
        SerializedName('KeySchema'),
        Assert\KeySchema
    ]
    private ?array $keySchema = null;

    #[
        Groups(['create']),
        SerializedName('LocalSecondaryIndexes'),
        Assert\LocalSecondaryIndexes
    ]
    private ?array $localSecondaryIndexes = null;

    #[
        Groups(['create']),
        SerializedName('GlobalSecondaryIndexes'),
        Assert\GlobalSecondaryIndexes
    ]
    private ?array $globalSecondaryIndexes = null;

    #[
        Groups(['update']),
        SerializedName('GlobalSecondaryIndexUpdates'),
        Assert\GlobalSecondaryIndexUpdates
    ]
    private ?array $globalSecondaryIndexUpdates = null;

    #[
        Groups(['create', 'update']),
        SerializedName('ProvisionedThroughput'),
        Assert\ProvisionedThroughput(['groups' => ['create']])
    ]
    private ?array $provisionedThroughput = null;

    public function setTableName(string $tableName): void
    {
        $this->tableName = $tableName;
    }

    public function getTableName(): string
    {
        return $this->tableName;
    }

    public function addAttribute(string $name, string $type): void
    {
        if ($this->attributeDefinitions === null) {
            $this->attributeDefinitions = [];
        }

        $this->attributeDefinitions[] = [
            'AttributeName' => $name,
            'AttributeType' => $type,
        ];
    }

    public function getAttributeDefinitions(): ?array
    {
        return $this->attributeDefinitions;
    }

    public function addKeyElement(string $name, string $type): self
    {
        if ($this->keySchema === null) {
            $this->keySchema = [];
        }

        $this->keySchema[] = [
            'AttributeName' => $name,
            'KeyType' => $type,
        ];

        return $this;
    }

    public function getKeySchema(): ?array
    {
        $this->assertKeySchemaAttributesDefined($this->keySchema ?? []);

        return $this->keySchema;
    }

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

    public function addGlobalSecondaryIndex(
        string $name,
        string $projectionType,
        string $hashAttribute,
        ?string $rangeAttribute,
        ?int $writeCapacity,
        ?int $readCapacity
    ): void {
        if ($this->globalSecondaryIndexes === null) {
            $this->globalSecondaryIndexes = [];
        }

        $this->globalSecondaryIndexes[] = $this->buildGlobalSecondaryIndex(
            $name,
            $projectionType,
            $hashAttribute,
            $rangeAttribute,
            $writeCapacity,
            $readCapacity
        );
    }

    public function getGlobalSecondaryIndexes(): ?array
    {
        if ($this->globalSecondaryIndexes === null) {
            return $this->globalSecondaryIndexes;
        }

        return $this->normalizeGlobalSecondaryIndexes();
    }

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
            $this->globalSecondaryIndexes
        );
    }

    public function createGlobalSecondaryIndex(
        string $name,
        string $projectionType,
        string $hashAttribute,
        ?string $rangeAttribute,
        ?int $writeCapacity,
        ?int $readCapacity
    ): void {
        if ($this->globalSecondaryIndexUpdates === null) {
            $this->globalSecondaryIndexUpdates = [];
        }

        $this->globalSecondaryIndexUpdates['Create'] = $this->buildGlobalSecondaryIndex(
            $name,
            $projectionType,
            $hashAttribute,
            $rangeAttribute,
            $writeCapacity,
            $readCapacity
        );
    }

    private function buildGlobalSecondaryIndex(
        string $name,
        string $projectionType,
        string $hashAttribute,
        ?string $rangeAttribute,
        ?int $writeCapacity,
        ?int $readCapacity
    ): array {
        $keySchema = [
            [
                'AttributeName' => $hashAttribute,
                'KeyType' => KeyType::HASH,
            ],
        ];

        if ($rangeAttribute !== null) {
            $keySchema[] = [
                'AttributeName' => $rangeAttribute,
                'KeyType' => KeyType::RANGE,
            ];
        }

        $provisionedThroughput = null;
        if ($readCapacity !== null && $writeCapacity !== null) {
            $provisionedThroughput = [
                'ReadCapacityUnits' => $readCapacity,
                'WriteCapacityUnits' => $writeCapacity,
            ];
        }

        return [
            'IndexName' => $name,
            'KeySchema' => $keySchema,
            'Projection' => [
                'ProjectionType' => $projectionType,
            ],
            'ProvisionedThroughput' => $provisionedThroughput,
        ];
    }

    public function updateGlobalSecondaryIndex(string $name, ?int $writeCapacity, ?int $readCapacity): void
    {
        if ($this->globalSecondaryIndexUpdates === null) {
            $this->globalSecondaryIndexUpdates = [];
        }

        $provisionedThroughput = null;
        if ($readCapacity !== null && $writeCapacity !== null) {
            $provisionedThroughput = [
                'ReadCapacityUnits' => $readCapacity,
                'WriteCapacityUnits' => $writeCapacity,
            ];
        }

        $this->globalSecondaryIndexUpdates['Update'] = [
            'IndexName' => $name,
            'ProvisionedThroughput' => $provisionedThroughput,
        ];
    }

    public function deleteGlobalSecondaryIndex(string $name): void
    {
        if ($this->globalSecondaryIndexUpdates === null) {
            $this->globalSecondaryIndexUpdates = [];
        }

        $this->globalSecondaryIndexUpdates['Delete'] = [
            'IndexName' => $name,
        ];
    }

    public function getGlobalSecondaryIndexUpdates(): ?array
    {
        return $this->globalSecondaryIndexUpdates;
    }

    public function addLocalSecondaryIndex(string $name, string $hashAttribute, ?string $rangeAttribute): void
    {
        if ($this->localSecondaryIndexes === null) {
            $this->localSecondaryIndexes = [];
        }

        $keySchema = [
            [
                'AttributeName' => $hashAttribute,
                'KeyType' => KeyType::HASH,
            ],
        ];

        if ($rangeAttribute !== null) {
            $keySchema[] = [
                'AttributeName' => $rangeAttribute,
                'KeyType' => KeyType::RANGE,
            ];
        }

        $this->localSecondaryIndexes[] = [
            'IndexName' => $name,
            'KeySchema' => $keySchema,
        ];
    }

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

    public function getProvisionedThroughput(): ?array
    {
        return $this->provisionedThroughput;
    }

    public function assertHashKeySet(): void
    {
        $hashKeys = [];
        foreach ($this->keySchema ?? [] as $key) {
            if ($key['KeyType'] === KeyType::HASH) {
                $hashKeys[] = $key;
            }
        }

        if (\count($hashKeys) < 1) {
            throw SchemaException::hashKeyNotSet();
        }
    }

    public function getSerializationContext(string $group): array
    {
        return [
            'groups' => $group,
            AbstractObjectNormalizer::SKIP_NULL_VALUES => true,
        ];
    }
}
