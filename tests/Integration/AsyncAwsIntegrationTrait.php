<?php

declare(strict_types=1);

namespace Dynamite\Tests\Integration;

use AsyncAws\Core\Credentials\Credentials;
use AsyncAws\DynamoDb\DynamoDbClient;
use AsyncAws\DynamoDb\Enum\KeyType;
use AsyncAws\DynamoDb\Enum\ProjectionType;
use AsyncAws\DynamoDb\Enum\ScalarAttributeType;
use Dynamite\Client\AsyncAwsClient;
use Dynamite\Client\ClientInterface;

trait AsyncAwsIntegrationTrait
{
    protected DynamoDbClient $dynamoDbClient;

    protected function onSetUp(): void
    {
        $this->dynamoDbClient = $this->createDynamoDbClient();
    }

    protected function onTearDown(): void
    {
        $this->client->dropTable('Users');
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

    protected function createClient(): ClientInterface
    {
        return new AsyncAwsClient(
            $this->dynamoDbClient,
            $this->serializer,
            $this->logger
        );
    }

    protected function createDynamoDbClient(): DynamoDbClient
    {
        return new DynamoDbClient(
            [
                'endpoint' => 'http://localhost:8000',
            ],
            new Credentials('AccessKey', 'SecretKey')
        );
    }
}
