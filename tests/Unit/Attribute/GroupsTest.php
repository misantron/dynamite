<?php

declare(strict_types=1);

namespace Dynamite\Tests\Unit\Attribute;

use Dynamite\Attribute\Groups;
use Dynamite\Tests\Fixtures\Fixtures\Domain\Table1DomainDataLoader;
use Dynamite\Tests\Fixtures\Tables\Table1;
use Dynamite\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

#[Group('unit')]
final class GroupsTest extends UnitTestCase
{
    public function testConstructor(): void
    {
        $groups = new Groups(['group1', 'group2']);

        self::assertSame(['group1', 'group2'], $groups->getNames());
    }

    public function testAttributeOnTableClass(): void
    {
        $reflectionClass = new \ReflectionClass(Table1::class);
        $attributes = $reflectionClass->getAttributes(Groups::class);

        self::assertCount(1, $attributes);
        self::assertSame(Groups::class, $attributes[0]->getName());
    }

    public function testAttributeOnFixtureClass(): void
    {
        $reflectionClass = new \ReflectionClass(Table1DomainDataLoader::class);
        $attributes = $reflectionClass->getAttributes(Groups::class);

        self::assertCount(1, $attributes);
        self::assertSame(Groups::class, $attributes[0]->getName());
    }
}
