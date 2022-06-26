<?php

declare(strict_types=1);

namespace Dynamite\Purger;

use Dynamite\FixtureInterface;
use Dynamite\TableInterface;

interface PurgerInterface
{
    /**
     * @param array<string, FixtureInterface> $fixtures
     * @param array<string, TableInterface> $tables
     */
    public function purge(array $fixtures, array $tables): void;
}
