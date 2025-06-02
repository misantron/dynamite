<?php

declare(strict_types=1);

namespace Dynamite\Schema;

use Dynamite\Client\ClientInterface;

/**
 * @phpstan-import-type AttributeValue from ClientInterface
 */
final readonly class Record
{
    /**
     * @param array<int, Value> $values
     */
    public function __construct(
        private array $values,
    ) {}

    /**
     * @return array<string, AttributeValue>
     */
    public function getValues(): array
    {
        $values = [];
        foreach ($this->values as $value) {
            $values[$value->getName()] = [
                $value->getType()->value => $value->getValue(),
            ];
        }

        return $values;
    }
}
