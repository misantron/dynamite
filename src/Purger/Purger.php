<?php

declare(strict_types=1);

namespace Dynamite\Purger;

use Dynamite\Client\ClientInterface;

readonly class Purger implements PurgerInterface
{
    public function __construct(
        private ClientInterface $client,
    ) {
    }

    public function purge(array $fixtures, array $tables): void
    {
        foreach ($fixtures as $fixture) {
            $tableName = $fixture->getTableName();
            if ($tableName !== null) {
                $this->truncateData($tableName);
            }
        }

        foreach ($tables as $table) {
            $tableName = $table->getTableName();
            if ($tableName !== null) {
                $this->dropTable($tableName);
            }
        }
    }

    protected function truncateData(string $tableName): void
    {
        $this->client->truncateRecords($tableName);
    }

    protected function dropTable(string $tableName): void
    {
        $this->client->dropTable($tableName);
    }
}
