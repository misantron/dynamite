<?php

declare(strict_types=1);

namespace Dynamite\Tests\Fixtures\Fixtures\Domain;

use Dynamite\AbstractFixture;
use Dynamite\Attribute\Groups;
use Dynamite\FixtureInterface;
use Dynamite\Schema\Record;
use Dynamite\Schema\Value;

#[Groups(['group2'])]
final class Table2DomainDataLoader extends AbstractFixture implements FixtureInterface
{
    protected function configure(): void
    {
        $this
            ->setTableName('Table2')
            ->addRecords([
                new Record([
                    Value::stringValue('Column1', 'e5502ec2-42a7-408b-9f03-f8e162b6257e'),
                ]),
                new Record([
                    Value::stringValue('Column1', 'f0cf458c-4fc0-4dd8-ba5b-eca6dba9be63'),
                ]),
            ])
        ;
    }
}
