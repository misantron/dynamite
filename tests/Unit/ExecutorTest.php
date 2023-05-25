<?php

declare(strict_types=1);

namespace Dynamite\Tests\Unit;

use Dynamite\Client\ClientInterface;
use Dynamite\Executor;
use Dynamite\Purger\Purger;
use PHPUnit\Framework\Attributes\Group;
use Psr\Log\NullLogger;

#[Group('unit')]
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
