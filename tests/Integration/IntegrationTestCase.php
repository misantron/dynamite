<?php

declare(strict_types=1);

namespace Dynamite\Tests\Integration;

use Dynamite\Client\ClientInterface;
use Dynamite\Tests\DependencyMockTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\ErrorHandler\BufferingLogger;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class IntegrationTestCase extends TestCase
{
    use DependencyMockTrait;

    protected ClientInterface $client;

    protected NormalizerInterface $serializer;

    protected ValidatorInterface $validator;

    protected BufferingLogger $logger;

    protected function setUp(): void
    {
        $this->onSetUp();

        $this->serializer = $this->createSerializer();
        $this->validator = $this->createValidator();
        $this->logger = $this->createTestLogger();
        $this->client = $this->createClient();
    }

    protected function tearDown(): void
    {
        $this->onTearDown();

        $this->logger->cleanLogs();
    }

    abstract protected function onSetUp(): void;

    abstract protected function onTearDown(): void;

    abstract protected function createDynamoDbClient(): mixed;

    abstract protected function createClient(): ClientInterface;

    abstract protected function createTable(): void;
}
