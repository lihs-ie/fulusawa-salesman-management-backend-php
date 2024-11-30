<?php

use PhpCsFixer\Config;

return (new Config())
  ->setRiskyAllowed(true)
  ->setRules([
    'no_unused_imports' => true,
    'ordered_imports' => ['sort_algorithm' => 'alpha'],
  ])
  ->setFinder(
    PhpCsFixer\Finder::create()
      ->in([__DIR__ . '/app', __DIR__ . '/routes', __DIR__ . '/tests'])
      ->name('*.php')
      ->notName('*.blade.php')
      ->ignoreDotFiles(true)
      ->ignoreVCS(true)
  );
