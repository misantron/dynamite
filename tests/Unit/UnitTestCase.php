<?php

declare(strict_types=1);

namespace Dynamite\Tests\Unit;

use AsyncAws\Core\Response;
use AsyncAws\Core\Test\Http\SimpleMockedResponse;
use AsyncAws\DynamoDb\Exception\ResourceNotFoundException;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\Validator\Mapping\Loader\AnnotationLoader as ValidatorAnnotationLoader;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

abstract class UnitTestCase extends TestCase
{
    protected function createValidator(): ValidatorInterface
    {
        return Validation::createValidatorBuilder()
            ->addLoader(new ValidatorAnnotationLoader())
            ->getValidator();
    }

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
