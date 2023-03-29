<?php

declare(strict_types=1);

namespace Dynamite\Enum;

enum ProjectionTypeEnum: string
{
    case All = 'ALL';
    case Include = 'INCLUDE';
    case KeysOnly = 'KEYS_ONLY';
}
