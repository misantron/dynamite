<?php

declare(strict_types=1);

namespace Dynamite\Tests\Unit\Validator\Constraints;

use Dynamite\Enum\KeyType;
use Dynamite\Tests\Unit\UnitTestCase;
use Dynamite\Validator\Constraints\KeySchema;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

#[Group('unit')]
class KeySchemaTest extends UnitTestCase
{
    #[DataProvider('validateDataProvider')]
    public function testValidate(mixed $input, bool $expected): void
    {
        $entity = new class {
            /**
             * @var array<int, mixed>|null
             */
            #[KeySchema]
            public ?array $keySchema = null;
        };
        $entity->keySchema = $input;

        $this->assertSame($expected, $this->createValidator()->validate($entity)->count() > 0);
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
                    'KeyType' => KeyType::Hash,
                ],
            ],
            false,
        ];
    }
}
