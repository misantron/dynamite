<?php

declare(strict_types=1);

namespace Dynamite\Tests\Unit;

use Dynamite\Loader;
use Dynamite\Tests\Fixtures\Fixtures\Domain\Table2DomainDataLoader;
use Dynamite\Tests\Fixtures\Tables\Table1;

class LoaderTest extends UnitTestCase
{
    public function testLoadFromDirectoryWithInvalidPath(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid directory path: test');

        $loader = new Loader($this->createValidator(), $this->createSerializer());
        $loader->loadFromDirectory('test');
    }

    public function testLoadFromDirectory(): void
    {
        $path = realpath(__DIR__ . '/../Fixtures');
        if ($path === false) {
            self::fail('Unable to load fixtures');
        }

        $loader = new Loader($this->createValidator(), $this->createSerializer());
        $loader->loadFromDirectory($path);

        $this->assertCount(2, $loader->getTables());
        $this->assertCount(3, $loader->getFixtures());
    }

    public function testAddTableDuplication(): void
    {
        $loader = new Loader($this->createValidator(), $this->createSerializer());
        $loader->addTable(new Table1());
        $loader->addTable(new Table1());

        $this->assertCount(1, $loader->getTables());
        $this->assertCount(0, $loader->getFixtures());
    }

    public function testAddTable(): void
    {
        $loader = new Loader($this->createValidator(), $this->createSerializer());
        $loader->addTable(new Table1());

        $this->assertCount(1, $loader->getTables());
        $this->assertCount(1, $loader->getTables(['group1']));
        $this->assertCount(0, $loader->getTables(['group2']));
        $this->assertCount(0, $loader->getFixtures());
    }

    public function testAddFixtureDuplication(): void
    {
        $loader = new Loader($this->createValidator(), $this->createSerializer());
        $loader->addFixture(new Table2DomainDataLoader());
        $loader->addFixture(new Table2DomainDataLoader());

        $this->assertCount(0, $loader->getTables());
        $this->assertCount(1, $loader->getFixtures());
    }

    public function testAddFixture(): void
    {
        $loader = new Loader($this->createValidator(), $this->createSerializer());
        $loader->addFixture(new Table2DomainDataLoader());

        $this->assertCount(0, $loader->getTables());
        $this->assertCount(1, $loader->getFixtures());
        $this->assertCount(0, $loader->getFixtures(['group1']));
        $this->assertCount(1, $loader->getFixtures(['group2']));
    }
}
