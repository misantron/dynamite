<?php

declare(strict_types=1);

namespace Dynamite\Tests\Integration\AwsSdk;

use Dynamite\Schema\Record;
use Dynamite\Schema\Value;
use Dynamite\Tests\Integration\AwsSdkIntegrationTrait;
use Dynamite\Tests\Integration\IntegrationTestCase;
use PHPUnit\Framework\Attributes\Group;

#[Group('AwsSdk')]
#[Group('integration')]
class FixtureTest extends IntegrationTestCase
{
    use AwsSdkIntegrationTrait;

    public function testInsertSingleItem(): void
    {
        $this->createTable();

        $fixture = $this->createFixture([
            new Record([
                Value::stringValue('Id', 'e5502ec2-42a7-408b-9f03-f8e162b6257e'),
                Value::stringValue('Email', 'test@example.com'),
            ]),
        ]);
        $fixture->setValidator($this->validator);

        $fixture->load($this->client, $this->logger);

        $input = [
            'TableName' => self::TABLE_NAME,
            'KeyConditionExpression' => 'Id = :Id',
            'ExpressionAttributeValues' => [
                ':Id' => [
                    'S' => 'e5502ec2-42a7-408b-9f03-f8e162b6257e',
                ],
            ],
        ];

        $response = $this->dynamoDbClient->query($input);

        $this->assertSame(1, $response['Count']);
    }

    public function testInsertBatchItems(): void
    {
        $this->createTable();

        $fixture = $this->createFixture([
            new Record([
                Value::stringValue('Id', 'e5502ec2-42a7-408b-9f03-f8e162b6257e'),
                Value::stringValue('Email', 'test.one@example.com'),
            ]),
            new Record([
                Value::stringValue('Id', 'f0cf458c-4fc0-4dd8-ba5b-eca6dba9be63'),
                Value::stringValue('Email', 'test.two@example.com'),
            ]),
            new Record([
                Value::stringValue('Id', '41757ca6-9b51-4bd8-adc4-22e0ba2902f8'),
                Value::stringValue('Email', 'test.three@example.com'),
            ]),
        ]);
        $fixture->setValidator($this->validator);

        $fixture->load($this->client, $this->logger);

        $response = $this->dynamoDbClient->scan([
            'TableName' => self::TABLE_NAME,
        ]);

        $this->assertSame(3, $response['Count']);
    }
}
