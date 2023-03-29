<?php

declare(strict_types=1);

namespace Dynamite\Enum;

enum KeyTypeEnum: string
{
    case Hash = 'HASH';
    case Range = 'RANGE';
}
