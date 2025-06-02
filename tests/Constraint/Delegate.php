<?php

declare(strict_types=1);

namespace Dynamite\Tests\Constraint;

use PHPUnit\Framework\Constraint\Constraint;

final class Delegate extends Constraint
{
    public function __construct(
        private readonly Constraint $matcher,
        private readonly \Closure $getter,
        private readonly string $name,
    ) {}

    public function evaluate(mixed $other, string $description = '', bool $returnResult = false): ?bool
    {
        return $this->matcher->evaluate(($this->getter)($other), $description, $returnResult);
    }

    public function toString(): string
    {
        return $this->name . ' ' . $this->matcher->toString(); // @phpstan-ignore method.internalInterface
    }
}
