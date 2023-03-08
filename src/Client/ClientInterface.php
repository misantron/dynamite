<?php

declare(strict_types=1);

namespace Dynamite\Client;

use Dynamite\Schema\Record;
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

    public function createRecord(string $tableName, Record $record): void;

    /**
     * @param array<int, Record> $records
     */
    public function creatBatchRecords(string $tableName, array $records): void;

    public function truncateRecords(string $tableName): void;
}
