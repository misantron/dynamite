<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\Import\OrderedImportsFixer;
use PhpCsFixer\Fixer\Operator\NotOperatorWithSuccessorSpaceFixer;
use PhpCsFixer\Fixer\StringNotation\ExplicitStringVariableFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;

return ECSConfig::configure()
    ->withParallel()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withCache(directory: '.cache/ecs')
    ->withSkip([
        NotOperatorWithSuccessorSpaceFixer::class,
        ExplicitStringVariableFixer::class,
    ])
    ->withPreparedSets(
        psr12: true,
        common: true,
        strict: true
    )
    ->withConfiguredRule(
        checkerClass: OrderedImportsFixer::class,
        configuration: [
            'sort_algorithm' => 'alpha',
            'imports_order' => [
                'const',
                'class',
                'function',
            ],
        ],
    )
;
