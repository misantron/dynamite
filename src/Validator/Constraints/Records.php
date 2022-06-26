<?php

declare(strict_types=1);

namespace Dynamite\Validator\Constraints;

use Symfony\Component\Validator\Constraints as Assert;

#[\Attribute]
final class Records extends Assert\Compound
{
    protected function getConstraints(array $options): array
    {
        return [
            new Assert\Count(
                min: 1,
                max: 100,
                minMessage: 'At least {{ limit }} record is required',
                maxMessage: 'Max batch size is {{ limit }} records'
            ),
        ];
    }
}
