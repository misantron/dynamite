<?php

declare(strict_types=1);

namespace Dynamite\Schema;

use Dynamite\Enum\ScalarAttributeTypeEnum;

final readonly class Value
{
    public function __construct(
        private string $name,
        private ScalarAttributeTypeEnum $type,
        private mixed $value
    ) {
    }

    public static function stringValue(string $name, string $value): self
    {
        return new self($name, ScalarAttributeTypeEnum::String, $value);
    }

    public static function numericValue(string $name, int|float $value): self
    {
        return new self($name, ScalarAttributeTypeEnum::Numeric, $value);
    }

    public static function boolValue(string $name, bool $value): self
    {
        return new self($name, ScalarAttributeTypeEnum::Bool, $value);
    }

    public static function binaryValue(string $name, string $value): self
    {
        return new self($name, ScalarAttributeTypeEnum::Binary, $value);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): ScalarAttributeTypeEnum
    {
        return $this->type;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }
}
