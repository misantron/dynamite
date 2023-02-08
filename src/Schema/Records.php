<?php

declare(strict_types=1);

namespace Dynamite\Schema;

use Dynamite\Client\ClientInterface;
use Dynamite\Validator\Constraints as Assert;

/**
 * @phpstan-import-type AttributeValue from ClientInterface
 */
final class Records
{
    #[Assert\TableOrIndexName]
    private ?string $tableName = null;

    /**
     * @var array<int, array<string, AttributeValue>>
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
     * @param array<string, AttributeValue> $record
     */
    public function addRecord(array $record): void
    {
        $this->records[] = $record;
    }

    /**
     * @return array<int, array<string, AttributeValue>>
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
