<?php

declare(strict_types=1);

namespace Dynamite;

use AsyncAws\DynamoDb\DynamoDbClient;
use AsyncAws\DynamoDb\Input\BatchWriteItemInput;
use AsyncAws\DynamoDb\Input\PutItemInput;
use AsyncAws\DynamoDb\ValueObject\PutRequest;
use AsyncAws\DynamoDb\ValueObject\WriteRequest;
use Dynamite\Exception\TableException;
use Dynamite\Exception\ValidationException;
use Dynamite\Schema\Records;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class AbstractSeeder implements SeederInterface
{
    use TableTrait;

    private Records $schema;

    public function __construct(
        private readonly DynamoDbClient $dynamoDbClient,
        private readonly ValidatorInterface $validator
    ) {
        $this->schema = new Records();
    }

    /**
     * @param array<int, array> $items
     */
    protected function addItems(array $items): self
    {
        foreach ($items as $item) {
            $this->schema->addRecord($item);
        }

        return $this;
    }

    /**
     * @param array<string, array<string, string>> $item
     */
    protected function addItem(array $item): self
    {
        $this->schema->addRecord($item);

        return $this;
    }

    protected function save(): array
    {
        $violations = $this->validator->validate($this->schema);
        if (\count($violations) > 0) {
            throw new ValidationException($violations);
        }

        if (!$this->isTableExists()) {
            throw TableException::notExists($this->schema->getTableName());
        }

        if ($this->schema->isSingleRecord()) {
            $input = new PutItemInput([
                'TableName' => $this->schema->getTableName(),
                'Item' => current($this->schema->getRecords()),
            ]);

            $response = $this->dynamoDbClient->putItem($input);
            $response->resolve();

            return $response->info();
        }

        $input = new BatchWriteItemInput([
            'RequestItems' => [
                $this->schema->getTableName() => array_map(
                    static fn (array $item): WriteRequest => new WriteRequest([
                        'PutRequest' => new PutRequest([
                            'Item' => $item,
                        ]),
                    ]),
                    $this->schema->getRecords()
                ),
            ],
        ]);

        $response = $this->dynamoDbClient->batchWriteItem($input);
        $response->resolve();

        return $response->info();
    }
}
