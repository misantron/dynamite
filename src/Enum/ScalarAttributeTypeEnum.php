<?php

declare(strict_types=1);

namespace Dynamite\Enum;

enum ScalarAttributeTypeEnum: string
{
    case Binary = 'B';
    case Numeric = 'N';
    case String = 'S';
    case Null = 'NULL';
    case Bool = 'BOOL';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(
            static fn (ScalarAttributeTypeEnum $case): string => $case->value,
            self::cases()
        );
    }
}
