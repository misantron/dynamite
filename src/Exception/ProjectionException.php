<?php

declare(strict_types=1);

namespace Dynamite\Exception;

class ProjectionException extends AbstractException
{
    public static function unknownType(string $type): static
    {
        return new static("Unknown projection type `$type`");
    }
}