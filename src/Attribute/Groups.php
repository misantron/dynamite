<?php

declare(strict_types=1);

namespace Dynamite\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
final readonly class Groups
{
    /**
     * @param list<string> $names
     */
    public function __construct(
        private array $names,
    ) {}

    /**
     * @return list<string>
     */
    public function getNames(): array
    {
        return $this->names;
    }
}
