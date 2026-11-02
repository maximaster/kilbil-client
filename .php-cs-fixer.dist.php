<?php

declare(strict_types=1);

$finder = (new PhpCsFixer\Finder())
    ->in([__DIR__ . '/src', __DIR__ . '/tests'])
    // `generated/` is the generator's canonical output. Formatting it would
    // make the committed tree drift from the next regeneration.
    ->exclude(['build', 'vendor']);

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PER-CS2x0' => true,
        '@PER-CS2x0:risky' => true,
        '@PHP8x4Migration' => true,
        'declare_strict_types' => true,
        'global_namespace_import' => [
            'import_classes' => true,
            'import_constants' => true,
            'import_functions' => true,
        ],
        'fully_qualified_strict_types' => ['import_symbols' => true],
        'ordered_imports' => ['imports_order' => ['class', 'function', 'const']],
        'no_unused_imports' => true,
        'single_quote' => true,
    ])
    ->setFinder($finder)
    ->setCacheFile(__DIR__ . '/build/cache/.php-cs-fixer.cache');
