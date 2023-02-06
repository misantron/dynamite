<?php

declare(strict_types=1);

namespace Dynamite\Tests\Integration\AsyncAws;

use AsyncAws\DynamoDb\Input\QueryInput;
use AsyncAws\DynamoDb\Input\ScanInput;
use AsyncAws\DynamoDb\ValueObject\AttributeValue;
use Dynamite\AbstractFixture;
use Dynamite\FixtureInterface;
use Dynamite\Tests\Integration\AsyncAwsIntegrationTestCase;

class FixtureTest extends AsyncAwsIntegrationTestCase
{
    public function testInsertSingleItem(): void
    {
        $this->createTable();

        $fixture = new class() extends AbstractFixture implements FixtureInterface {
            public function configure(): void
            {
                $this
                    ->setTableName('Users')
                    ->addItem([
                        'Id' => [
                            'S' => 'e5502ec2-42a7-408b-9f03-f8e162b6257e',
                        ],
                        'Email' => [
                            'S' => 'test@example.com',
                        ],
                    ])
                ;
            }
        };
        $fixture->setValidator($this->validator);

        $fixture->load($this->asyncAwsClient, $this->logger);

        $input = [
            'TableName' => 'Users',
            'KeyConditionExpression' => 'Id = :Id',
            'ExpressionAttributeValues' => [
                ':Id' => new AttributeValue([
                    'S' => 'e5502ec2-42a7-408b-9f03-f8e162b6257e',
                ]),
            ],
        ];

        $response = $this->dynamoDbClient->query(new QueryInput($input));

        self::assertSame(1, $response->getCount());
    }

    public function testInsertBatchItems(): void
    {
        $this->createTable();

        $fixture = new class() extends AbstractFixture implements FixtureInterface {
            public function configure(): void
            {
                $this
                    ->setTableName('Users')
                    ->addItems([
                        [
                            'Id' => [
                                'S' => 'e5502ec2-42a7-408b-9f03-f8e162b6257e',
                            ],
                            'Email' => [
                                'S' => 'test.one@example.com',
                            ],
                        ],
                        [
                            'Id' => [
                                'S' => 'f0cf458c-4fc0-4dd8-ba5b-eca6dba9be63',
                            ],
                            'Email' => [
                                'S' => 'test.two@example.com',
                            ],
                        ],
                        [
                            'Id' => [
                                'S' => '41757ca6-9b51-4bd8-adc4-22e0ba2902f8',
                            ],
                            'Email' => [
                                'S' => 'test.three@example.com',
                            ],
                        ],
                    ]);
            }
        };
        $fixture->setValidator($this->validator);

        $fixture->load($this->asyncAwsClient, $this->logger);

        $response = $this->dynamoDbClient->scan(
            new ScanInput([
                'TableName' => 'Users',
            ])
        );

        self::assertSame(3, $response->getCount());
    }
}
