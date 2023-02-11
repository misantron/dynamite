<?php

declare(strict_types=1);

namespace Dynamite\Tests\Integration;

use AsyncAws\Core\Configuration;
use AsyncAws\Core\Credentials\CredentialProvider;
use AsyncAws\Core\Credentials\Credentials;
use AsyncAws\DynamoDb\DynamoDbClient;
use Dynamite\Client\ClientFactory;
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
        return ClientFactory::create($this->serializer, $this->logger)->createAsyncAwsClient(
            ...$this->getClientArguments()
        );
    }

    protected function createDynamoDbClient(): DynamoDbClient
    {
        return new DynamoDbClient(...$this->getClientArguments());
    }

    /**
     * @return array{0: Configuration, 1: CredentialProvider}
     */
    private function getClientArguments(): array
    {
        return [
            Configuration::create([
                'endpoint' => 'http://localhost:8000',
            ]),
            new Credentials('AccessKey', 'SecretKey'),
        ];
    }
}
