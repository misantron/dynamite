<?php

declare(strict_types=1);

namespace Dynamite\Tests\Integration;

use AsyncAws\Core\Credentials\Credentials;
use AsyncAws\DynamoDb\DynamoDbClient;
use AsyncAws\DynamoDb\Enum\ProjectionType;
use AsyncAws\DynamoDb\Exception\ResourceNotFoundException;
use AsyncAws\DynamoDb\Result\TableExistsWaiter;
use Dynamite\AbstractMigration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader as SerializerAnnotationLoader;
use Symfony\Component\Serializer\NameConverter\MetadataAwareNameConverter;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Mapping\Loader\AnnotationLoader as ValidatorAnnotationLoader;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class IntegrationTestCase extends TestCase
{
    protected DynamoDbClient $dynamoDbClient;

    protected NormalizerInterface $serializer;

    protected ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->dynamoDbClient = $this->getDynamoDbClient();
        $this->serializer = $this->getSerializer();
        $this->validator = $this->getValidator();
    }

    protected function tearDown(): void
    {
        try {
            $this->dynamoDbClient->deleteTable([
                'TableName' => 'Users',
            ])->resolve();
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

        $response = $this->dynamoDbClient->tableExists([
            'TableName' => 'Users',
        ]);
        $response->resolve();

        return $response;
    }

    private function getDynamoDbClient(): DynamoDbClient
    {
        return new DynamoDbClient(
            [
                'endpoint' => 'http://localhost:8000',
            ],
            new Credentials('AccessKey', 'SecretKey')
        );
    }

    private function getSerializer(): NormalizerInterface
    {
        $classMetadataFactory = new ClassMetadataFactory(new SerializerAnnotationLoader());
        $nameConverter = new MetadataAwareNameConverter($classMetadataFactory);

        return new Serializer([new ObjectNormalizer($classMetadataFactory, $nameConverter)]);
    }

    private function getValidator(): ValidatorInterface
    {
        return Validation::createValidatorBuilder()
            ->addLoader(new ValidatorAnnotationLoader())
            ->getValidator();
    }
}
