<?php

declare(strict_types=1);

namespace Dynamite\Schema;

use Dynamite\Validator\Constraints as Assert;

final class Records
{
    #[Assert\TableOrIndexName]
    private ?string $tableName = null;

    /**
     * @var array<int, Record>
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

    public function addRecord(Record $record): void
    {
        $this->records[] = $record;
    }

    /**
     * @return array<int, Record>
     */
    public function getRecords(): array
    {
        return $this->records;
    }

    public function getCount(): int
    {
        return \count($this->records);
    }
}
