<?php

declare(strict_types=1);

namespace Dynamite\Enum;

enum ScalarAttributeType: string
{
    case Binary = 'B';
    case Numeric = 'N';
    case String = 'S';
    case Null = 'NULL';
    case Bool = 'BOOL';
}
