<?php

declare(strict_types=1);

namespace Dynamite\Schema;

use Dynamite\Enum\KeyType;
use Dynamite\Enum\ScalarAttributeType;

final readonly class Attribute
{
    public function __construct(
        private string $name,
        private ScalarAttributeType $type,
        private ?KeyType $keyType = null
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): ScalarAttributeType
    {
        return $this->type;
    }

    public function getKeyType(): ?KeyType
    {
        return $this->keyType;
    }
}
