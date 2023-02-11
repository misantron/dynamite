<?php

declare(strict_types=1);

namespace Dynamite\Tests\Integration;

use Aws\DynamoDb\DynamoDbClient;
use Dynamite\Client\AwsSdkClient;
use Dynamite\Client\ClientInterface;

trait AwsSdkIntegrationTrait
{
    protected DynamoDbClient $dynamoDbClient;

    protected function onSetUp(): void
    {
        $this->dynamoDbClient = $this->createDynamoDbClient();
    }

    protected function createTable(): void
    {
        $this->dynamoDbClient->createTable($this->getFixtureTableSchema());
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
