<?php

declare(strict_types=1);

namespace Dynamite\Tests\Fixtures\Tables;

use Dynamite\AbstractTable;
use Dynamite\Attribute\Groups;
use Dynamite\TableInterface;

#[Groups(['group1'])]
final class Table1 extends AbstractTable implements TableInterface
{
    protected function configure(): void
    {
        // no content
    }
}
