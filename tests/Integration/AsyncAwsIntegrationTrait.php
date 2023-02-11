<?php

declare(strict_types=1);

namespace Dynamite\Tests\Integration;

use AsyncAws\Core\Credentials\Credentials;
use AsyncAws\DynamoDb\DynamoDbClient;
use Dynamite\Client\AsyncAwsClient;
use Dynamite\Client\ClientInterface;

trait AsyncAwsIntegrationTrait
{
    protected DynamoDbClient $dynamoDbClient;

    protected function onSetUp(): void
    {
        $this->dynamoDbClient = $this->createDynamoDbClient();
    }

    protected function createTable(): void
    {
        $this->dynamoDbClient->createTable($this->getFixtureTableSchema())->resolve();
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
