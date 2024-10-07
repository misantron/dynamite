<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\Import\OrderedImportsFixer;
use PhpCsFixer\Fixer\Operator\NotOperatorWithSuccessorSpaceFixer;
use PhpCsFixer\Fixer\StringNotation\ExplicitStringVariableFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;
use Symplify\EasyCodingStandard\ValueObject\Set\SetList;

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
    ->withSets([
        SetList::SPACES,
        SetList::ARRAY,
        SetList::CLEAN_CODE,
        SetList::STRICT,
        SetList::PSR_12,
        SetList::PHPUNIT,
        SetList::CONTROL_STRUCTURES,
        SetList::NAMESPACES,
    ])
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
