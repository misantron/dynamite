<?php

declare(strict_types=1);

namespace Dynamite\Tests\Unit;

use Dynamite\Client\ClientInterface;
use Dynamite\Executor;
use Dynamite\Purger\Purger;
use Psr\Log\NullLogger;

class ExecutorTest extends UnitTestCase
{
    public function testDefaultConstructor(): void
    {
        $dynamoDbClient = $this->createMock(ClientInterface::class);

        $executor = new Executor($dynamoDbClient);
        $executor->execute([], []);

        self::assertInstanceOf(Purger::class, $executor->getPurger());
        self::assertInstanceOf(NullLogger::class, $executor->getLogger());
    }
}
