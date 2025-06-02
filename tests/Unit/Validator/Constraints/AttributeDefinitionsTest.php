<?php

declare(strict_types=1);

namespace Dynamite\Tests\Unit\Validator\Constraints;

use Dynamite\Enum\ScalarAttributeType;
use Dynamite\Tests\Unit\UnitTestCase;
use Dynamite\Validator\Constraints\AttributeDefinitions;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

#[Group('unit')]
class AttributeDefinitionsTest extends UnitTestCase
{
    #[DataProvider('validateDataProvider')]
    public function testValidate(mixed $input, bool $expected): void
    {
        $entity = new class {
            /**
             * @var array<int, array{AttributeName: string, AttributeType: ScalarAttributeType}>|null
             */
            #[AttributeDefinitions]
            public ?array $attributeDefinitions = null;
        };
        $entity->attributeDefinitions = $input;

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
                    'AttributeType' => ScalarAttributeType::String,
                ],
            ],
            false,
        ];
    }
}
