<?php

declare(strict_types=1);

namespace Dynamite\Tests\Integration;

use AsyncAws\DynamoDb\Enum\ScalarAttributeType;
use Dynamite\AbstractFixture;
use Dynamite\AbstractTable;
use Dynamite\Executor;
use Dynamite\FixtureInterface;
use Dynamite\Loader;
use Dynamite\TableInterface;

class ExecutorTest extends IntegrationTestCase
{
    public function testExecute(): void
    {
        $table = new class() extends AbstractTable implements TableInterface {
            protected function configure(): void
            {
                $this
                    ->setTableName('Users')
                    ->addAttributes([
                        ['Id', ScalarAttributeType::S],
                        ['Email', ScalarAttributeType::S],
                    ])
                    ->addHashKey('Id')
                    ->addRangeKey('Email')
                    ->setProvisionedThroughput(1, 1)
                ;
            }
        };

        $fixture = new class() extends AbstractFixture implements FixtureInterface {
            protected function configure(): void
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
                    ])
                ;
            }
        };

        $loader = new Loader($this->validator, $this->serializer);
        $loader->addTable($table);
        $loader->addFixture($fixture);

        $executor = new Executor($this->dynamoDbClient, logger: $this->logger);
        $executor->execute($loader->getFixtures(), $loader->getTables());

        $response = $this->dynamoDbClient->describeTable([
            'TableName' => 'Users',
        ]);
        $response->resolve();

        self::assertSame('Users', $response->getTable()?->getTableName());

        $response = $this->dynamoDbClient->scan([
            'TableName' => 'Users',
        ]);

        self::assertSame(2, $response->getCount());
    }
}
