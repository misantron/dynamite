<?php

declare(strict_types=1);

namespace Dynamite\Tests\Integration;

use AsyncAws\DynamoDb\Input\QueryInput;
use AsyncAws\DynamoDb\Input\ScanInput;
use AsyncAws\DynamoDb\ValueObject\AttributeValue;
use Dynamite\AbstractSeeder;

class SeederTest extends IntegrationTestCase
{
    public function testInsertSingleItem(): void
    {
        $seeder = new class($this->dynamoDbClient, $this->validator) extends AbstractSeeder {
            public function seed(): void
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
                    ->save()
                ;
            }
        };
        $seeder->seed();

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
        $seeder = new class($this->dynamoDbClient, $this->validator) extends AbstractSeeder {
            public function seed(): void
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
                    ])
                    ->save()
                ;
            }
        };
        $seeder->seed();

        $response = $this->dynamoDbClient->scan(new ScanInput([
            'TableName' => 'Users',
        ]));

        self::assertSame(3, $response->getCount());
    }
}
