<?php

declare(strict_types=1);

namespace Dynamite\Tests\Integration\AsyncAws;

use AsyncAws\DynamoDb\Exception\ResourceNotFoundException;
use Dynamite\Tests\Integration\AsyncAwsIntegrationTrait;
use Dynamite\Tests\Integration\IntegrationTestCase;
use PHPUnit\Framework\Attributes\Group;

#[Group('AsyncAws')]
#[Group('integration')]
class TableTest extends IntegrationTestCase
{
    use AsyncAwsIntegrationTrait;

    public function testCreate(): void
    {
        $table = $this->createFixtureTable();

        $this->assertSame(self::TABLE_NAME, $table->getTableName());

        $table->setValidator($this->validator);
        $table->setNormalizer($this->serializer);

        $table->create($this->client, $this->logger);

        $response = $this->dynamoDbClient->tableExists([
            'TableName' => self::TABLE_NAME,
        ]);
        $response->resolve();

        $this->assertTrue($response->isSuccess());

        try {
            $response = $this->dynamoDbClient->describeTable([
                'TableName' => self::TABLE_NAME,
            ]);
            $response->resolve();
        } catch (ResourceNotFoundException) {
            self::fail('Table does not exists: ' . self::TABLE_NAME);
        }

        $this->assertSame(self::TABLE_NAME, $response->getTable()?->getTableName());
    }
}
