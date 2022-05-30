<?php

declare(strict_types=1);

namespace Dynamite\Tests\Unit\Helper;

use Dynamite\Helper\Finder;
use Dynamite\MigrationInterface;
use Dynamite\SeederInterface;
use Dynamite\Tests\Unit\UnitTestCase;

class FinderTest extends UnitTestCase
{
    public function testGetSeederClasses(): void
    {
        $finder = new Finder(realpath(__DIR__ . '/../../resources/seeders'));
        $classes = $finder->getClasses(SeederInterface::class);

        self::assertCount(3, $classes);
    }

    public function testGetMigrationClasses(): void
    {
        $finder = new Finder(realpath(__DIR__ . '/../../resources/migrations'));
        $classes = $finder->getClasses(MigrationInterface::class);

        self::assertCount(2, $classes);
    }
}