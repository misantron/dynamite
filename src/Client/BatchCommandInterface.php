<?php

declare(strict_types=1);

namespace Dynamite\Client;

interface BatchCommandInterface
{
    /**
     * @see https://docs.aws.amazon.com/amazondynamodb/latest/APIReference/API_BatchWriteItem.html
     */
    public const BATCH_MAX_SIZE = 25;

    public const TYPE_PUT = 'put';

    public const TYPE_DELETE = 'delete';

    /**
     * @param array<int, array<string, mixed>> $items
     */
    public function execute(string $tableName, array $items): void;
}
