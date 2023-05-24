<?php

declare(strict_types=1);

namespace Dynamite\Exception;

final class SchemaException extends AbstractException
{
    public static function notDefinedAttribute(string $attribute): self
    {
        return new self("Attribute `$attribute` is not defined");
    }

    public static function hashKeyNotSet(): self
    {
        return new self('Table key require at least one hash attribute');
    }

    public static function provisionedThroughputNotSet(): self
    {
        return new self('Table provisioned throughput not set');
    }
}
