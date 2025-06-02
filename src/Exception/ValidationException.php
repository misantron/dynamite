<?php

declare(strict_types=1);

namespace Dynamite\Exception;

use Symfony\Component\Validator\ConstraintViolationListInterface;

final class ValidationException extends AbstractException
{
    public function __construct(
        private readonly ConstraintViolationListInterface $violationList,
    ) {
        parent::__construct('Validation failed');
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function getErrors(): array
    {
        $output = [];
        foreach ($this->violationList as $value) {
            $output[$value->getPropertyPath()][] = (string) $value->getMessage();
        }

        return $output;
    }
}
