<?php

declare(strict_types=1);

namespace Dynamite\Enum;

enum ProjectionTypeEnum: string
{
    case All = 'ALL';
    case Include = 'INCLUDE';
    case KeysOnly = 'KEYS_ONLY';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(
            static fn (ProjectionTypeEnum $case): string => $case->value,
            self::cases()
        );
    }
}
