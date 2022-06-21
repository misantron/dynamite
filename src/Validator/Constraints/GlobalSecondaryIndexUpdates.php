<?php

declare(strict_types=1);

namespace Dynamite\Validator\Constraints;

use Symfony\Component\Validator\Constraints as Assert;

#[\Attribute]
final class GlobalSecondaryIndexUpdates extends Assert\Compound
{
    protected function getConstraints(array $options): array
    {
        return [
            new Assert\Collection([
                'Create' => new Assert\Optional([
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
                        'ProvisionedThroughput' => new Assert\Required([
                            new ProvisionedThroughput([
                                'groups' => ['update'],
                            ]),
                        ]),
                    ]),
                ]),
                'Update' => new Assert\Optional([
                    new Assert\Collection([
                        'IndexName' => new Assert\Required([
                            new TableOrIndexName(),
                        ]),
                        'ProvisionedThroughput' => new Assert\Required([
                            new ProvisionedThroughput([
                                'groups' => ['update'],
                            ]),
                        ]),
                    ]),
                ]),
                'Delete' => new Assert\Optional(
                    [
                        new Assert\Collection([
                            'IndexName' => new Assert\Required([
                                new TableOrIndexName(),
                            ]),
                        ]),
                    ]
                ),
            ]),
        ];
    }
}
