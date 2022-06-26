<?php

declare(strict_types=1);

namespace Dynamite\Tests\Unit;

use AsyncAws\DynamoDb\DynamoDbClient;
use Dynamite\Executor;

class ExecutorTest extends UnitTestCase
{
    public function testConstructWithoutPurger(): void
    {
        $dynamoDbClient = $this->createMock(DynamoDbClient::class);

        $executor = new Executor($dynamoDbClient);
        $executor->execute([], []);

        self::assertNotNull($executor->getPurger());
    }
}
