<?php

declare(strict_types=1);

namespace Dynamite\Query;

use AsyncAws\DynamoDb\DynamoDbClient;
use AsyncAws\DynamoDb\Input\BatchWriteItemInput;
use AsyncAws\DynamoDb\ValueObject\AttributeValue;
use AsyncAws\DynamoDb\ValueObject\DeleteRequest;
use AsyncAws\DynamoDb\ValueObject\PutRequest;
use AsyncAws\DynamoDb\ValueObject\WriteRequest;
use Psr\Log\LoggerInterface;

final class BatchWriteItems
{
    /**
     * @see https://docs.aws.amazon.com/amazondynamodb/latest/APIReference/API_BatchWriteItem.html
     */
    private const BATCH_MAX_SIZE = 25;

    public function __construct(
        private readonly DynamoDbClient $client,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @param array<int, array<string, AttributeValue>> $items
     */
    public function putItems(string $tableName, array $items): void
    {
        $this->batchWriteRequest(
            $tableName,
            $items,
            static fn (array $item): WriteRequest => new WriteRequest([
                'PutRequest' => new PutRequest([
                    'Item' => $item,
                ]),
            ]),
            function (string $tableName, int $batchNumber): void {
                $this->logger->debug('Data batch executed', [
                    'table' => $tableName,
                    'batch' => '#' . $batchNumber,
                ]);
            }
        );
    }

    /**
     * @param array<int, array<string, AttributeValue>> $keys
     */
    public function deleteItems(string $tableName, array $keys): void
    {
        $this->batchWriteRequest(
            $tableName,
            $keys,
            static fn (array $key): WriteRequest => new WriteRequest([
                'DeleteRequest' => new DeleteRequest([
                    'Key' => $key,
                ]),
            ]),
            function (string $tableName, int $batchNumber): void {
                $this->logger->debug('Data batch deleted', [
                    'table' => $tableName,
                    'batch' => '#' . $batchNumber,
                ]);
            }
        );
    }

    private function batchWriteRequest(
        string $tableName,
        array $items,
        \Closure $requestCallback,
        \Closure $logCallback
    ): void {
        $offset = $batchNumber = 0;

        while ($offset < \count($items)) {
            $batch = array_slice($items, $offset, self::BATCH_MAX_SIZE);

            $input = new BatchWriteItemInput([
                'RequestItems' => [
                    $tableName => array_map($requestCallback, $batch),
                ],
            ]);

            $this->client->batchWriteItem($input)->resolve();

            $offset += self::BATCH_MAX_SIZE;
            ++$batchNumber;

            $logCallback($tableName, $batchNumber);
        }
    }
}
