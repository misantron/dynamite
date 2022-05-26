<?php

declare(strict_types=1);

namespace Dynamite\Tests\Integration;

use AsyncAws\Core\Credentials\NullProvider;
use AsyncAws\DynamoDb\DynamoDbClient;
use AsyncAws\DynamoDb\Enum\ProjectionType;
use AsyncAws\DynamoDb\Exception\ResourceNotFoundException;
use AsyncAws\DynamoDb\Result\TableExistsWaiter;
use Doctrine\Common\Annotations\AnnotationReader;
use Dynamite\AbstractMigration;
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

    protected function tearDown(): void
    {
        try {
            $this->dynamoDbClient->deleteTable(['TableName' => 'Users'])->resolve();
        } catch (ResourceNotFoundException) {

        }
    }

    protected function createTable(): TableExistsWaiter
    {
        $migration = new class($this->dynamoDbClient, $this->serializer, $this->validator) extends AbstractMigration {

            public function up(): void
            {
                $this
                    ->setTableName('Users')
                    ->addAttribute('Id', 'S')
                    ->addAttribute('Email', 'S')
                    ->addHashKey('Id')
                    ->setProvisionedThroughput(1, 1)
                    ->addGlobalSecondaryIndex('Emails', ProjectionType::KEYS_ONLY, 'Email')
                    ->create()
                ;
            }

        };
        $migration->up();

        $response = $this->dynamoDbClient->tableExists(['TableName' => 'Users']);
        $response->resolve();

        return $response;
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