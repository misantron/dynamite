<?php

declare(strict_types=1);

namespace Dynamite\Client;

use AsyncAws\DynamoDb;
use AsyncAws\DynamoDb\Enum;
use AsyncAws\DynamoDb\Exception;
use AsyncAws\DynamoDb\Input;
use AsyncAws\DynamoDb\ValueObject;
use Dynamite\Schema\Table;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Normalizer;

/**
 * @phpstan-import-type AttributeValue from ClientInterface
 */
final class AsyncAwsClient implements ClientInterface
{
    public function __construct(
        private readonly DynamoDb\DynamoDbClient $client,
        private readonly Normalizer\NormalizerInterface $normalizer,
        private readonly LoggerInterface $logger
    ) {
    }

    public function createTable(Table $schema): void
    {
        $input = (array) $this->normalizer->normalize(
            $schema,
            context: [
                Normalizer\AbstractObjectNormalizer::SKIP_NULL_VALUES => true,
            ]
        );

        $this->client->createTable(new Input\CreateTableInput($input))->resolve();
    }

    public function dropTable(string $tableName): void
    {
        try {
            $input = new Input\DeleteTableInput([
                'TableName' => $tableName,
            ]);

            $this->client->deleteTable($input)->resolve();

            $this->logger->debug('Table dropped', [
                'table' => $tableName,
            ]);
        } catch (Exception\ResourceNotFoundException) {
            // ignore a non-existent table exception
        }
    }

    /**
     * @param array<string, AttributeValue> $record
     */
    public function createRecord(string $tableName, array $record): void
    {
        $input = new Input\PutItemInput([
            'TableName' => $tableName,
            'Item' => $record,
        ]);

        $this->client->putItem($input)->resolve();
    }

    /**
     * @param array<int, array<string, AttributeValue>> $records
     */
    public function creatBatchRecords(string $tableName, array $records): void
    {
        $mapped = [];
        foreach ($records as $k => $record) {
            foreach ($record as $name => $value) {
                $mapped[$k][$name] = new ValueObject\AttributeValue($value);
            }
        }

        $this->executePut($tableName, $mapped);
    }

    public function truncateRecords(string $tableName): void
    {
        try {
            $input = new Input\DescribeTableInput([
                'TableName' => $tableName,
            ]);

            $response = $this->client->describeTable($input);
            $response->resolve();
        } catch (Exception\ResourceNotFoundException) {
            return;
        }

        if ($response->getTable() === null) {
            return;
        }

        $primaryKey = $this->getPrimaryKeyAttributes(
            $response->getTable()->getKeySchema()
        );

        $input = new Input\ScanInput([
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

        $this->executeBatchDelete($tableName, $keysToDelete);

        $this->logger->debug('Table data truncated', [
            'table' => $tableName,
        ]);
    }

    /**
     * @param array<int, array<string, ValueObject\AttributeValue>> $keys
     */
    private function executeBatchDelete(string $tableName, array $keys): void
    {
        $this->batchWriteRequest(
            $tableName,
            $keys,
            static fn (array $key): ValueObject\WriteRequest => new ValueObject\WriteRequest([
                'DeleteRequest' => new ValueObject\DeleteRequest([
                    'Key' => $key,
                ]),
            ]),
            'Data batch deleted'
        );
    }

    /**
     * @param array<int, array<string, ValueObject\AttributeValue>> $items
     */
    private function executePut(string $tableName, array $items): void
    {
        $this->batchWriteRequest(
            $tableName,
            $items,
            static fn (array $item): ValueObject\WriteRequest => new ValueObject\WriteRequest([
                'PutRequest' => new ValueObject\PutRequest([
                    'Item' => $item,
                ]),
            ]),
            'Data batch executed'
        );
    }

    /**
     * @param array<int, array<string, ValueObject\AttributeValue>> $items
     */
    private function batchWriteRequest(
        string $tableName,
        array $items,
        callable $mappingCallback,
        string $logMessage
    ): void {
        $chunks = array_chunk($items, self::BATCH_MAX_SIZE);

        foreach ($chunks as $number => $chunk) {
            $input = new Input\BatchWriteItemInput([
                'RequestItems' => [
                    $tableName => array_map($mappingCallback, $chunk),
                ],
            ]);

            $this->client->batchWriteItem($input)->resolve();

            $this->logger->debug($logMessage, [
                'table' => $tableName,
                'batch' => '#' . ($number + 1),
            ]);
        }
    }

    /**
     * @param array<int, ValueObject\KeySchemaElement> $keySchema
     * @return array<int, string>
     */
    private function getPrimaryKeyAttributes(array $keySchema): array
    {
        $attributes = [];
        foreach ($keySchema as $value) {
            if ($value->getKeyType() === Enum\KeyType::HASH) {
                $attributes[] = $value->getAttributeName();
            }
        }
        return $attributes;
    }

    /**
     * @param array<int, string> $primaryKey
     * @param array<string, ValueObject\AttributeValue> $item
     * @return array<string, ValueObject\AttributeValue>
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
