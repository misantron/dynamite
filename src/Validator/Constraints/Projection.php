<?php

declare(strict_types=1);

namespace Dynamite\Validator\Constraints;

use AsyncAws\DynamoDb\Enum\ProjectionType;
use Symfony\Component\Validator\Constraints as Assert;

final class Projection extends Assert\Compound
{
    protected function getConstraints(array $options): array
    {
        return [
            new Assert\Collection([
                'ProjectionType' => new Assert\Required([
                    new Assert\NotBlank(),
                    new Assert\Choice(choices: [
                        ProjectionType::KEYS_ONLY,
                        ProjectionType::INCLUDE,
                        ProjectionType::ALL,
                    ]),
                ]),
            ]),
        ];
    }
}
