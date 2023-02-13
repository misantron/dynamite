<?php

declare(strict_types=1);

namespace Dynamite\Tests;

use Dynamite\Serializer\PropertyNameConverter;
use Symfony\Component\ErrorHandler\BufferingLogger;
use Symfony\Component\Serializer\Normalizer\BackedEnumNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Mapping\Loader\AnnotationLoader;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

trait DependencyMockTrait
{
    protected function createSerializer(): NormalizerInterface
    {
        return new Serializer([
            new BackedEnumNormalizer(),
            new ObjectNormalizer(null, new PropertyNameConverter()),
        ]);
    }

    protected function createValidator(): ValidatorInterface
    {
        return Validation::createValidatorBuilder()
            ->addLoader(new AnnotationLoader())
            ->getValidator();
    }

    protected function createTestLogger(): BufferingLogger
    {
        return new BufferingLogger();
    }
}
