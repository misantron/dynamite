<?php

declare(strict_types=1);

namespace Dynamite;

use AsyncAws\DynamoDb\DynamoDbClient;
use Dynamite\Purger\Purger;
use Dynamite\Purger\PurgerInterface;

class Executor
{
    private readonly PurgerInterface $purger;

    public function __construct(
        private readonly DynamoDbClient $client,
        PurgerInterface $purger = null
    ) {
        $this->purger = $purger ?? $this->createDefaultPurger();
    }

    public function execute(array $fixtures, array $tables): void
    {
        $this->purge($fixtures, $tables);

        foreach ($tables as $table) {
            $this->createTable($table);
        }
        foreach ($fixtures as $fixture) {
            $this->loadFixture($fixture);
        }
    }

    public function getPurger(): PurgerInterface
    {
        return $this->purger;
    }

    protected function purge(array $fixtures, array $tables): void
    {
        $this->getPurger()->purge($fixtures, $tables);
    }

    protected function createTable(TableInterface $table): void
    {
        $table->create($this->client);
    }

    protected function loadFixture(FixtureInterface $fixture): void
    {
        $fixture->load($this->client);
    }

    private function createDefaultPurger(): PurgerInterface
    {
        return new Purger($this->client);
    }
}
