<?php

declare(strict_types=1);

namespace Dynamite\Client;

use AsyncAws\Core\Configuration;
use AsyncAws\Core\Credentials\CredentialProvider;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class ClientFactory
{
    public function __construct(
        private readonly NormalizerInterface $normalizer,
        private readonly LoggerInterface $logger = new NullLogger()
    ) {
    }

    public static function create(
        NormalizerInterface $normalizer,
        LoggerInterface $logger = new NullLogger()
    ): self {
        return new self($normalizer, $logger);
    }

    /**
     * @see \AsyncAws\Core\Configuration
     * @param array<string, mixed>|Configuration $configuration
     */
    public function createAsyncAwsClient(
        Configuration|array $configuration = [],
        CredentialProvider $credentialProvider = null,
        HttpClientInterface $httpClient = null
    ): ClientInterface {
        return new AsyncAwsClient(
            new \AsyncAws\DynamoDb\DynamoDbClient($configuration, $credentialProvider, $httpClient, $this->logger),
            $this->normalizer,
            $this->logger
        );
    }

    /**
     * @see \Aws\AwsClient
     * @param array<string, mixed> $configuration
     */
    public function createAwsSdkClient(array $configuration): ClientInterface
    {
        return new AwsSdkClient(
            new \Aws\DynamoDb\DynamoDbClient($configuration),
            $this->normalizer,
            $this->logger
        );
    }
}
