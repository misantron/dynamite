<?php

declare(strict_types=1);

namespace Dynamite\Tests\Fixtures\Fixtures\Domain;

use Dynamite\AbstractFixture;
use Dynamite\Attribute\Groups;
use Dynamite\FixtureInterface;

#[Groups(['group1'])]
final class Table1DomainDataLoader extends AbstractFixture implements FixtureInterface
{
    protected function configure(): void
    {
        // no content
    }
}
