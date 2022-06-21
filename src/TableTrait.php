<?php

declare(strict_types=1);

namespace Dynamite;

use AsyncAws\DynamoDb\Exception\ResourceNotFoundException;
use AsyncAws\DynamoDb\Input\DescribeTableInput;
use AsyncAws\DynamoDb\Result\DescribeTableOutput;

trait TableTrait
{
    private ?DescribeTableOutput $describeTableOutput = null;

    protected function setTableName(string $tableName): self
    {
        $this->schema->setTableName($tableName);

        return $this;
    }

    private function isTableExists(): bool
    {
        try {
            $this->getTableDescription();

            return true;
        } catch (ResourceNotFoundException) {
            return false;
        }
    }

    private function isGlobalSecondaryIndexExists(string $name): bool
    {
        $output = $this->getTableDescription();

        if ($output->getTable() === null) {
            return false;
        }

        foreach ($output->getTable()->getGlobalSecondaryIndexes() as $index) {
            if ($index->getIndexName() === $name) {
                return true;
            }
        }

        return false;
    }

    private function getTableDescription(): DescribeTableOutput
    {
        if ($this->describeTableOutput === null) {
            $input = [
                'TableName' => $this->schema->getTableName(),
            ];

            $output = $this->dynamoDbClient->describeTable(new DescribeTableInput($input));
            $output->resolve();

            $this->describeTableOutput = $output;
        }

        return $this->describeTableOutput;
    }
}
