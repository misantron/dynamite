<?php

declare(strict_types=1);

namespace Dynamite\Purger;

use AsyncAws\DynamoDb\DynamoDbClient;
use AsyncAws\DynamoDb\Enum\KeyType;
use AsyncAws\DynamoDb\Exception\ResourceNotFoundException;
use AsyncAws\DynamoDb\Input\DeleteItemInput;
use AsyncAws\DynamoDb\Input\DeleteTableInput;
use AsyncAws\DynamoDb\Input\DescribeTableInput;
use AsyncAws\DynamoDb\Input\ScanInput;
use AsyncAws\DynamoDb\ValueObject\AttributeValue;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class Purger implements PurgerInterface
{
    public function __construct(
        private readonly DynamoDbClient $client,
        private readonly LoggerInterface $logger = new NullLogger()
    ) {
    }

    public function purge(array $fixtures, array $tables): void
    {
        foreach ($fixtures as $fixture) {
            $this->truncateData($fixture->getTableName());
        }

        foreach ($tables as $table) {
            $this->dropTable($table->getTableName());
        }
    }

    protected function truncateData(string $tableName): void
    {
        try {
            $response = $this->client->describeTable(
                new DescribeTableInput([
                    'TableName' => $tableName,
                ])
            );
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

        $response = $this->client->scan(
            new ScanInput([
                'TableName' => $tableName,
            ])
        );
        $response->resolve();

        if ($response->getCount() < 1) {
            $this->logger->debug('Table data truncate skipped - no data', [
                'table' => $tableName,
            ]);

            return;
        }

        foreach ($response->getItems() as $item) {
            $this->client->deleteItem(
                new DeleteItemInput([
                    'TableName' => $tableName,
                    'Key' => $this->getDeleteKey($primaryKey, $item),
                ])
            )->resolve();
        }

        $this->logger->debug('Table data truncated', [
            'table' => $tableName,
        ]);
    }

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
     * @param array<string, AttributeValue> $item
     * @return array<string, AttributeValue>
     */
    private function getDeleteKey(array $primaryKey, array $item): array
    {
        $key = [];
        foreach ($item as $name => $val) {
            if (!\in_array($name, $primaryKey, true)) {
                continue;
            }
            $key[$name] = $val;
        }

        return $key;
    }

    protected function dropTable(string $tableName): void
    {
        try {
            $this->client
                ->deleteTable(
                    new DeleteTableInput([
                        'TableName' => $tableName,
                    ])
                )
                ->resolve()
            ;

            $this->logger->debug('Table dropped', [
                'table' => $tableName,
            ]);
        } catch (ResourceNotFoundException) {
            // ignore a non-existent table exception
        }
    }
}
