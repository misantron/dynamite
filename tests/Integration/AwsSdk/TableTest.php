<?php

declare(strict_types=1);

namespace Dynamite\Tests\Integration\AwsSdk;

use Aws\DynamoDb\Exception\DynamoDbException;
use Dynamite\Client\AwsSdkClient;
use Dynamite\Tests\Integration\AwsSdkIntegrationTrait;
use Dynamite\Tests\Integration\IntegrationTestCase;
use PHPUnit\Framework\Attributes\Group;

#[Group('AwsSdk')]
class TableTest extends IntegrationTestCase
{
    use AwsSdkIntegrationTrait;

    public function testCreate(): void
    {
        $table = $this->createFixtureTable();

        self::assertSame(self::TABLE_NAME, $table->getTableName());

        $table->setValidator($this->validator);
        $table->setNormalizer($this->serializer);

        $table->create($this->client, $this->logger);

        try {
            $response = $this->dynamoDbClient->describeTable([
                'TableName' => self::TABLE_NAME,
            ]);
        } catch (DynamoDbException $e) {
            if ($e->getAwsErrorCode() === AwsSdkClient::RESOURCE_NOT_FOUND_ERROR_CODE) {
                self::fail('Table does not exists: ' . self::TABLE_NAME);
            }
            throw $e;
        }

        self::assertSame(self::TABLE_NAME, $response['Table']['TableName']);
    }
}
