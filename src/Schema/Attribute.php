<?php

declare(strict_types=1);

namespace Dynamite\Schema;

use Dynamite\Enum\KeyTypeEnum;
use Dynamite\Enum\ScalarAttributeTypeEnum;

final readonly class Attribute
{
    public function __construct(
        private string $name,
        private ScalarAttributeTypeEnum $type,
        private ?KeyTypeEnum $keyType = null
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): ScalarAttributeTypeEnum
    {
        return $this->type;
    }

    public function getKeyType(): ?KeyTypeEnum
    {
        return $this->keyType;
    }
}
