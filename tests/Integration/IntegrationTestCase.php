<?php

declare(strict_types=1);

namespace Dynamite\Tests\Integration;

use AsyncAws\Core\Credentials\NullProvider;
use AsyncAws\DynamoDb\DynamoDbClient;
use Doctrine\Common\Annotations\AnnotationReader;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader as SerializerAnnotationLoader;
use Symfony\Component\Serializer\NameConverter\MetadataAwareNameConverter;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Mapping\Loader\AnnotationLoader as ValidatorAnnotationLoader;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class IntegrationTestCase extends TestCase
{
    protected DynamoDbClient $dynamoDbClient;
    protected SerializerInterface $serializer;
    protected ValidatorInterface $validator;

    protected function setUp(): void
    {
        $reader = new AnnotationReader();

        $this->dynamoDbClient = $this->getDynamoDbClient();
        $this->serializer = $this->getSerializer($reader);
        $this->validator = $this->getValidator($reader);
    }

    private function getDynamoDbClient(): DynamoDbClient
    {
        return new DynamoDbClient(
            ['endpoint' => 'http://localhost:4575'],
            new NullProvider()
        );
    }

    private function getSerializer(AnnotationReader $reader): SerializerInterface
    {
        $classMetadataFactory = new ClassMetadataFactory(new SerializerAnnotationLoader($reader));
        $nameConverter = new MetadataAwareNameConverter($classMetadataFactory);

        return new Serializer([new ObjectNormalizer($classMetadataFactory, $nameConverter)]);
    }

    private function getValidator(AnnotationReader $reader): ValidatorInterface
    {
        return Validation::createValidatorBuilder()
            ->addLoader(new ValidatorAnnotationLoader($reader))
            ->getValidator();
    }
}