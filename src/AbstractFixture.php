<?php

declare(strict_types=1);

namespace Dynamite;

use Dynamite\Client\ClientInterface;
use Dynamite\Exception\ValidationException;
use Dynamite\Schema\Records;
use Dynamite\Validator\ValidatorAwareTrait;
use Psr\Log\LoggerInterface;

/**
 * @phpstan-import-type AttributeValue from ClientInterface
 */
abstract class AbstractFixture
{
    use TableTrait;
    use ValidatorAwareTrait;

    private Records $schema;

    /**
     * @param ?array<int, array<string, AttributeValue>> $items
     */
    public function __construct(array $items = null)
    {
        $this->schema = new Records();

        if ($items !== null) {
            $this->addItems($items);
        }
    }

    /**
     * @param array<string, AttributeValue> $item
     */
    protected function addItem(array $item): self
    {
        $this->schema->addRecord($item);

        return $this;
    }

    /**
     * @param array<int, array<string, AttributeValue>> $items
     */
    protected function addItems(array $items): self
    {
        foreach ($items as $item) {
            $this->schema->addRecord($item);
        }

        return $this;
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

    abstract protected function configure(): void;
}
