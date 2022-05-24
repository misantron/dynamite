<?php

declare(strict_types=1);

namespace Dynamite\Exception;

abstract class AbstractException extends \RuntimeException implements ExceptionInterface
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }
}
