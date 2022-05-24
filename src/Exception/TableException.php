<?php

declare(strict_types=1);

namespace Dynamite\Exception;

class TableException extends AbstractException
{
    public static function notExists(string $tableName): static
    {
        return new static("Table `$tableName` does not exist");
    }

    public static function alreadyExists(string $tableName): static
    {
        return new static("Table `$tableName` already exists");
    }
}
