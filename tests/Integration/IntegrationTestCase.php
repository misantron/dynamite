<?php

declare(strict_types=1);

namespace Dynamite\Tests\Integration;

use AsyncAws\Core\Credentials\Credentials;
use AsyncAws\DynamoDb\DynamoDbClient;
use AsyncAws\DynamoDb\Enum\ProjectionType;
use AsyncAws\DynamoDb\Exception\ResourceNotFoundException;
use AsyncAws\DynamoDb\Result\TableExistsWaiter;
use Dynamite\AbstractMigration;
use Dynamite\Tests\DependencyMockTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class IntegrationTestCase extends TestCase
{
    use DependencyMockTrait;

    protected DynamoDbClient $dynamoDbClient;

    protected NormalizerInterface $serializer;

    protected ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->dynamoDbClient = $this->createDynamoDbClient();
        $this->serializer = $this->createSerializer();
        $this->validator = $this->createValidator();
    }

    protected function tearDown(): void
    {
        try {
            $this->dynamoDbClient->deleteTable([
                'TableName' => 'Users',
            ])->resolve();
        } catch (ResourceNotFoundException) {
            // ignore exception
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

    private function createDynamoDbClient(): DynamoDbClient
    {
        return new DynamoDbClient(
            [
                'endpoint' => 'http://localhost:8000',
            ],
            new Credentials('AccessKey', 'SecretKey')
        );
    }
}
