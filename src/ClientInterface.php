<?php

declare(strict_types=1);

namespace Dynamite;

use Dynamite\Schema\Table;

interface ClientInterface
{
    public function createTable(Table $schema): void;

    public function dropTable(string $tableName): void;

    /**
     * @param array<string, array<string, string>> $record
     */
    public function createRecord(string $tableName, array $record): void;

    /**
     * @param array<int, array<string, array<string, string>>> $records
     */
    public function creatBatchRecords(string $tableName, array $records): void;

    public function truncateRecords(string $tableName): void;
}
