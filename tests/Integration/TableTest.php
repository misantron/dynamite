<?php

declare(strict_types=1);

namespace Dynamite\Tests\Integration;

use AsyncAws\DynamoDb\Enum\ProjectionType;
use AsyncAws\DynamoDb\Enum\ScalarAttributeType;
use Dynamite\AbstractTable;
use Dynamite\TableInterface;
use Psr\Log\LogLevel;

class TableTest extends IntegrationTestCase
{
    public function testCreate(): void
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
                    ->addGlobalSecondaryIndex('Emails', ProjectionType::KEYS_ONLY, 'Email')
                    ->setProvisionedThroughput(1, 1)
                ;
            }
        };
        $table->setValidator($this->validator);
        $table->setNormalizer($this->serializer);

        $table->create($this->dynamoDbClient, $this->logger);

        self::assertTrue($this->logger->hasRecords(LogLevel::DEBUG));

        $response = $this->dynamoDbClient->tableExists([
            'TableName' => 'Users',
        ]);
        $response->resolve();

        self::assertTrue($response->isSuccess());

        $response = $this->dynamoDbClient->describeTable([
            'TableName' => 'Users',
        ]);
        $response->resolve();

        self::assertSame('Users', $response->getTable()->getTableName());
    }
}
