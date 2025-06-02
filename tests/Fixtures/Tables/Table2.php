<?php

declare(strict_types=1);

namespace Dynamite\Tests\Fixtures\Tables;

use Dynamite\AbstractTable;
use Dynamite\Attribute\Groups;
use Dynamite\Enum\KeyType;
use Dynamite\Enum\ScalarAttributeType;
use Dynamite\Schema\Attribute;
use Dynamite\TableInterface;

#[Groups(['group2'])]
final class Table2 extends AbstractTable implements TableInterface
{
    protected function configure(): void
    {
        $this
            ->setTableName('Table2')
            ->addAttributes([
                new Attribute('Column1', ScalarAttributeType::String, KeyType::Hash),
            ])
            ->setProvisionedThroughput(1, 1)
        ;
    }
}
