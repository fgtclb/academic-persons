<?php

$EM_CONF[$_EXTKEY] = [
    'author' => 'FGTCLB',
    'author_company' => 'FGTCLB GmbH',
    'author_email' => 'hello@fgtclb.com',
    'category' => 'plugin',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.22-13.4.99',
            'extbase' => '12.4.22-13.4.99',
            'fluid' => '12.4.22-13.4.99',
            'frontend' => '12.4.22-13.4.99',
            'install' => '12.4.22-13.4.99',
            'rte_ckeditor' => '12.4.22-13.4.99',
            'academic_base' => '2.0.2',
        ],
        'conflicts' => [
        ],
        'suggests' => [
            'numbered_pagination' => '2.1.0-2.99.99',
        ],
    ],
    'description' => 'Adds a person database to TYPO3 with plugins to show them in the frontend.',
    'state' => 'beta',
    'title' => 'FGTCLB: Academic Persons',
    'version' => '2.0.1',
];
