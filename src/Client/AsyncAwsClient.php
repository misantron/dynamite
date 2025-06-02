<?php

declare(strict_types=1);

namespace Dynamite\Client;

use AsyncAws\DynamoDb\DynamoDbClient;
use AsyncAws\DynamoDb\Enum\KeyType;
use AsyncAws\DynamoDb\Exception\ResourceNotFoundException;
use AsyncAws\DynamoDb\Input\BatchWriteItemInput;
use AsyncAws\DynamoDb\Input\CreateTableInput;
use AsyncAws\DynamoDb\Input\DeleteTableInput;
use AsyncAws\DynamoDb\Input\DescribeTableInput;
use AsyncAws\DynamoDb\Input\PutItemInput;
use AsyncAws\DynamoDb\Input\ScanInput;
use AsyncAws\DynamoDb\ValueObject;
use AsyncAws\DynamoDb\ValueObject\AttributeValue;
use AsyncAws\DynamoDb\ValueObject\DeleteRequest;
use AsyncAws\DynamoDb\ValueObject\PutRequest;
use AsyncAws\DynamoDb\ValueObject\TableDescription;
use AsyncAws\DynamoDb\ValueObject\WriteRequest;
use Dynamite\Schema\Record;
use Dynamite\Schema\Table;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final readonly class AsyncAwsClient implements ClientInterface
{
    public function __construct(
        private DynamoDbClient $client,
        private NormalizerInterface $normalizer,
        private LoggerInterface $logger
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

    public function createRecord(string $tableName, Record $record): void
    {
        $input = new PutItemInput([
            'TableName' => $tableName,
            'Item' => $record->getValues(),
        ]);

        $this->client->putItem($input)->resolve();
    }

    /**
     * @param array<int, Record> $records
     */
    public function creatBatchRecords(string $tableName, array $records): void
    {
        $mapped = [];
        foreach ($records as $k => $record) {
            foreach ($record->getValues() as $name => $value) {
                $mapped[$k][$name] = new AttributeValue($value);
            }
        }

        $this->executePut($tableName, $mapped);
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

        // @codeCoverageIgnoreStart
        if (!$response->getTable() instanceof TableDescription) {
            return;
        }

        // @codeCoverageIgnoreEnd

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
            static fn (array $key): WriteRequest => new WriteRequest([
                'DeleteRequest' => new DeleteRequest([
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
            static fn (array $item): WriteRequest => new WriteRequest([
                'PutRequest' => new PutRequest([
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
            $input = new BatchWriteItemInput([
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
            if ($value->getKeyType() === KeyType::HASH) {
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
