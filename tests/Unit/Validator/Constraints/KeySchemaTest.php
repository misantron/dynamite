<?php

declare(strict_types=1);

namespace Dynamite\Tests\Unit\Validator\Constraints;

use Dynamite\Tests\Unit\UnitTestCase;
use Dynamite\Validator\Constraints\KeySchema;

class KeySchemaTest extends UnitTestCase
{
    /**
     * @dataProvider validateDataProvider
     */
    public function testValidate(mixed $input, bool $expected): void
    {
        $entity = new class() {
            #[KeySchema]
            public ?array $keySchema = null;
        };
        $entity->keySchema = $input;

        self::assertSame($expected, $this->createValidator()->validate($entity)->count() > 0);
    }

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
        yield 'invalid-schema' => [
            [
                'AttributeName' => 'Id',
                'KeyType' => 'HASH',
            ],
            true,
        ];
        yield 'invalid-key-type' => [
            [
                [
                    'AttributeName' => 'Id',
                    'KeyType' => 'GIN',
                ],
            ],
            true,
        ];
        yield 'valid-array' => [
            [
                [
                    'AttributeName' => 'Id',
                    'KeyType' => 'HASH',
                ],
            ],
            false,
        ];
    }
}
