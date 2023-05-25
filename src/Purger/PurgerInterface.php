<?php

declare(strict_types=1);

namespace Dynamite\Purger;

use Dynamite\FixtureInterface;
use Dynamite\TableInterface;

interface PurgerInterface
{
    /**
     * @param list<FixtureInterface> $fixtures
     * @param list<TableInterface> $tables
     */
    public function purge(array $fixtures, array $tables): void;
}
