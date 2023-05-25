<?php

declare(strict_types=1);

namespace Dynamite\Tests\Integration;

use Dynamite\AbstractFixture;
use Dynamite\AbstractTable;
use Dynamite\Client\ClientInterface;
use Dynamite\Enum\KeyTypeEnum;
use Dynamite\Enum\ProjectionTypeEnum;
use Dynamite\Enum\ScalarAttributeTypeEnum;
use Dynamite\FixtureInterface;
use Dynamite\Schema\Attribute;
use Dynamite\Schema\Record;
use Dynamite\TableInterface;
use Dynamite\Tests\DependencyMockTrait;
use Faker\Factory;
use Faker\Generator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\ErrorHandler\BufferingLogger;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @phpstan-import-type AttributeValue from ClientInterface
 */
abstract class IntegrationTestCase extends TestCase
{
    use DependencyMockTrait;

    public const TABLE_NAME = 'Users';

    protected ClientInterface $client;

    protected NormalizerInterface $serializer;

    protected ValidatorInterface $validator;

    protected BufferingLogger $logger;

    protected function setUp(): void
    {
        $this->onSetUp();

        $this->serializer = $this->createSerializer();
        $this->validator = $this->createValidator();
        $this->logger = $this->createTestLogger();
        $this->client = $this->createClient();
    }

    protected function tearDown(): void
    {
        $this->client->dropTable(self::TABLE_NAME);
        $this->client->dropTable('Table1');
        $this->client->dropTable('Table2');

        $this->logger->cleanLogs();
    }

    abstract protected function onSetUp(): void;

    abstract protected function createDynamoDbClient(): mixed;

    abstract protected function createClient(): ClientInterface;

    abstract protected function createTable(): void;

    protected function createFixtureTable(): TableInterface
    {
        return new class() extends AbstractTable implements TableInterface {
            protected function configure(): void
            {
                $this
                    ->setTableName(IntegrationTestCase::TABLE_NAME)
                    ->addAttributes([
                        new Attribute('Id', ScalarAttributeTypeEnum::String, KeyTypeEnum::Hash),
                        new Attribute('Email', ScalarAttributeTypeEnum::String),
                    ])
                    ->addGlobalSecondaryIndex('Emails', ProjectionTypeEnum::KeysOnly, 'Email')
                    ->setProvisionedThroughput(1, 1)
                ;
            }
        };
    }

    /**
     * @param array<int, Record> $items
     */
    protected function createFixture(array $items): FixtureInterface
    {
        return new class($items) extends AbstractFixture implements FixtureInterface {
            protected function configure(): void
            {
                $this->setTableName(IntegrationTestCase::TABLE_NAME);
            }
        };
    }

    /**
     * @return array{
     *     TableName: string,
     *     AttributeDefinitions: array<int, array{AttributeName: string, AttributeType: string}>,
     *     KeySchema: array<int, array{AttributeName: string, KeyType: string}>,
     *     GlobalSecondaryIndexes: array<int, mixed>,
     *     ProvisionedThroughput: array{ReadCapacityUnits: int, WriteCapacityUnits: int}
     * }
     */
    protected function getFixtureTableSchema(): array
    {
        return [
            'TableName' => self::TABLE_NAME,
            'AttributeDefinitions' => [
                [
                    'AttributeName' => 'Id',
                    'AttributeType' => ScalarAttributeTypeEnum::String->value,
                ],
                [
                    'AttributeName' => 'Email',
                    'AttributeType' => ScalarAttributeTypeEnum::String->value,
                ],
            ],
            'KeySchema' => [
                [
                    'AttributeName' => 'Id',
                    'KeyType' => KeyTypeEnum::Hash->value,
                ],
            ],
            'GlobalSecondaryIndexes' => [
                [
                    'IndexName' => 'Emails',
                    'Projection' => [
                        'ProjectionType' => ProjectionTypeEnum::KeysOnly->value,
                    ],
                    'KeySchema' => [
                        [
                            'AttributeName' => 'Email',
                            'KeyType' => KeyTypeEnum::Hash->value,
                        ],
                    ],
                    'ProvisionedThroughput' => [
                        'ReadCapacityUnits' => 1,
                        'WriteCapacityUnits' => 1,
                    ],
                ],
            ],
            'ProvisionedThroughput' => [
                'ReadCapacityUnits' => 1,
                'WriteCapacityUnits' => 1,
            ],
        ];
    }

    protected function createFakerFactory(): Generator
    {
        return Factory::create();
    }
}
