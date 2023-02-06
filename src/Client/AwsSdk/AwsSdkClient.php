<?php

declare(strict_types=1);

namespace Dynamite\Client\AwsSdk;

use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Exception\DynamoDbException;
use Dynamite\ClientInterface;
use Dynamite\Enum\KeyTypeEnum;
use Dynamite\Schema\Table;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class AwsSdkClient implements ClientInterface
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
            if ($e->getAwsErrorCode() === 'ResourceNotFoundException') {
                return;
            }
            throw $e;
        }

        $this->client->waitUntil('TableNotExists', [
            'TableName' => $tableName,
        ]);
    }

    public function createRecord(string $tableName, array $record): void
    {
        $this->client->putItem([
            'TableName' => $tableName,
            'Item' => $record,
        ]);
    }

    public function creatBatchRecords(string $tableName, array $records): void
    {
        BatchCommand::createPutCommand($this->client, $this->logger)
            ->execute($tableName, $records)
        ;
    }

    public function truncateRecords(string $tableName): void
    {
        try {
            $response = $this->client->describeTable([
                'TableName' => $tableName,
            ]);
        } catch (DynamoDbException $e) {
            if ($e->getAwsErrorCode() === 'ResourceNotFoundException') {
                return;
            }
            throw $e;
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

        BatchCommand::createDeleteCommand($this->client, $this->logger)
            ->execute($tableName, $keysToDelete)
        ;

        $this->logger->debug('Table data truncated', [
            'table' => $tableName,
        ]);
    }

    /**
     * @param array<int, string> $primaryKey
     * @param array<string, mixed> $item
     * @return array<string, mixed>
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
