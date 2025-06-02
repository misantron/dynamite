<?php

declare(strict_types=1);

namespace Dynamite\Tests\Unit\Validator\Constraints;

use Dynamite\Enum\ProjectionTypeEnum;
use Dynamite\Tests\Unit\UnitTestCase;
use Dynamite\Validator\Constraints\Projection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

#[Group('unit')]
class ProjectionTest extends UnitTestCase
{
    #[DataProvider('validateDataProvider')]
    public function testValidate(mixed $input, bool $expected): void
    {
        $this->assertSame($expected, $this->createValidator()->validate($input, new Projection())->count() > 0);
    }

    /**
     * @return iterable<string, array<int, mixed>>
     */
    public static function validateDataProvider(): iterable
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
                'ProjectionType' => ProjectionTypeEnum::KeysOnly,
            ],
            false,
        ];
    }
}
