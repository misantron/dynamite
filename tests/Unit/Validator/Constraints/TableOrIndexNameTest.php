<?php

declare(strict_types=1);

namespace Dynamite\Tests\Unit\Validator\Constraints;

use Dynamite\Tests\Unit\UnitTestCase;
use Dynamite\Validator\Constraints\TableOrIndexName;

class TableOrIndexNameTest extends UnitTestCase
{
    /**
     * @dataProvider validateDataProvider
     */
    public function testValidate(mixed $input, bool $expected): void
    {
        $entity = new class() {
            #[TableOrIndexName]
            public ?string $tableName = null;
        };
        $entity->tableName = $input;

        self::assertSame($expected, $this->createValidator()->validate($entity)->count() > 0);
    }

    /**
     * @return iterable<string, array<int, mixed>>
     */
    public function validateDataProvider(): iterable
    {
        yield 'null-value' => [
            null,
            true,
        ];
        yield 'empty-value' => [
            '',
            true,
        ];
        yield 'small-length-value' => [
            'ab',
            true,
        ];
        yield 'big-length-value' => [
            '8jk4mm81tditcmfftoy6biu12lfvavne8zfkbp7j16i0jv1gy0beo-vgct9jcmzrmd_dn1wk_csq_7zodbaddtr1udb8d9v99ob0yzu0a4a-a_r6otf7orvoxck65zd5to6t4d2rkr1oehh0ges-gs1ju2qw-7znfmxw9m486xgn93bsut036hcjo3a4j8zucliwpq4w7ddnepako7lhydqwp8lrt2sc13xp8bde7xqs4p1gspcgybejaceqzwmh',
            true,
        ];
        yield 'contains-illegal-chars' => [
            'user@meta',
            true,
        ];
        yield 'valid-name-1' => [
            'Users-123',
            false,
        ];
        yield 'valid-name-2' => [
            'public.app_logs',
            false,
        ];
    }
}
