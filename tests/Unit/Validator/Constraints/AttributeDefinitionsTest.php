<?php

declare(strict_types=1);

namespace Dynamite\Tests\Unit\Validator\Constraints;

use Dynamite\Tests\Unit\UnitTestCase;
use Dynamite\Validator\Constraints\AttributeDefinitions;

class AttributeDefinitionsTest extends UnitTestCase
{
    /**
     * @dataProvider validateDataProvider
     */
    public function testValidate(mixed $input, bool $expected): void
    {
        $entity = new class() {
            #[AttributeDefinitions]
            public ?array $attributeDefinitions = null;
        };
        $entity->attributeDefinitions = $input;

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
                'AttributeType' => 'N',
            ],
            true,
        ];
        yield 'invalid-attribute-type' => [
            [
                [
                    'AttributeName' => 'Id',
                    'AttributeType' => 'U',
                ],
            ],
            true,
        ];
        yield 'valid-array' => [
            [
                [
                    'AttributeName' => 'Id',
                    'AttributeType' => 'S',
                ],
            ],
            false,
        ];
    }
}
