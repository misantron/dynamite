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
use Dynamite\Exception\SchemaException;
use Dynamite\Exception\TableException;
use Dynamite\Exception\ValidationException;
use Dynamite\Schema\Table;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class AbstractMigration implements MigrationInterface
{
    use TableTrait;

    private Table $schema;

    public function __construct(
        private readonly DynamoDbClient $dynamoDbClient,
        private readonly NormalizerInterface $serializer,
        private readonly ValidatorInterface $validator
    ) {
        $this->schema = new Table();
    }

    protected function addAttribute(string $name, string $type): self
    {
        if (!ScalarAttributeType::exists($type)) {
            throw SchemaException::unexpectedAttributeType($type);
        }

        $this->schema->addAttribute($name, $type);

        return $this;
    }

    protected function addHashKey(string $name): self
    {
        $this->schema->addKeyElement($name, KeyType::HASH);

        return $this;
    }

    protected function addRangeKey(string $name): self
    {
        $this->schema->addKeyElement($name, KeyType::RANGE);

        return $this;
    }

    protected function setProvisionedThroughput(int $writeCapacity, int $readCapacity): self
    {
        $this->schema->setProvisionedThroughput($writeCapacity, $readCapacity);

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
        if (!ProjectionType::exists($projectionType)) {
            throw SchemaException::unexpectedProjectionType($projectionType);
        }

        $this->schema->addGlobalSecondaryIndex(
            $name,
            $projectionType,
            $hashAttribute,
            $rangeAttribute,
            $writeCapacity,
            $readCapacity
        );

        return $this;
    }

    protected function addLocalSecondaryIndex(string $name, string $hashAttribute, string $rangeAttribute = null): self
    {
        $this->schema->addLocalSecondaryIndex($name, $hashAttribute, $rangeAttribute);

        return $this;
    }

    protected function create(): void
    {
        $violations = $this->validator->validate($this->schema);
        if (\count($violations) > 0) {
            throw new ValidationException($violations);
        }

        if ($this->isTableExists()) {
            throw TableException::alreadyExists($this->schema->getTableName());
        }

        $this->schema->assertHashKeySet();

        $input = $this->serializer->normalize(
            $this->schema,
            context: $this->schema->getSerializationContext('create')
        );

        $this->dynamoDbClient->createTable(new CreateTableInput($input))->resolve();
    }

    protected function update(): void
    {
        $violations = $this->validator->validate($this->schema);
        if (\count($violations) > 0) {
            throw new ValidationException($violations);
        }

        if (!$this->isTableExists()) {
            throw TableException::notExists($this->schema->getTableName());
        }

        $input = $this->serializer->normalize(
            $this->schema,
            context: $this->schema->getSerializationContext('update')
        );

        $this->dynamoDbClient->updateTable(new UpdateTableInput($input))->resolve();
    }

    protected function delete(): void
    {
        $violations = $this->validator->validate($this->schema);
        if (\count($violations) > 0) {
            throw new ValidationException($violations);
        }

        if (!$this->isTableExists()) {
            throw TableException::notExists($this->schema->getTableName());
        }

        $input = [
            'TableName' => $this->schema->getTableName(),
        ];

        $this->dynamoDbClient->deleteTable(new DeleteTableInput($input))->resolve();
    }
}
