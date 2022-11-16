<?php

declare(strict_types=1);

namespace Dynamite;

use AsyncAws\DynamoDb\DynamoDbClient;
use AsyncAws\DynamoDb\Input\PutItemInput;
use Dynamite\Exception\ValidationException;
use Dynamite\Query\BatchWriteItems;
use Dynamite\Schema\Records;
use Dynamite\Validator\ValidatorAwareTrait;
use Psr\Log\LoggerInterface;

abstract class AbstractFixture
{
    use TableTrait;
    use ValidatorAwareTrait;

    private Records $schema;

    public function __construct()
    {
        $this->schema = new Records();
    }

    /**
     * @param array<string, array<string, string>> $item
     */
    protected function addItem(array $item): self
    {
        $this->schema->addRecord($item);

        return $this;
    }

    /**
     * @param array<int, array<string, array<string, string>>> $items
     */
    protected function addItems(array $items): self
    {
        foreach ($items as $item) {
            $this->schema->addRecord($item);
        }

        return $this;
    }

    final public function load(DynamoDbClient $client, LoggerInterface $logger): void
    {
        $this->initialize();

        $violations = $this->validator->validate($this->schema);
        if ($violations->count() > 0) {
            throw new ValidationException($violations);
        }

        /** @var string $tableName */
        $tableName = $this->schema->getTableName();

        if ($this->schema->isSingleRecord()) {
            $input = new PutItemInput([
                'TableName' => $tableName,
                'Item' => current($this->schema->getRecords()),
            ]);

            $client->putItem($input)->resolve();

            $logger->debug('Single record loaded', [
                'table' => $tableName,
            ]);

            return;
        }

        $query = new BatchWriteItems($client, $logger);
        $query->putItems($tableName, $this->schema->getRecords());

        $logger->debug('Batch records loaded', [
            'table' => $tableName,
        ]);
    }

    abstract protected function configure(): void;
}
