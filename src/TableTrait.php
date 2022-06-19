<?php

declare(strict_types=1);

namespace Dynamite;

use AsyncAws\DynamoDb\Exception\ResourceNotFoundException;
use AsyncAws\DynamoDb\Input\DescribeTableInput;

trait TableTrait
{
    protected function setTableName(string $tableName): self
    {
        $this->schema->setTableName($tableName);

        return $this;
    }

    private function isTableExists(): bool
    {
        try {
            $input = [
                'TableName' => $this->schema->getTableName(),
            ];

            $this->dynamoDbClient->describeTable(new DescribeTableInput($input))->resolve();

            return true;
        } catch (ResourceNotFoundException) {
            return false;
        }
    }
}
