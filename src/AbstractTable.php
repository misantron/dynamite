<?php

declare(strict_types=1);

namespace Dynamite;

use Dynamite\Client\ClientInterface;
use Dynamite\Enum\KeyTypeEnum;
use Dynamite\Enum\ProjectionTypeEnum;
use Dynamite\Enum\ScalarAttributeTypeEnum;
use Dynamite\Exception\ValidationException;
use Dynamite\Schema\Attribute;
use Dynamite\Schema\Table;
use Dynamite\Validator\ValidatorAwareTrait;
use Psr\Log\LoggerInterface;
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

    final public function create(ClientInterface $client, LoggerInterface $logger): void
    {
        $this->initialize();

        $violations = $this->validator->validate($this->schema);
        if ($violations->count() > 0) {
            throw new ValidationException($violations);
        }

        $this->schema->assertHashKeySet();

        $client->createTable($this->schema);

        $logger->debug('Table created', [
            'table' => $this->schema->getTableName(),
        ]);
    }

    protected function addAttribute(string $name, ScalarAttributeTypeEnum $type, KeyTypeEnum $keyType = null): self
    {
        $this->schema->addAttribute($name, $type, $keyType);

        return $this;
    }

    /**
     * @param array<int, Attribute> $items
     */
    protected function addAttributes(array $items): self
    {
        foreach ($items as $item) {
            $this->addAttribute($item->getName(), $item->getType(), $item->getKeyType());
        }

        return $this;
    }

    protected function addGlobalSecondaryIndex(
        string $name,
        ProjectionTypeEnum $projectionType,
        string $hashAttribute,
        string $rangeAttribute = null,
        int $writeCapacity = null,
        int $readCapacity = null
    ): self {
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

    protected function addLocalSecondaryIndex(
        string $name,
        ProjectionTypeEnum $projectionType,
        string $hashAttribute,
        string $rangeAttribute = null
    ): self {
        $this->schema->addLocalSecondaryIndex($name, $projectionType, $hashAttribute, $rangeAttribute);

        return $this;
    }

    protected function setProvisionedThroughput(int $writeCapacity, int $readCapacity): self
    {
        $this->schema->setProvisionedThroughput($writeCapacity, $readCapacity);

        return $this;
    }

    abstract protected function configure(): void;
}
