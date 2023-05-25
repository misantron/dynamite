<?php

declare(strict_types=1);

namespace Dynamite\Tests\Fixtures\Tables;

use Dynamite\AbstractTable;
use Dynamite\Attribute\Groups;
use Dynamite\Enum\KeyTypeEnum;
use Dynamite\Enum\ScalarAttributeTypeEnum;
use Dynamite\Schema\Attribute;
use Dynamite\TableInterface;

#[Groups(['group1'])]
final class Table1 extends AbstractTable implements TableInterface
{
    protected function configure(): void
    {
        $this
            ->setTableName('Table1')
            ->addAttributes([
                new Attribute('Column1', ScalarAttributeTypeEnum::String, KeyTypeEnum::Hash),
            ])
            ->setProvisionedThroughput(1, 1)
        ;
    }
}
