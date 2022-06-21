<?php

declare(strict_types=1);

namespace Dynamite\Validator\Constraints;

use AsyncAws\DynamoDb\Enum\KeyType;
use Symfony\Component\Validator\Constraints as Assert;

#[\Attribute]
final class KeySchema extends Assert\Compound
{
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
                        new Assert\Choice(choices: [
                            KeyType::HASH,
                            KeyType::RANGE,
                        ]),
                    ]),
                ]),
            ]),
        ];
    }
}
