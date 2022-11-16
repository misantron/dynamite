<?php

declare(strict_types=1);

namespace Dynamite\Tests\Unit\Validator\Constraints;

use Dynamite\Tests\Unit\UnitTestCase;
use Dynamite\Validator\Constraints\Projection;

class ProjectionTest extends UnitTestCase
{
    /**
     * @dataProvider validateDataProvider
     */
    public function testValidate(mixed $input, bool $expected): void
    {
        self::assertSame($expected, $this->createValidator()->validate($input, new Projection())->count() > 0);
    }

    /**
     * @return iterable<string, array<int, mixed>>
     */
    public function validateDataProvider(): iterable
    {
        yield 'null-value' => [
            null,
            false,
        ];
        yield 'empty-array' => [
            [],
            true,
        ];
        yield 'invalid-type' => [
            [
                'ProjectionType' => 'EXCLUDE',
            ],
            true,
        ];
        yield 'valid-value' => [
            [
                'ProjectionType' => 'KEYS_ONLY',
            ],
            false,
        ];
    }
}
