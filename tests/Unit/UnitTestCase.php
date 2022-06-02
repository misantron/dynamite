<?php

declare(strict_types=1);

namespace Dynamite\Tests\Unit;

use AsyncAws\Core\Response;
use AsyncAws\Core\Test\Http\SimpleMockedResponse;
use AsyncAws\DynamoDb\Exception\ResourceNotFoundException;
use Dynamite\Tests\DependencyMockTrait;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

abstract class UnitTestCase extends TestCase
{
    use DependencyMockTrait;

    protected function createMockedResponse(): Response
    {
        $client = $this->createClientMock();

        return new Response(
            $client->request('POST', 'http://localhost'),
            $client,
            new NullLogger()
        );
    }

    protected function createResourceNotFoundException(): ResourceNotFoundException
    {
        $client = $this->createClientMock();

        return new ResourceNotFoundException($client->request('POST', 'http://localhost'));
    }

    private function createClientMock(): HttpClientInterface
    {
        return new MockHttpClient(new SimpleMockedResponse('{}', [], 200));
    }
}
