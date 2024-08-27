<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

return (new Config())
    ->setRules([
        '@PhpCsFixer' => true,
        'ordered_class_elements' => [
            'sort_algorithm' => 'alpha',
            'order' => [
                'use_trait',
                'constant',
                'constant_public',
                'constant_protected',
                'constant_private',
                'public',
                'protected',
                'private',
                'property',
                'property_static',
                'property_public_static',
                'property_protected_static',
                'property_private_static',
                'property_public',
                'property_protected',
                'property_private',
                'method_public_abstract_static',
                'method_protected_abstract_static',
                'method_private_abstract_static',
                'method_public_abstract',
                'method_protected_abstract',
                'method_private_abstract',
                'construct',
                'destruct',
                'magic',
                'phpunit',
                'method',
                'method_static',
                'method_public_static',
                'method_protected_static',
                'method_private_static',
                'method_public',
                'method_protected',
                'method_private',
            ],
        ],
    ])
    ->setFinder(
        Finder::create()
            ->in(__DIR__.'/src')
            ->in(__DIR__.'/tests')
    )
;
