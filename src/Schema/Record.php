<?php

declare(strict_types=1);

namespace Dynamite\Schema;

use Dynamite\Client\ClientInterface;

/**
 * @phpstan-import-type AttributeValue from ClientInterface
 */
final class Record
{
    /**
     * @param array<int, Value> $values
     */
    public function __construct(
        private readonly array $values
    ) {
    }

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
