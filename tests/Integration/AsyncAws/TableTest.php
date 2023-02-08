<?php

declare(strict_types=1);

namespace Dynamite\Tests\Integration\AsyncAws;

use Dynamite\AbstractTable;
use Dynamite\Enum\KeyTypeEnum;
use Dynamite\Enum\ProjectionTypeEnum;
use Dynamite\Enum\ScalarAttributeTypeEnum;
use Dynamite\Schema\Attribute;
use Dynamite\TableInterface;
use Dynamite\Tests\Integration\IntegrationTestCase;
use Dynamite\Tests\Integration\AsyncAwsIntegrationTrait;

class TableTest extends IntegrationTestCase
{
    use AsyncAwsIntegrationTrait;

    public function testCreate(): void
    {
        $table = new class() extends AbstractTable implements TableInterface {
            protected function configure(): void
            {
                $this
                    ->setTableName('Users')
                    ->addAttributes([
                        new Attribute('Id', ScalarAttributeTypeEnum::String, KeyTypeEnum::Hash),
                        new Attribute('Email', ScalarAttributeTypeEnum::String),
                    ])
                    ->addGlobalSecondaryIndex('Emails', ProjectionTypeEnum::KeysOnly, 'Email')
                    ->setProvisionedThroughput(1, 1)
                ;
            }
        };
        $table->setValidator($this->validator);
        $table->setNormalizer($this->serializer);

        $table->create($this->client, $this->logger);

        $response = $this->dynamoDbClient->tableExists([
            'TableName' => 'Users',
        ]);
        $response->resolve();

        self::assertTrue($response->isSuccess());

        $response = $this->dynamoDbClient->describeTable([
            'TableName' => 'Users',
        ]);
        $response->resolve();

        self::assertSame('Users', $response->getTable()?->getTableName());
    }
}
