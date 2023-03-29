<?php

declare(strict_types=1);

namespace Dynamite\Serializer;

use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

final class PropertyNameConverter implements NameConverterInterface
{
    public function normalize(string $propertyName): string
    {
        return ucfirst($propertyName);
    }

    public function denormalize(string $propertyName): string
    {
        return lcfirst($propertyName);
    }
}
