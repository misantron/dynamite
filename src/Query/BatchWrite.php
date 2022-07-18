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

final class BatchWrite
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
        $offset = $batchNumber = 0;

        while ($offset < \count($items)) {
            $batch = array_slice($items, $offset, self::BATCH_MAX_SIZE);

            $input = new BatchWriteItemInput([
                'RequestItems' => [
                    $tableName => array_map(
                        static fn (array $item): WriteRequest => new WriteRequest([
                            'PutRequest' => new PutRequest([
                                'Item' => $item,
                            ]),
                        ]),
                        $batch
                    ),
                ],
            ]);

            $this->client->batchWriteItem($input)->resolve();

            $offset += self::BATCH_MAX_SIZE;
            ++$batchNumber;

            $this->logger->debug('Data batch executed', [
                'table' => $tableName,
                'batch' => '#' . $batchNumber,
            ]);
        }
    }

    /**
     * @param array<int, array<string, AttributeValue>> $keys
     */
    public function deleteItems(string $tableName, array $keys): void
    {
        $offset = $batchNumber = 0;

        while ($offset < \count($keys)) {
            $batch = array_slice($keys, $offset, self::BATCH_MAX_SIZE);

            $input = new BatchWriteItemInput([
                'RequestItems' => [
                    $tableName => array_map(
                        static fn (array $key): WriteRequest => new WriteRequest([
                            'DeleteRequest' => new DeleteRequest([
                                'Key' => $key,
                            ]),
                        ]),
                        $batch
                    ),
                ],
            ]);

            $this->client->batchWriteItem($input)->resolve();

            $offset += self::BATCH_MAX_SIZE;
            ++$batchNumber;

            $this->logger->debug('Data batch deleted', [
                'table' => $tableName,
                'batch' => '#' . $batchNumber,
            ]);
        }
    }
}
