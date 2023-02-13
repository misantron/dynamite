<?php

declare(strict_types=1);

namespace Dynamite\Validator\Constraints;

use Dynamite\Enum\ScalarAttributeTypeEnum;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class AttributeDefinitions extends Assert\Compound
{
    /**
     * @param array<int, mixed> $options
     * @return array<int, Constraint>
     */
    protected function getConstraints(array $options): array
    {
        return [
            new Assert\Count(min: 1, minMessage: 'Table attributes must contains at least one definition'),
            new Assert\All([
                new Assert\Collection([
                    'AttributeName' => new Assert\Required([
                        new Assert\NotBlank(),
                    ]),
                    'AttributeType' => new Assert\Required([
                        new Assert\NotBlank(),
                        new Assert\Choice(choices: ScalarAttributeTypeEnum::values()),
                    ]),
                ]),
            ]),
        ];
    }
}
