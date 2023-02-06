<?php

declare(strict_types=1);

namespace Dynamite\Client\AsyncAws;

use AsyncAws\DynamoDb\DynamoDbClient;
use AsyncAws\DynamoDb\Enum\KeyType;
use AsyncAws\DynamoDb\Exception\ResourceNotFoundException;
use AsyncAws\DynamoDb\Input\CreateTableInput;
use AsyncAws\DynamoDb\Input\DeleteTableInput;
use AsyncAws\DynamoDb\Input\DescribeTableInput;
use AsyncAws\DynamoDb\Input\PutItemInput;
use AsyncAws\DynamoDb\Input\ScanInput;
use AsyncAws\DynamoDb\ValueObject\AttributeValue;
use AsyncAws\DynamoDb\ValueObject\KeySchemaElement;
use Dynamite\ClientInterface;
use Dynamite\Schema\Table;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class AsyncAwsClient implements ClientInterface
{
    public function __construct(
        private readonly DynamoDbClient $client,
        private readonly NormalizerInterface $normalizer,
        private readonly LoggerInterface $logger
    ) {
    }

    public function createTable(Table $schema): void
    {
        $input = (array) $this->normalizer->normalize(
            $schema,
            context: [
                AbstractObjectNormalizer::SKIP_NULL_VALUES => true,
            ]
        );

        $this->client->createTable(new CreateTableInput($input))->resolve();
    }

    public function dropTable(string $tableName): void
    {
        try {
            $input = new DeleteTableInput([
                'TableName' => $tableName,
            ]);

            $this->client->deleteTable($input)->resolve();

            $this->logger->debug('Table dropped', [
                'table' => $tableName,
            ]);
        } catch (ResourceNotFoundException) {
            // ignore a non-existent table exception
        }
    }

    /**
     * @param array<string, array<string, string>> $record
     */
    public function createRecord(string $tableName, array $record): void
    {
        $input = new PutItemInput([
            'TableName' => $tableName,
            'Item' => $record,
        ]);

        $this->client->putItem($input)->resolve();
    }

    /**
     * @param array<int, array<string, array<string, string>>> $records
     */
    public function creatBatchRecords(string $tableName, array $records): void
    {
        BatchCommand::createPutCommand($this->client, $this->logger)
            ->execute($tableName, $records)
        ;
    }

    public function truncateRecords(string $tableName): void
    {
        try {
            $input = new DescribeTableInput([
                'TableName' => $tableName,
            ]);

            $response = $this->client->describeTable($input);
            $response->resolve();
        } catch (ResourceNotFoundException) {
            return;
        }

        if ($response->getTable() === null) {
            return;
        }

        $primaryKey = $this->getPrimaryKeyAttributes(
            $response->getTable()->getKeySchema()
        );

        $input = new ScanInput([
            'TableName' => $tableName,
            'ConsistentRead' => true,
        ]);

        $response = $this->client->scan($input);
        $response->resolve();

        if ($response->getCount() < 1) {
            $this->logger->debug('Table data truncate skipped - no data', [
                'table' => $tableName,
            ]);

            return;
        }

        $keysToDelete = [];
        foreach ($response->getItems() as $item) {
            $keysToDelete[] = $this->getDeleteKey($primaryKey, $item);
        }

        BatchCommand::createDeleteCommand($this->client, $this->logger)
            ->execute($tableName, $keysToDelete)
        ;

        $this->logger->debug('Table data truncated', [
            'table' => $tableName,
        ]);
    }

    /**
     * @param array<int, KeySchemaElement> $keySchema
     * @return array<int, string>
     */
    private function getPrimaryKeyAttributes(array $keySchema): array
    {
        $attributes = [];
        foreach ($keySchema as $value) {
            if ($value->getKeyType() === KeyType::HASH) {
                $attributes[] = $value->getAttributeName();
            }
        }
        return $attributes;
    }

    /**
     * @param array<int, string> $primaryKey
     * @param array<string, AttributeValue> $item
     * @return array<string, AttributeValue>
     */
    private function getDeleteKey(array $primaryKey, array $item): array
    {
        return array_filter(
            $item,
            static fn (string $name) => \in_array($name, $primaryKey, true),
            ARRAY_FILTER_USE_KEY
        );
    }
}
