<?php

declare(strict_types=1);

namespace Dynamite\Exception;

class AttributeException extends AbstractException
{
    public static function unknownType(string $type): static
    {
        return new static("Unknown attribute type `$type`");
    }

    public static function notExists(string $name): static
    {
        return new static("Table attribute `$name` does not exist");
    }
}
