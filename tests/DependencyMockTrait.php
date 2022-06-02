<?php

declare(strict_types=1);

namespace Dynamite\Tests;

use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader as SerializerAnnotationLoader;
use Symfony\Component\Serializer\NameConverter\MetadataAwareNameConverter;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Mapping\Loader\AnnotationLoader as ValidatorAnnotationLoader;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

trait DependencyMockTrait
{
    protected function createSerializer(): NormalizerInterface
    {
        $classMetadataFactory = new ClassMetadataFactory(new SerializerAnnotationLoader());
        $nameConverter = new MetadataAwareNameConverter($classMetadataFactory);

        return new Serializer([
            new ObjectNormalizer($classMetadataFactory, $nameConverter),
        ]);
    }

    protected function createValidator(): ValidatorInterface
    {
        return Validation::createValidatorBuilder()
            ->addLoader(new ValidatorAnnotationLoader())
            ->getValidator();
    }
}
