<?php

declare(strict_types=1);

namespace Dynamite\Tests\Unit;

use AsyncAws\DynamoDb\DynamoDbClient;
use Dynamite\Executor;
use Dynamite\Purger\Purger;

class ExecutorTest extends UnitTestCase
{
    public function testConstructWithoutPurger(): void
    {
        $dynamoDbClient = $this->createMock(DynamoDbClient::class);

        $executor = new Executor($dynamoDbClient);
        $executor->execute([], []);

        self::assertInstanceOf(Purger::class, $executor->getPurger());
    }
}
