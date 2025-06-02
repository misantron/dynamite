<?php

declare(strict_types=1);

namespace Dynamite\Validator\Constraints;

use Dynamite\Enum\ProjectionTypeEnum;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;

final class Projection extends Assert\Compound
{
    /**
     * @param array<string, mixed> $options
     * @return array<int, Constraint>
     */
    protected function getConstraints(array $options): array
    {
        return [
            new Assert\Collection([
                'ProjectionType' => new Assert\Required([
                    new Assert\NotBlank(),
                    new Assert\Type(ProjectionTypeEnum::class),
                ]),
            ]),
        ];
    }
}
