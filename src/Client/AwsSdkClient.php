<?php

declare(strict_types=1);

namespace Dynamite\Client;

use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Exception\DynamoDbException;
use Dynamite\Enum\KeyTypeEnum;
use Dynamite\Schema\Record;
use Dynamite\Schema\Table;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @phpstan-import-type AttributeValue from ClientInterface
 */
final class AwsSdkClient implements ClientInterface
{
    public const RESOURCE_NOT_FOUND_ERROR_CODE = 'ResourceNotFoundException';

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

        $this->client->createTable($input);

        $this->client->waitUntil('TableExists', [
            'TableName' => $schema->getTableName(),
        ]);
    }

    public function dropTable(string $tableName): void
    {
        try {
            $this->client->deleteTable([
                'TableName' => $tableName,
            ]);
        } catch (DynamoDbException $e) {
            if ($e->getAwsErrorCode() === self::RESOURCE_NOT_FOUND_ERROR_CODE) {
                return;
            }
            // @codeCoverageIgnoreStart
            throw $e;
            // @codeCoverageIgnoreEnd
        }

        $this->client->waitUntil('TableNotExists', [
            'TableName' => $tableName,
        ]);
    }

    public function createRecord(string $tableName, Record $record): void
    {
        $this->client->putItem([
            'TableName' => $tableName,
            'Item' => $record->getValues(),
        ]);
    }

    /**
     * @param array<int, Record> $records
     */
    public function creatBatchRecords(string $tableName, array $records): void
    {
        $mapped = array_map(
            static fn (Record $record) => $record->getValues(),
            $records
        );

        $this->executeBatchPut($tableName, $mapped);
    }

    public function truncateRecords(string $tableName): void
    {
        try {
            $response = $this->client->describeTable([
                'TableName' => $tableName,
            ]);
        } catch (DynamoDbException $e) {
            if ($e->getAwsErrorCode() === self::RESOURCE_NOT_FOUND_ERROR_CODE) {
                return;
            }
            // @codeCoverageIgnoreStart
            throw $e;
            // @codeCoverageIgnoreEnd
        }

        $primaryKey = $this->getPrimaryKeyAttributes($response['Table']['KeySchema'] ?? []);

        $response = $this->client->scan([
            'TableName' => $tableName,
            'ConsistentRead' => true,
        ]);

        if (($response['Count'] ?? 0) < 1) {
            $this->logger->debug('Table data truncate skipped - no data', [
                'table' => $tableName,
            ]);

            return;
        }

        $keysToDelete = [];
        foreach ($response['Items'] as $item) {
            $keysToDelete[] = $this->getDeleteKey($primaryKey, $item);
        }

        $this->executeBatchDelete($tableName, $keysToDelete);

        $this->logger->debug('Table data truncated', [
            'table' => $tableName,
        ]);
    }

    /**
     * @param array<int, array<string, AttributeValue>> $items
     */
    private function executeBatchPut(string $tableName, array $items): void
    {
        $this->batchWriteRequest(
            $tableName,
            $items,
            static fn (array $item): array => [
                'PutRequest' => [
                    'Item' => $item,
                ],
            ],
            'Data batch executed'
        );
    }

    /**
     * @param array<int, array<string, AttributeValue>> $keys
     */
    private function executeBatchDelete(string $tableName, array $keys): void
    {
        $this->batchWriteRequest(
            $tableName,
            $keys,
            static fn (array $key): array => [
                'DeleteRequest' => [
                    'Key' => $key,
                ],
            ],
            'Data batch deleted'
        );
    }

    /**
     * @param array<int, array<string, AttributeValue>> $items
     */
    private function batchWriteRequest(
        string $tableName,
        array $items,
        callable $mappingCallback,
        string $logMessage
    ): void {
        $chunks = array_chunk($items, self::BATCH_MAX_SIZE);

        foreach ($chunks as $number => $chunk) {
            $input = [
                'RequestItems' => [
                    $tableName => array_map($mappingCallback, $chunk),
                ],
            ];

            $this->client->batchWriteItem($input);

            $this->logger->debug($logMessage, [
                'table' => $tableName,
                'batch' => '#' . ($number + 1),
            ]);
        }
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

    /**
     * @param array<int, array{AttributeName: string, KeyType: string}> $keySchema
     * @return array<int, string>
     */
    private function getPrimaryKeyAttributes(array $keySchema): array
    {
        $attributes = [];
        foreach ($keySchema as $element) {
            if (KeyTypeEnum::tryFrom($element['KeyType']) === KeyTypeEnum::Hash) {
                $attributes[] = $element['AttributeName'];
            }
        }
        return $attributes;
    }
}
