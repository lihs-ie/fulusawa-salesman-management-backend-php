<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$srcDirectory = __DIR__.'/src';
$targets = [
    'app',
    'config',
    'database',
    'routes',
    'tests',
];

return (new Config())
    ->setRiskyAllowed(true)
    ->setRules([
        'no_unused_imports' => true,
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        '@PSR2' => true,
        '@PhpCsFixer' => true,
        'linebreak_after_opening_tag' => true,
        'list_syntax' => ['syntax' => 'short'],
        'no_superfluous_phpdoc_tags' => true,
        'simplified_null_return' => true,
        'void_return' => true,
        'yoda_style' => false,
    ])
    ->setFinder(
        Finder::create()
            ->in(array_map(fn (string $target): string => "{$srcDirectory}/{$target}", $targets))
            ->name('*.php')
            ->notName('*.blade.php')
            ->ignoreDotFiles(true)
            ->ignoreVCS(true)
    )
;
