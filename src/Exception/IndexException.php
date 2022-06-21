<?php

declare(strict_types=1);

namespace Dynamite\Exception;

final class IndexException extends AbstractException
{
    public static function notExists(string $indexName): IndexException
    {
        return new IndexException("Index `$indexName` does not exist");
    }

    public static function alreadyExists(string $indexName): IndexException
    {
        return new IndexException("Index `$indexName` already exists");
    }
}
