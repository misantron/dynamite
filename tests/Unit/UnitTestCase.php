<?php

declare(strict_types=1);

namespace Dynamite\Tests\Unit;

use Dynamite\Tests\DependencyMockTrait;
use PHPUnit\Framework\TestCase;

abstract class UnitTestCase extends TestCase
{
    use DependencyMockTrait;
}
