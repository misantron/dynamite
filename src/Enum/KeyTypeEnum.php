<?php

declare(strict_types=1);

namespace Dynamite\Enum;

enum KeyTypeEnum: string
{
    case Hash = 'HASH';
    case Range = 'RANGE';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(
            static fn (KeyTypeEnum $case): string => $case->value,
            self::cases()
        );
    }
}
