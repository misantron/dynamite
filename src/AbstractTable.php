<?php

declare(strict_types=1);

namespace Dynamite;

use AsyncAws\DynamoDb\DynamoDbClient;
use AsyncAws\DynamoDb\Enum\KeyType;
use AsyncAws\DynamoDb\Enum\ProjectionType;
use AsyncAws\DynamoDb\Enum\ScalarAttributeType;
use AsyncAws\DynamoDb\Input\CreateTableInput;
use Dynamite\Exception\SchemaException;
use Dynamite\Exception\ValidationException;
use Dynamite\Schema\Table;
use Dynamite\Validator\ValidatorAwareTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;

abstract class AbstractTable
{
    use TableTrait;
    use ValidatorAwareTrait;
    use NormalizerAwareTrait;

    private Table $schema;

    public function __construct()
    {
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

    protected function addAttributes(array $items): self
    {
        foreach ($items as [$name, $type]) {
            $this->addAttribute($name, $type);
        }

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

    protected function setProvisionedThroughput(int $writeCapacity, int $readCapacity): self
    {
        $this->schema->setProvisionedThroughput($writeCapacity, $readCapacity);

        return $this;
    }

    final public function create(DynamoDbClient $client, LoggerInterface $logger): void
    {
        $this->initialize();

        $violations = $this->validator->validate($this->schema);
        if ($violations->count() > 0) {
            throw new ValidationException($violations);
        }

        $this->schema->assertHashKeySet();

        $input = new CreateTableInput(
            $this->normalizer->normalize(
                $this->schema,
                context: [
                    AbstractObjectNormalizer::SKIP_NULL_VALUES => true,
                ]
            )
        );

        $client->createTable($input)->resolve();

        $logger->debug('Table created', [
            'table' => $this->schema->getTableName(),
        ]);
    }

    abstract protected function configure(): void;
}
