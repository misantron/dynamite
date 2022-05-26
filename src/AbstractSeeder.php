<?php

declare(strict_types=1);

namespace Dynamite;

use AsyncAws\DynamoDb\DynamoDbClient;
use AsyncAws\DynamoDb\Input\BatchWriteItemInput;
use AsyncAws\DynamoDb\Input\PutItemInput;
use AsyncAws\DynamoDb\ValueObject\AttributeValue;
use AsyncAws\DynamoDb\ValueObject\PutRequest;
use AsyncAws\DynamoDb\ValueObject\WriteRequest;
use Dynamite\Exception\TableException;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class AbstractSeeder implements SeederInterface
{
    use TableTrait;

    #[Count(
        min: 1,
        max: 100,
        minMessage: 'At least {{ limit }} record is required',
        maxMessage: 'Max batch size is {{ limit }} records'
    )]
    private array $items;

    public function __construct(
        private readonly DynamoDbClient $dynamoDbClient,
        private readonly ValidatorInterface $validator
    ) {
        $this->items = [];
    }

    /**
     * @param array<int, array> $items
     */
    protected function addItems(array $items): self
    {
        foreach ($items as $item) {
            $this->items[] = $item;
        }

        return $this;
    }

    /**
     * @param array<string, AttributeValue> $item
     */
    protected function addItem(array $item): self
    {
        $this->items[] = $item;

        return $this;
    }

    protected function save(): array
    {
        $violations = $this->validator->validate($this);
        if (\count($violations) > 0) {
            throw new ValidationFailedException('', $violations);
        }

        if (!$this->isTableExists()) {
            throw TableException::notExists($this->tableName);
        }

        if ($this->isSingleRequest()) {
            $input = new PutItemInput([
                'TableName' => $this->tableName,
                'Item' => current($this->items),
            ]);

            $response = $this->dynamoDbClient->putItem($input);
            $response->resolve();

            return $response->info();
        }

        $input = new BatchWriteItemInput([
            'RequestItems' => [
                $this->tableName => array_map(
                    static fn (array $item): WriteRequest => new WriteRequest([
                        'PutRequest' => new PutRequest([
                            'Item' => $item,
                        ]),
                    ]),
                    $this->items
                ),
            ],
        ]);

        $response = $this->dynamoDbClient->batchWriteItem($input);
        $response->resolve();

        return $response->info();
    }

    private function isSingleRequest(): bool
    {
        return \count($this->items) === 1;
    }
}
