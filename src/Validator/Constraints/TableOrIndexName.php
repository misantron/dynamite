<?php

declare(strict_types=1);

namespace Dynamite\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class TableOrIndexName extends Assert\Compound
{
    /**
     * @param array<string, mixed> $options
     * @return array<int, Constraint>
     */
    protected function getConstraints(array $options): array
    {
        return [
            new Assert\NotBlank(message: 'Table name is not defined'),
            new Assert\Length(
                min: 3,
                max: 255,
                minMessage: 'Name should have at least {{ limit }} characters length',
                maxMessage: 'Name cannot have more than {{ limit }} characters length',
            ),
            new Assert\Regex('/^[a-zA-Z0-9_\-.]+$/'),
        ];
    }
}
