<?php

declare(strict_types=1);

namespace Dynamite\Schema;

use Dynamite\Validator\Constraints as Assert;

final class Records
{
    #[Assert\TableOrIndexName]
    private ?string $tableName = null;

    #[Assert\Records]
    private array $records = [];

    public function setTableName(string $tableName): void
    {
        $this->tableName = $tableName;
    }

    public function getTableName(): string
    {
        return $this->tableName;
    }

    public function addRecord(array $record): void
    {
        $this->records[] = $record;
    }

    public function getRecords(): array
    {
        return $this->records;
    }

    public function isSingleRecord(): bool
    {
        return \count($this->records) === 1;
    }
}
