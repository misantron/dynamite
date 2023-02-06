<?php

declare(strict_types=1);

namespace Dynamite\Tests\Integration;

use AsyncAws\Core\Credentials\Credentials;
use AsyncAws\DynamoDb\DynamoDbClient;
use AsyncAws\DynamoDb\Enum\KeyType;
use AsyncAws\DynamoDb\Enum\ProjectionType;
use AsyncAws\DynamoDb\Enum\ScalarAttributeType;
use AsyncAws\DynamoDb\Exception\ResourceNotFoundException;
use Dynamite\Client\AsyncAws\AsyncAwsClient;
use Dynamite\ClientInterface;
use Dynamite\Tests\DependencyMockTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\ErrorHandler\BufferingLogger;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class AsyncAwsIntegrationTestCase extends TestCase
{
    use DependencyMockTrait;

    protected ClientInterface $asyncAwsClient;

    protected DynamoDbClient $dynamoDbClient;

    protected NormalizerInterface $serializer;

    protected ValidatorInterface $validator;

    protected BufferingLogger $logger;

    protected function setUp(): void
    {
        $this->dynamoDbClient = $this->createDynamoDbClient();
        $this->serializer = $this->createSerializer();
        $this->validator = $this->createValidator();
        $this->logger = $this->createTestLogger();
        $this->asyncAwsClient = $this->createAsyncAwsClient();
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

        $this->logger->cleanLogs();
    }

    protected function createTable(): void
    {
        $this->dynamoDbClient->createTable([
            'TableName' => 'Users',
            'AttributeDefinitions' => [
                [
                    'AttributeName' => 'Id',
                    'AttributeType' => ScalarAttributeType::S,
                ],
                [
                    'AttributeName' => 'Email',
                    'AttributeType' => ScalarAttributeType::S,
                ],
            ],
            'KeySchema' => [
                [
                    'AttributeName' => 'Id',
                    'KeyType' => KeyType::HASH,
                ],
            ],
            'GlobalSecondaryIndexes' => [
                [
                    'IndexName' => 'Emails',
                    'Projection' => [
                        'ProjectionType' => ProjectionType::KEYS_ONLY,
                    ],
                    'KeySchema' => [
                        [
                            'AttributeName' => 'Email',
                            'KeyType' => KeyType::HASH,
                        ],
                    ],
                    'ProvisionedThroughput' => [
                        'ReadCapacityUnits' => 1,
                        'WriteCapacityUnits' => 1,
                    ],
                ],
            ],
            'ProvisionedThroughput' => [
                'ReadCapacityUnits' => 1,
                'WriteCapacityUnits' => 1,
            ],
        ])->resolve();
    }

    private function createAsyncAwsClient(): ClientInterface
    {
        return new AsyncAwsClient(
            $this->dynamoDbClient,
            $this->serializer,
            $this->logger
        );
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
