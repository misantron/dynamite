<?php

declare(strict_types=1);

namespace Dynamite\Exception;

final class SchemaException extends AbstractException
{
    public static function unexpectedAttributeType(string $type): SchemaException
    {
        return new SchemaException("Unexpected attribute type `$type` provided");
    }

    public static function unexpectedProjectionType(string $type): SchemaException
    {
        return new SchemaException("Unexpected projection type `$type` provided");
    }

    public static function notDefinedAttribute(string $attribute): SchemaException
    {
        return new SchemaException("Attribute `$attribute` is not defined");
    }

    public static function hashKeyNotSet(): SchemaException
    {
        return new SchemaException('Table key require at least one hash attribute');
    }

    public static function provisionedThroughputNotSet(): SchemaException
    {
        return new SchemaException('Table provisioned throughput not set');
    }
}
