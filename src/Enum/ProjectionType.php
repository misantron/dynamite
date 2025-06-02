<?php

declare(strict_types=1);

namespace Dynamite\Enum;

enum ProjectionType: string
{
    case All = 'ALL';
    case Include = 'INCLUDE';
    case KeysOnly = 'KEYS_ONLY';
}
