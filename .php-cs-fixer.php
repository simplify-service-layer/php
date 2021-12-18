<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

return (new Config())
    ->setRules([
        '@PhpCsFixer' => true,
        'ordered_class_elements' => [
            'sort_algorithm' => 'alpha',
        ],
    ])
    ->setFinder(
        Finder::create()
            ->in(__DIR__.'/src')
            ->in(__DIR__.'/tests')
    )
;
