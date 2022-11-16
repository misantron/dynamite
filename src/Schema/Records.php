<?php

declare(strict_types=1);

namespace Dynamite\Schema;

use Dynamite\Validator\Constraints as Assert;

final class Records
{
    #[Assert\TableOrIndexName]
    private ?string $tableName = null;

    /**
     * @var array<int, array<string, array<string, string>>>
     */
    #[Assert\Records]
    private array $records = [];

    public function setTableName(string $tableName): void
    {
        $this->tableName = $tableName;
    }

    public function getTableName(): ?string
    {
        return $this->tableName;
    }

    /**
     * @param array<string, array<string, string>> $record
     */
    public function addRecord(array $record): void
    {
        $this->records[] = $record;
    }

    /**
     * @return array<int, array<string, array<string, string>>>
     */
    public function getRecords(): array
    {
        return $this->records;
    }

    public function isSingleRecord(): bool
    {
        return \count($this->records) === 1;
    }
}
