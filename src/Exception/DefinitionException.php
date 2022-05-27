<?php

declare(strict_types=1);

namespace Dynamite\Exception;

final class DefinitionException extends AbstractException
{
    public static function provisionedThroughputNotDefined(): static
    {
        return new static('Provisioned throughput must be defined');
    }

    public static function tableAttributesNotDefined(): static
    {
        return new static('Table attributes must be defined');
    }

    public static function hashKeyNotDefined(): static
    {
        return new static('Table must contains at least one primary key column');
    }
}
