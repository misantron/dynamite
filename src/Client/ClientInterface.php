<?php

declare(strict_types=1);

namespace Dynamite\Client;

use Dynamite\Schema\Table;

/**
 * @phpstan-type AttributeValue array{
 *   S?: null|string,
 *   N?: null|string,
 *   B?: null|string,
 *   NULL?: null|bool,
 *   BOOL?: null|bool
 * }
 */
interface ClientInterface
{
    /**
     * @link https://docs.aws.amazon.com/amazondynamodb/latest/APIReference/API_BatchWriteItem.html
     */
    public const BATCH_MAX_SIZE = 25;

    public function createTable(Table $schema): void;

    public function dropTable(string $tableName): void;

    /**
     * @param array<string, AttributeValue> $record
     */
    public function createRecord(string $tableName, array $record): void;

    /**
     * @param array<int, array<string, AttributeValue>> $records
     */
    public function creatBatchRecords(string $tableName, array $records): void;

    public function truncateRecords(string $tableName): void;
}
