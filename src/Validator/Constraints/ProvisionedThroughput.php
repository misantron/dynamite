<?php

declare(strict_types=1);

namespace Dynamite\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;

#[\Attribute]
final class ProvisionedThroughput extends Assert\Compound
{
    /**
     * @param array<int, mixed> $options
     * @return array<int, Constraint>
     */
    protected function getConstraints(array $options): array
    {
        return [
            new Assert\Collection([
                'ReadCapacityUnits' => new Assert\Required([
                    new Assert\NotBlank(),
                    new Assert\Type('integer'),
                    new Assert\Positive(),
                ]),
                'WriteCapacityUnits' => new Assert\Required([
                    new Assert\NotBlank(),
                    new Assert\Type('integer'),
                    new Assert\Positive(),
                ]),
            ]),
        ];
    }
}
