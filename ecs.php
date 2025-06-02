<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\FunctionNotation\FunctionDeclarationFixer;
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
    ->withPhpCsFixerSets(perCS: true)
    ->withPreparedSets(
        common: true,
        strict: true
    )
    ->withConfiguredRule(
        checkerClass: FunctionDeclarationFixer::class,
        configuration: [
            'closure_fn_spacing' => 'one',
        ],
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
