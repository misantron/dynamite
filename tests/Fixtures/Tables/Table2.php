<?php

declare(strict_types=1);

namespace Dynamite\Tests\Fixtures\Tables;

use Dynamite\AbstractTable;
use Dynamite\TableInterface;

final class Table2 extends AbstractTable implements TableInterface
{
    protected function configure(): void
    {
        // no content
    }
}
