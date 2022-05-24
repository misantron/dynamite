<?php

declare(strict_types=1);

namespace Dynamite;

use AsyncAws\DynamoDb\Input\ListTablesInput;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\Constraints\NotBlank;

trait TableTrait
{
    #[
        NotBlank(message: 'Table name must be specified'),
        Groups(['create', 'delete']),
        SerializedName('TableName')
    ]
    private string $tableName;

    protected function setTableName(string $tableName): self
    {
        $this->tableName = $tableName;

        return $this;
    }

    private function isTableExists(): bool
    {
        $input = new ListTablesInput([
            'ExclusiveStartTableName' => $this->tableName,
            'Limit' => 1,
        ]);

        $response = $this->dynamoDbClient->listTables($input);

        foreach ($response->getTableNames(true) as $name) {
            if ($this->tableName === $name) {
                return true;
            }
        }

        return false;
    }
}
