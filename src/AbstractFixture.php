<?php

declare(strict_types=1);

namespace Dynamite;

use Dynamite\Client\ClientInterface;
use Dynamite\Exception\ValidationException;
use Dynamite\Schema\Record;
use Dynamite\Schema\Records;
use Dynamite\Validator\ValidatorAwareTrait;
use Psr\Log\LoggerInterface;

abstract class AbstractFixture
{
    use TableTrait;
    use ValidatorAwareTrait;

    private Records $schema;

    /**
     * @param ?array<int, Record> $records
     */
    public function __construct(array $records = null)
    {
        $this->schema = new Records();

        if ($records !== null) {
            $this->addRecords($records);
        }
    }

    final public function load(ClientInterface $client, LoggerInterface $logger): void
    {
        $this->initialize();

        $violations = $this->validator->validate($this->schema);
        if ($violations->count() > 0) {
            throw new ValidationException($violations);
        }

        /** @var string $tableName */
        $tableName = $this->schema->getTableName();

        if ($this->schema->getCount() === 1) {
            $records = $this->schema->getRecords();
            $client->createRecord($tableName, $records[0]);

            $logger->debug('Single record loaded', [
                'table' => $tableName,
            ]);

            return;
        }

        $client->creatBatchRecords($tableName, $this->schema->getRecords());

        $logger->debug('Batch records loaded', [
            'table' => $tableName,
        ]);
    }

    protected function addRecord(Record $record): self
    {
        $this->schema->addRecord($record);

        return $this;
    }

    /**
     * @param array<int, Record> $records
     */
    protected function addRecords(array $records): self
    {
        foreach ($records as $record) {
            $this->schema->addRecord($record);
        }

        return $this;
    }

    abstract protected function configure(): void;
}
