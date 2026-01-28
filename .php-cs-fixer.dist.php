<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;
use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;

return (new Config())
    ->setParallelConfig(ParallelConfigFactory::detect()) // @TODO 4.0 no need to call this manually
    ->setRiskyAllowed(true)
    ->setRules([
        '@Symfony' => true,
        '@Symfony:risky' => true,
        'declare_strict_types' => true,
        'no_useless_else' => true,
        'no_useless_return' => true,
        'php_unit_strict' => true,
        'strict_comparison' => true,
        'strict_param' => true,
        'final_internal_class' => [
            'include' => [],
            'exclude' => ['final', 'api-extendable'],
            'consider_absent_docblock_as_internal_class' => true,
        ],
        'modernize_strpos' => true, // needs PHP 8+ or polyfill
        'native_constant_invocation' => ['strict' => false], // strict:false to not remove `\` on low-end PHP versions for not-yet-known consts
        'numeric_literal_separator' => true,
        'phpdoc_order' => [
            'order' => [
                'type',
                'template',
                'template-covariant',
                'template-extends',
                'extends',
                'implements',
                'property',
                'method',
                'param',
                'return',
                'var',
                'assert',
                'assert-if-false',
                'assert-if-true',
                'throws',
                'author',
                'see',
            ],
        ],
        'trailing_comma_in_multiline' => [
            'after_heredoc' => true,
            'elements' => [
                'arguments',
                'array_destructuring',
                'arrays',
                // 'match', // @TODO PHP 8.0: enable me
                // 'parameters', // @TODO PHP 8.0: enable me
            ],
        ],
    ])
    // 💡 by default, Fixer looks for `*.php` files excluding `./vendor/` - here, you can groom this config
    ->setFinder(
        (new Finder())
            // 💡 root folder to check
            ->in(__DIR__)
            // 💡 additional files, eg bin entry file
            // ->append([__DIR__.'/bin-entry-file'])
            // 💡 folders to exclude, if any
            // ->exclude([/* ... */])
            // 💡 path patterns to exclude, if any
            // ->notPath([/* ... */])
            // 💡 extra configs
            // ->ignoreDotFiles(false) // true by default in v3, false in v4 or future mode
            // ->ignoreVCS(true) // true by default
    )
;
