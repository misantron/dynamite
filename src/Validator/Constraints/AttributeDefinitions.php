<?php

declare(strict_types=1);

namespace Dynamite\Validator\Constraints;

use AsyncAws\DynamoDb\Enum\ScalarAttributeType;
use Symfony\Component\Validator\Constraints as Assert;

#[\Attribute]
final class AttributeDefinitions extends Assert\Compound
{
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
                        new Assert\Choice(choices: [
                            ScalarAttributeType::S,
                            ScalarAttributeType::B,
                            ScalarAttributeType::N,
                        ]),
                    ]),
                ]),
            ]),
        ];
    }
}
