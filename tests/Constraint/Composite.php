<?php

declare(strict_types=1);

namespace Dynamite\Tests\Constraint;

use PHPUnit\Framework\Constraint\Constraint;
use PHPUnit\Framework\Constraint\IsEqual;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Util\ThrowableToStringMapper;

final class Composite extends Constraint
{
    /**
     * @var array<int, Constraint>
     */
    private array $constraints = [];

    private ?ExpectationFailedException $expectationFailedException = null;

    public function __construct(mixed ...$constraints)
    {
        foreach ($constraints as $constraint) {
            if (!$constraint instanceof Constraint) {
                $constraint = new IsEqual($constraint);
            }

            $this->constraints[] = $constraint;
        }
    }

    public function evaluate(mixed $other, string $description = '', bool $returnResult = false): bool
    {
        $success = true;
        foreach ($this->constraints as $constraint) {
            try {
                $constraint->evaluate($other, $description);
            } catch (ExpectationFailedException $ex) {
                $success = false;
                $this->expectationFailedException = $ex;
                break;
            }
        }

        if ($returnResult) {
            return $success;
        }

        if (!$success) {
            $this->fail($other, $description);
        }

        return false;
    }

    public function toString(): string
    {
        if ($this->expectationFailedException instanceof ExpectationFailedException) {
            return ThrowableToStringMapper::map($this->expectationFailedException); // @phpstan-ignore staticMethod.internalClass
        }

        $string = [];
        foreach ($this->constraints as $constraint) {
            $string[] = $constraint->toString(); // @phpstan-ignore method.internalInterface
        }

        return implode('; ', $string);
    }

    public function count(): int
    {
        return \count($this->constraints);
    }
}
