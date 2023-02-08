<?php

declare(strict_types=1);

namespace Dynamite\Tests\Integration\AwsSdk;

use Dynamite\AbstractFixture;
use Dynamite\AbstractTable;
use Dynamite\Enum\KeyTypeEnum;
use Dynamite\Enum\ScalarAttributeTypeEnum;
use Dynamite\Executor;
use Dynamite\FixtureInterface;
use Dynamite\Loader;
use Dynamite\Schema\Attribute;
use Dynamite\TableInterface;
use Dynamite\Tests\Integration\AwsSdkIntegrationTrait;
use Dynamite\Tests\Integration\IntegrationTestCase;
use PHPUnit\Framework\Attributes\Group;

#[Group('AwsSdk')]
class ExecutorTest extends IntegrationTestCase
{
    use AwsSdkIntegrationTrait;

    public function testExecute(): void
    {
        $table = new class() extends AbstractTable implements TableInterface {
            protected function configure(): void
            {
                $this
                    ->setTableName('Users')
                    ->addAttributes([
                        new Attribute('Id', ScalarAttributeTypeEnum::String, KeyTypeEnum::Hash),
                        new Attribute('Email', ScalarAttributeTypeEnum::String, KeyTypeEnum::Range),
                    ])
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

        $executor = new Executor($this->client, logger: $this->logger);
        $executor->execute($loader->getFixtures(), $loader->getTables());

        $response = $this->dynamoDbClient->describeTable([
            'TableName' => 'Users',
        ]);

        self::assertSame('Users', $response['Table']['TableName']);

        $response = $this->dynamoDbClient->scan([
            'TableName' => 'Users',
        ]);

        self::assertSame(2, $response['Count']);
    }
}
