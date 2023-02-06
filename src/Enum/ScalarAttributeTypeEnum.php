<?php

declare(strict_types=1);

namespace Dynamite\Enum;

enum ScalarAttributeTypeEnum: string
{
    case Binary = 'B';
    case Numeric = 'N';
    case String = 'S';
}
