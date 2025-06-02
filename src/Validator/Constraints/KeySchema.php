<?php

declare(strict_types=1);

namespace Dynamite\Validator\Constraints;

use Dynamite\Enum\KeyType;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class KeySchema extends Assert\Compound
{
    /**
     * @param array<string, mixed> $options
     * @return array<int, Constraint>
     */
    protected function getConstraints(array $options): array
    {
        return [
            new Assert\Count(min: 1, minMessage: 'Key schema must contains at least one element'),
            new Assert\All([
                new Assert\Collection([
                    'AttributeName' => new Assert\Required([
                        new Assert\NotBlank(),
                    ]),
                    'KeyType' => new Assert\Required([
                        new Assert\NotBlank(),
                        new Assert\Type(KeyType::class),
                    ]),
                ]),
            ]),
        ];
    }
}
