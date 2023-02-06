<?php

declare(strict_types=1);

namespace Dynamite\Client\AwsSdk;

use Aws\DynamoDb\DynamoDbClient;
use Dynamite\Client\BatchCommandInterface;
use Psr\Log\LoggerInterface;

final class BatchCommand implements BatchCommandInterface
{
    private ?string $tableName = null;

    private function __construct(
        private readonly DynamoDbClient $client,
        private readonly LoggerInterface $logger,
        private readonly string $type
    ) {
    }

    public static function createPutCommand(DynamoDbClient $client, LoggerInterface $logger): self
    {
        return new self($client, $logger, self::TYPE_PUT);
    }

    public static function createDeleteCommand(DynamoDbClient $client, LoggerInterface $logger): self
    {
        return new self($client, $logger, self::TYPE_DELETE);
    }

    /**
     * @param array<int, array<string, array<string, string>>> $items
     */
    public function execute(string $tableName, array $items): void
    {
        $this->tableName = $tableName;

        if ($this->type === self::TYPE_PUT) {
            $this->executePut($items);
        } elseif ($this->type === self::TYPE_DELETE) {
            $this->executeDelete($items);
        }
    }

    /**
     * @param array<int, array<string, array<string, string>>> $items
     */
    private function executePut(array $items): void
    {
        $this->batchWriteRequest(
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
     * @param array<int, array<string, array<string, string>>> $keys
     */
    private function executeDelete(array $keys): void
    {
        $this->batchWriteRequest(
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
     * @param array<int, array<string, array<string, string>>> $items
     */
    private function batchWriteRequest(
        array $items,
        \Closure $requestCallback,
        string $logMessage
    ): void {
        $chunks = array_chunk($items, self::BATCH_MAX_SIZE);

        foreach ($chunks as $number => $chunk) {
            $input = [
                'RequestItems' => [
                    $this->tableName => array_map($requestCallback, $chunk),
                ],
            ];

            $this->client->batchWriteItem($input);

            $this->logger->debug($logMessage, [
                'table' => $this->tableName,
                'batch' => '#' . ($number + 1),
            ]);
        }
    }
}
