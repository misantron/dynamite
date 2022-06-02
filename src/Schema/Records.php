<?php

declare(strict_types=1);

namespace Dynamite\Schema;

use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\NotBlank;

final class Records
{
    #[NotBlank(message: 'Table name is not defined', allowNull: false)]
    private ?string $tableName = null;

    #[Count(
        min: 1,
        max: 100,
        minMessage: 'At least {{ limit }} record is required',
        maxMessage: 'Max batch size is {{ limit }} records'
    )]
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
