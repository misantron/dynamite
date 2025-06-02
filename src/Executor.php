<?php

declare(strict_types=1);

namespace Dynamite;

use Dynamite\Client\ClientInterface;
use Dynamite\Purger\Purger;
use Dynamite\Purger\PurgerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class Executor
{
    private readonly PurgerInterface $purger;

    public function __construct(
        private readonly ClientInterface $client,
        ?PurgerInterface $purger = null,
        private readonly LoggerInterface $logger = new NullLogger()
    ) {
        $this->purger = $purger ?? new Purger($this->client);
    }

    /**
     * @param list<FixtureInterface> $fixtures
     * @param list<TableInterface> $tables
     */
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

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * @param list<FixtureInterface> $fixtures
     * @param list<TableInterface> $tables
     */
    protected function purge(array $fixtures, array $tables): void
    {
        $this->getPurger()->purge($fixtures, $tables);
    }

    protected function createTable(TableInterface $table): void
    {
        $table->create($this->client, $this->logger);
    }

    protected function loadFixture(FixtureInterface $fixture): void
    {
        $fixture->load($this->client, $this->logger);
    }
}
