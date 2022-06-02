<?php

declare(strict_types=1);

namespace Dynamite\Exception;

final class TableException extends AbstractException
{
    public static function notExists(string $tableName): TableException
    {
        return new TableException("Table `$tableName` does not exist");
    }

    public static function alreadyExists(string $tableName): TableException
    {
        return new TableException("Table `$tableName` already exists");
    }
}
