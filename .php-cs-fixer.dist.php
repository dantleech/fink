<?php

use PhpCsFixer\Config;

$finder = PhpCsFixer\Finder::create()
    ->in('lib')
    ->in('tests')
;

return (new Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR2' => true,
        'array_syntax' => ['syntax' => 'short'],
        'no_unused_imports' => true,
        'visibility_required' => [
            'elements' => [
                'const',
                'method',
                'property',
            ],
        ],
    ])
    ->setFinder($finder)
;

