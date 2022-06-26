<?php

declare(strict_types=1);

namespace Dynamite\Tests\Unit;

use Dynamite\Loader;
use Dynamite\Tests\Fixtures\Fixtures\Domain\Table2DomainDataLoader;
use Dynamite\Tests\Fixtures\Tables\Table1;
use Dynamite\Tests\Integration\IntegrationTestCase;

class LoaderTest extends IntegrationTestCase
{
    public function testLoadFromDirectoryWithInvalidPath(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid directory path: test');

        $loader = new Loader($this->validator, $this->serializer);
        $loader->loadFromDirectory('test');
    }

    public function testLoadFromDirectory(): void
    {
        $loader = new Loader($this->validator, $this->serializer);
        $loader->loadFromDirectory(realpath(__DIR__ . '/../Fixtures'));

        self::assertCount(2, $loader->getTables());
        self::assertCount(3, $loader->getFixtures());
    }

    public function testAddTableDuplication(): void
    {
        $loader = new Loader($this->validator, $this->serializer);
        $loader->addTable(new Table1());
        $loader->addTable(new Table1());

        self::assertCount(1, $loader->getTables());
        self::assertCount(0, $loader->getFixtures());
    }

    public function testAddTable(): void
    {
        $loader = new Loader($this->validator, $this->serializer);
        $loader->addTable(new Table1());

        self::assertCount(1, $loader->getTables());
        self::assertCount(0, $loader->getFixtures());
    }

    public function testAddFixtureDuplication(): void
    {
        $loader = new Loader($this->validator, $this->serializer);
        $loader->addFixture(new Table2DomainDataLoader());
        $loader->addFixture(new Table2DomainDataLoader());

        self::assertCount(0, $loader->getTables());
        self::assertCount(1, $loader->getFixtures());
    }

    public function testAddFixture(): void
    {
        $loader = new Loader($this->validator, $this->serializer);
        $loader->addFixture(new Table2DomainDataLoader());

        self::assertCount(0, $loader->getTables());
        self::assertCount(1, $loader->getFixtures());
    }
}
