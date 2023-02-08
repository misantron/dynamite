<?php

declare(strict_types=1);

namespace Dynamite\Tests\Integration;

use Aws\DynamoDb\DynamoDbClient;
use Dynamite\Client\AwsSdkClient;
use Dynamite\Client\ClientInterface;
use Dynamite\Enum\KeyTypeEnum;
use Dynamite\Enum\ProjectionTypeEnum;
use Dynamite\Enum\ScalarAttributeTypeEnum;

trait AwsSdkIntegrationTrait
{
    protected DynamoDbClient $dynamoDbClient;

    protected function dropTable(): void
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
                    'AttributeType' => ScalarAttributeTypeEnum::String->value,
                ],
                [
                    'AttributeName' => 'Email',
                    'AttributeType' => ScalarAttributeTypeEnum::String->value,
                ],
            ],
            'KeySchema' => [
                [
                    'AttributeName' => 'Id',
                    'KeyType' => KeyTypeEnum::Hash->value,
                ],
            ],
            'GlobalSecondaryIndexes' => [
                [
                    'IndexName' => 'Emails',
                    'Projection' => [
                        'ProjectionType' => ProjectionTypeEnum::KeysOnly->value,
                    ],
                    'KeySchema' => [
                        [
                            'AttributeName' => 'Email',
                            'KeyType' => KeyTypeEnum::Hash->value,
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
        ]);
    }

    protected function createDynamoDbClient(): DynamoDbClient
    {
        return new DynamoDbClient([
            'endpoint' => 'http://localhost:8000',
            'credentials' => [
                'key' => 'AccessKey',
                'secret' => 'SecretKey',
            ],
            'region' => 'us-east-2',
            'version' => 'latest',
        ]);
    }

    protected function createClient(): ClientInterface
    {
        return new AwsSdkClient(
            $this->dynamoDbClient,
            $this->serializer,
            $this->logger
        );
    }
}
