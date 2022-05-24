<?php

declare(strict_types=1);

namespace Dynamite;

use AsyncAws\DynamoDb\DynamoDbClient;
use AsyncAws\DynamoDb\Enum\KeyType;
use AsyncAws\DynamoDb\Enum\ScalarAttributeType;
use AsyncAws\DynamoDb\Input\CreateTableInput;
use AsyncAws\DynamoDb\Input\DeleteTableInput;
use AsyncAws\DynamoDb\Input\UpdateTableInput;
use AsyncAws\DynamoDb\ValueObject\AttributeDefinition;
use AsyncAws\DynamoDb\ValueObject\GlobalSecondaryIndex;
use AsyncAws\DynamoDb\ValueObject\KeySchemaElement;
use AsyncAws\DynamoDb\ValueObject\LocalSecondaryIndex;
use AsyncAws\DynamoDb\ValueObject\ProvisionedThroughput;
use Dynamite\Exception\AttributeException;
use Dynamite\Exception\DefinitionException;
use Dynamite\Exception\TableException;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class AbstractMigration implements MigrationInterface
{
    use TableTrait;

    /**
     * @var AttributeDefinition[]|null
     */
    #[
        Groups(['create']),
        SerializedName('AttributeDefinitions')
    ]
    private ?array $attributeDefinitions;

    /**
     * @var KeySchemaElement[]|null
     */
    #[
        Groups(['create']),
        SerializedName('KeySchema')
    ]
    private ?array $keySchema;

    /**
     * @var LocalSecondaryIndex[]|null
     */
    #[
        Groups(['create']),
        SerializedName('LocalSecondaryIndexes')
    ]
    private ?array $localSecondaryIndexes;

    /**
     * @var GlobalSecondaryIndex[]|null
     */
    #[
        Groups(['create']),
        SerializedName('GlobalSecondaryIndexes')
    ]
    private ?array $globalSecondaryIndexes;

    #[
        Groups(['create']),
        SerializedName('ProvisionedThroughput')
    ]
    private ?ProvisionedThroughput $provisionedThroughput;

    public function __construct(
        private readonly DynamoDbClient $dynamoDbClient,
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator
    ) {
    }

    protected function addAttribute(string $name, string $type): self
    {
        if ($this->attributeDefinitions === null) {
            $this->attributeDefinitions = [];
        }

        if (!ScalarAttributeType::exists($type)) {
            throw AttributeException::unknownType($type);
        }

        $this->attributeDefinitions[] = new AttributeDefinition([
            'AttributeName' => $name,
            'AttributeType' => $type,
        ]);

        return $this;
    }

    protected function addHashKey(string $name): self
    {
        if ($this->keySchema === null) {
            $this->keySchema = [];
        }

        $this->keySchema[] = new KeySchemaElement([
            'AttributeName' => $name,
            'KeyType' => KeyType::HASH,
        ]);

        return $this;
    }

    protected function addRangeKey(string $name): self
    {
        if ($this->keySchema === null) {
            $this->keySchema = [];
        }

        $this->keySchema[] = new KeySchemaElement([
            'AttributeName' => $name,
            'KeyType' => KeyType::RANGE,
        ]);

        return $this;
    }

    protected function setProvisionedThroughput(int $writeCapacity, int $readCapacity): self
    {
        $this->provisionedThroughput = new ProvisionedThroughput([
            'ReadCapacityUnits' => $readCapacity,
            'WriteCapacityUnits' => $writeCapacity,
        ]);

        return $this;
    }

    protected function addGlobalSecondaryIndex(
        string $name,
        string $hashAttribute,
        string $rangeAttribute,
        int $writeCapacity = null,
        int $readCapacity = null
    ): self {
        if ($this->globalSecondaryIndexes === null) {
            $this->globalSecondaryIndexes = [];
        }

        $this->isAttributeExists($hashAttribute);
        $this->isAttributeExists($rangeAttribute);

        $this->globalSecondaryIndexes[] = new GlobalSecondaryIndex([
            'IndexName' => $name,
            'KeySchema' => [
                new KeySchemaElement([
                    'AttributeName' => $hashAttribute,
                    'KeyType' => KeyType::HASH,
                ]),
                new KeySchemaElement([
                    'AttributeName' => $rangeAttribute,
                    'KeyType' => KeyType::RANGE,
                ]),
            ],
            'ProvisionedThroughput' => $this->createIndexProvisionedThroughput($writeCapacity, $readCapacity),
        ]);

        return $this;
    }

    protected function addLocalSecondaryIndexes(string $name, string $hashAttribute, string $rangeAttribute): self
    {
        if ($this->localSecondaryIndexes === null) {
            $this->localSecondaryIndexes = [];
        }

        $this->isAttributeExists($hashAttribute);
        $this->isAttributeExists($rangeAttribute);

        $this->localSecondaryIndexes[] = new LocalSecondaryIndex([
            'IndexName' => $name,
            'KeySchema' => [
                new KeySchemaElement([
                    'AttributeName' => $hashAttribute,
                    'KeyType' => KeyType::HASH,
                ]),
                new KeySchemaElement([
                    'AttributeName' => $rangeAttribute,
                    'KeyType' => KeyType::RANGE,
                ]),
            ],
        ]);

        return $this;
    }

    private function createIndexProvisionedThroughput(?int $writeCapacity, ?int $readCapacity): ProvisionedThroughput
    {
        if ($writeCapacity === null && $readCapacity === null) {
            if ($this->provisionedThroughput === null) {
                throw DefinitionException::provisionedThroughputNotDefined();
            }

            return $this->provisionedThroughput;
        }

        return new ProvisionedThroughput([
            'ReadCapacityUnits' => $readCapacity,
            'WriteCapacityUnits' => $writeCapacity,
        ]);
    }

    private function isAttributeExists(string $attribute): void
    {
        if ($this->attributeDefinitions === null) {
            throw DefinitionException::tableAttributesNotDefined();
        }

        foreach ($this->attributeDefinitions as $definition) {
            if ($definition->getAttributeName() === $attribute) {
                return;
            }
        }

        throw AttributeException::notExists($attribute);
    }

    private function isHashKeySet(): void
    {
        $hashKeysFound = [];
        foreach ($this->keySchema as $item) {
            if ($item->getKeyType() === KeyType::HASH) {
                $hashKeysFound[] = $item->getAttributeName();
            }
        }

        if (\count($hashKeysFound) === 0) {
            throw DefinitionException::hashKeyNotDefined();
        }

        $this->isAttributeExists(current($hashKeysFound));
    }

    protected function create(): array
    {
        if ($this->isTableExists()) {
            throw TableException::alreadyExists($this->tableName);
        }
        $this->isHashKeySet();

        $input = new CreateTableInput($this->serializer->normalize($this, context: ['groups' => 'create']));

        $response = $this->dynamoDbClient->createTable($input);
        $response->resolve();

        return $response->info();
    }

    protected function update(): array
    {
        if (!$this->isTableExists()) {
            throw TableException::notExists($this->tableName);
        }

        $input = new UpdateTableInput($this->serializer->normalize($this, context: ['groups' => 'update']));

        $response = $this->dynamoDbClient->updateTable($input);
        $response->resolve();

        return $response->info();
    }

    protected function delete(): array
    {
        if (!$this->isTableExists()) {
            throw TableException::notExists($this->tableName);
        }

        $input = new DeleteTableInput($this->serializer->normalize($this, context: ['groups' => 'delete']));

        $response = $this->dynamoDbClient->deleteTable($input);
        $response->resolve();

        return $response->info();
    }
}
