<?php

declare(strict_types=1);

namespace Dynamite\Tests\Unit\Serializer;

use Dynamite\Serializer\PropertyNameConverter;
use Dynamite\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

class PropertyNameConverterTest extends UnitTestCase
{
    private NameConverterInterface $converter;

    protected function setUp(): void
    {
        $this->converter = new PropertyNameConverter();
    }

    #[DataProvider('normalizeDataProvider')]
    public function testNormalize(string $propertyName, string $expected): void
    {
        self::assertSame($expected, $this->converter->normalize($propertyName));
    }

    /**
     * @return iterable<int, array{0: string, 1: string}>
     */
    public static function normalizeDataProvider(): iterable
    {
        yield ['attributeDefinitions', 'AttributeDefinitions'];
        yield ['keySchema', 'KeySchema'];
        yield ['localSecondaryIndexes', 'LocalSecondaryIndexes'];
        yield ['globalSecondaryIndexes', 'GlobalSecondaryIndexes'];
    }

    #[DataProvider('denormalizeDataProvider')]
    public function testDenormalize(string $propertyName, string $expected): void
    {
        self::assertSame($expected, $this->converter->denormalize($propertyName));
    }

    /**
     * @return iterable<int, array{0: string, 1: string}>
     */
    public static function denormalizeDataProvider(): iterable
    {
        yield ['AttributeDefinitions', 'attributeDefinitions'];
        yield ['KeySchema', 'keySchema'];
        yield ['LocalSecondaryIndexes', 'localSecondaryIndexes'];
        yield ['GlobalSecondaryIndexes', 'globalSecondaryIndexes'];
    }
}
