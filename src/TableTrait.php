<?php

declare(strict_types=1);

namespace Dynamite;

use AsyncAws\DynamoDb\Exception\ResourceNotFoundException;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\Constraints\NotBlank;

trait TableTrait
{
    #[
        NotBlank(message: 'Table name must be specified'),
        Groups(['create', 'update']),
        SerializedName('TableName')
    ]
    private string $tableName = '';

    protected function setTableName(string $tableName): self
    {
        $this->tableName = $tableName;

        return $this;
    }

    private function isTableExists(): bool
    {
        try {
            $response = $this->dynamoDbClient->describeTable(['TableName' => $this->tableName]);
            $response->resolve();

            return true;
        } catch (ResourceNotFoundException) {
            return false;
        }
    }
}
