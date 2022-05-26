<?php

declare(strict_types=1);

namespace Dynamite;

use AsyncAws\DynamoDb\DynamoDbClient;
use AsyncAws\DynamoDb\Enum\KeyType;
use AsyncAws\DynamoDb\Enum\ProjectionType;
use AsyncAws\DynamoDb\Enum\ScalarAttributeType;
use AsyncAws\DynamoDb\Input\CreateTableInput;
use AsyncAws\DynamoDb\Input\DeleteTableInput;
use AsyncAws\DynamoDb\Input\UpdateTableInput;
use Dynamite\Exception\AttributeException;
use Dynamite\Exception\DefinitionException;
use Dynamite\Exception\ProjectionException;
use Dynamite\Exception\TableException;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class AbstractMigration implements MigrationInterface
{
    use TableTrait;

    private const DEFAULT_SERIALIZATION_CONTEXT = [
        AbstractNormalizer::IGNORED_ATTRIBUTES => [
            'dynamoDbClient',
            'serializer',
            'validator',
        ],
        AbstractObjectNormalizer::SKIP_NULL_VALUES => true,
    ];

    #[
        Groups(['create']),
        SerializedName('AttributeDefinitions')
    ]
    private ?array $attributeDefinitions = null;

    #[
        Groups(['create']),
        SerializedName('KeySchema')
    ]
    private ?array $keySchema = null;

    #[
        Groups(['create']),
        SerializedName('LocalSecondaryIndexes')
    ]
    private ?array $localSecondaryIndexes = null;

    #[
        Groups(['create', 'update']),
        SerializedName('GlobalSecondaryIndexes')
    ]
    private ?array $globalSecondaryIndexes = null;

    #[
        Groups(['create', 'update']),
        SerializedName('ProvisionedThroughput')
    ]
    private ?array $provisionedThroughput = null;

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

        $this->attributeDefinitions[] = [
            'AttributeName' => $name,
            'AttributeType' => $type,
        ];

        return $this;
    }

    public function __get(string $name)
    {
        if (!property_exists($this, $name)) {
            throw new \InvalidArgumentException("Try to access unknown property `$name`");
        }

        return $this->{$name};
    }

    protected function addHashKey(string $name): self
    {
        if ($this->keySchema === null) {
            $this->keySchema = [];
        }

        $this->keySchema[] = [
            'AttributeName' => $name,
            'KeyType' => KeyType::HASH,
        ];

        return $this;
    }

    protected function addRangeKey(string $name): self
    {
        if ($this->keySchema === null) {
            $this->keySchema = [];
        }

        $this->keySchema[] = [
            'AttributeName' => $name,
            'KeyType' => KeyType::RANGE,
        ];

        return $this;
    }

    protected function setProvisionedThroughput(int $writeCapacity, int $readCapacity): self
    {
        $this->provisionedThroughput = [
            'ReadCapacityUnits' => $readCapacity,
            'WriteCapacityUnits' => $writeCapacity,
        ];

        return $this;
    }

    protected function addGlobalSecondaryIndex(
        string $name,
        string $projectionType,
        string $hashAttribute,
        string $rangeAttribute = null,
        int $writeCapacity = null,
        int $readCapacity = null
    ): self {
        if ($this->globalSecondaryIndexes === null) {
            $this->globalSecondaryIndexes = [];
        }

        if (!ProjectionType::exists($projectionType)) {
            throw ProjectionException::unknownType($projectionType);
        }

        $this->isAttributeExists($hashAttribute);

        $keySchema = [
            [
                'AttributeName' => $hashAttribute,
                'KeyType' => KeyType::HASH,
            ],
        ];

        if ($rangeAttribute !== null) {
            $this->isAttributeExists($rangeAttribute);

            $keySchema[] = [
                'AttributeName' => $rangeAttribute,
                'KeyType' => KeyType::RANGE,
            ];
        }

        $this->globalSecondaryIndexes[] = [
            'IndexName' => $name,
            'KeySchema' => $keySchema,
            'Projection' => [
                'ProjectionType' => $projectionType,
            ],
            'ProvisionedThroughput' => $this->createIndexProvisionedThroughput($writeCapacity, $readCapacity),
        ];

        return $this;
    }

    protected function addLocalSecondaryIndexes(string $name, string $hashAttribute, string $rangeAttribute): self
    {
        if ($this->localSecondaryIndexes === null) {
            $this->localSecondaryIndexes = [];
        }

        $this->isAttributeExists($hashAttribute);
        $this->isAttributeExists($rangeAttribute);

        $this->localSecondaryIndexes[] = [
            'IndexName' => $name,
            'KeySchema' => [
                [
                    'AttributeName' => $hashAttribute,
                    'KeyType' => KeyType::HASH,
                ],
                [
                    'AttributeName' => $rangeAttribute,
                    'KeyType' => KeyType::RANGE,
                ],
            ],
        ];

        return $this;
    }

    private function createIndexProvisionedThroughput(?int $writeCapacity, ?int $readCapacity): array
    {
        if ($writeCapacity === null && $readCapacity === null) {
            if ($this->provisionedThroughput === null) {
                throw DefinitionException::provisionedThroughputNotDefined();
            }

            return $this->provisionedThroughput;
        }

        return [
            'ReadCapacityUnits' => $readCapacity,
            'WriteCapacityUnits' => $writeCapacity,
        ];
    }

    private function isAttributeExists(string $attribute): void
    {
        if ($this->attributeDefinitions === null) {
            throw DefinitionException::tableAttributesNotDefined();
        }

        foreach ($this->attributeDefinitions as $definition) {
            if ($definition['AttributeName'] === $attribute) {
                return;
            }
        }

        throw AttributeException::notExists($attribute);
    }

    private function isHashKeySet(): void
    {
        $hashKeysFound = [];
        foreach ($this->keySchema as $item) {
            if ($item['KeyType'] === KeyType::HASH) {
                $hashKeysFound[] = $item['AttributeName'];
            }
        }

        if (\count($hashKeysFound) === 0) {
            throw DefinitionException::hashKeyNotDefined();
        }

        $this->isAttributeExists(current($hashKeysFound));
    }

    protected function create(): array
    {
        $violations = $this->validator->validate($this);
        if (\count($violations) > 0) {
            throw new ValidationFailedException('', $violations);
        }

        if ($this->isTableExists()) {
            throw TableException::alreadyExists($this->tableName);
        }
        $this->isHashKeySet();

        $input = $this->serializer->normalize($this, context: array_merge(
            self::DEFAULT_SERIALIZATION_CONTEXT,
            [
                'groups' => 'create',
            ]
        ));

        $response = $this->dynamoDbClient->createTable(new CreateTableInput($input));
        $response->resolve();

        return $response->info();
    }

    protected function update(): array
    {
        $violations = $this->validator->validate($this);
        if (\count($violations) > 0) {
            throw new ValidationFailedException('', $violations);
        }

        if (!$this->isTableExists()) {
            throw TableException::notExists($this->tableName);
        }

        $input = $this->serializer->normalize($this, context: array_merge(
            self::DEFAULT_SERIALIZATION_CONTEXT,
            [
                'groups' => 'update',
            ]
        ));

        $response = $this->dynamoDbClient->updateTable(new UpdateTableInput($input));
        $response->resolve();

        return $response->info();
    }

    protected function delete(): array
    {
        $violations = $this->validator->validate($this);
        if (\count($violations) > 0) {
            throw new ValidationFailedException('', $violations);
        }

        if (!$this->isTableExists()) {
            throw TableException::notExists($this->tableName);
        }

        $input = new DeleteTableInput([
            'TableName' => $this->tableName,
        ]);

        $response = $this->dynamoDbClient->deleteTable($input);
        $response->resolve();

        return $response->info();
    }
}
