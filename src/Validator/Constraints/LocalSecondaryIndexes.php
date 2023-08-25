<?php

declare(strict_types=1);

namespace Dynamite\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class LocalSecondaryIndexes extends Assert\Compound
{
    /**
     * @param array<int, mixed> $options
     * @return array<int, Constraint>
     */
    protected function getConstraints(array $options): array
    {
        return [
            new Assert\Count(min: 1, minMessage: 'Local secondary indexes must contains at least one element'),
            new Assert\All([
                new Assert\Collection([
                    'IndexName' => new Assert\Required([
                        new TableOrIndexName(),
                    ]),
                    'KeySchema' => new Assert\Required([
                        new KeySchema(),
                    ]),
                    'Projection' => new Assert\Required([
                        new Projection(),
                    ]),
                ]),
            ]),
        ];
    }
}
