<?php

declare(strict_types=1);

namespace Dynamite\Enum;

enum KeyType: string
{
    case Hash = 'HASH';
    case Range = 'RANGE';
}
