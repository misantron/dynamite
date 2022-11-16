<?php

declare(strict_types=1);

namespace Dynamite\Schema;

use AsyncAws\DynamoDb\Enum\KeyType;
use Dynamite\Exception\SchemaException;
use Dynamite\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\SerializedName;

final class Table
{
    #[
        SerializedName('TableName'),
        Assert\TableOrIndexName
    ]
    private ?string $tableName = null;

    /**
     * @var array<int, array{AttributeName: string, AttributeType: string}>|null
     */
    #[
        SerializedName('AttributeDefinitions'),
        Assert\AttributeDefinitions
    ]
    private ?array $attributeDefinitions = null;

    /**
     * @var array<int, array{AttributeName: string, KeyType: string}>|null
     */
    #[
        SerializedName('KeySchema'),
        Assert\KeySchema
    ]
    private ?array $keySchema = null;

    /**
     * @var array<int, array{
     *     IndexName: string,
     *     KeySchema: array<int, array{
     *          AttributeName: string,
     *          KeyType: string
     *     }>
     * }>|null
     */
    #[
        SerializedName('LocalSecondaryIndexes'),
        Assert\LocalSecondaryIndexes
    ]
    private ?array $localSecondaryIndexes = null;

    /**
     * @var array<int, array{
     *     IndexName: string,
     *     KeySchema: array<int, array{
     *          AttributeName: string,
     *          KeyType: string
     *     }>,
     *     Projection: array{ProjectionType: string},
     *     ProvisionedThroughput: array{ReadCapacityUnits: int, WriteCapacityUnits: int}|null
     * }>|null
     */
    #[
        SerializedName('GlobalSecondaryIndexes'),
        Assert\GlobalSecondaryIndexes
    ]
    private ?array $globalSecondaryIndexes = null;

    /**
     * @var array{ReadCapacityUnits: int, WriteCapacityUnits: int}|null
     */
    #[
        SerializedName('ProvisionedThroughput'),
        Assert\ProvisionedThroughput
    ]
    private ?array $provisionedThroughput = null;

    public function setTableName(string $tableName): void
    {
        $this->tableName = $tableName;
    }

    public function getTableName(): ?string
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

    /**
     * @return array<int, array{AttributeName: string, AttributeType: string}>|null
     */
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

    /**
     * @return array<int, array{AttributeName: string, KeyType: string}>|null
     */
    public function getKeySchema(): ?array
    {
        $this->assertKeySchemaAttributesDefined($this->keySchema ?? []);

        return $this->keySchema;
    }

    /**
     * @param array<int, array{AttributeName: string, KeyType: string}> $keySchema
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
     *          KeyType: string
     *     }>,
     *     Projection: array{ProjectionType: string},
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

    /**
     * @return array<int, array{
     *     IndexName: string,
     *     KeySchema: array<int, array{
     *          AttributeName: string,
     *          KeyType: string
     *     }>,
     *     Projection: array{ProjectionType: string},
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

    /**
     * @return array<int, array{
     *     IndexName: string,
     *     KeySchema: array<int, array{
     *          AttributeName: string,
     *          KeyType: string
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
            if ($key['KeyType'] === KeyType::HASH) {
                $hashKeys[] = $key;
            }
        }

        if (\count($hashKeys) < 1) {
            throw SchemaException::hashKeyNotSet();
        }
    }
}
