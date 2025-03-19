<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'FGTCLB: Academic Persons',
    'description' => 'Adds a person database to TYPO3 with plugins to show them in the frontend.',
    'category' => 'plugin',
    'author' => 'Tim Schreiner',
    'author_email' => 'tim.schreiner@km2.de',
    'author_company' => 'FGTCLB',
    'state' => 'beta',
    'version' => '1.1.5',
    'clearCacheOnLoad' => true,
    'constraints' => [
        'depends' => [
            'typo3' => '11.5.0-12.4.99',
            'extbase' => '11.5.0-12.4.99',
            'fluid' => '11.5.0-12.4.99',
            'frontend' => '11.5.0-12.4.99',
            'rte_ckeditor' => '11.5.0-12.4.99',
        ],
        'conflicts' => [
        ],
        'suggests' => [
            'numbered_pagination' => '1.0.0-1.99.99',
        ],
    ],
];
