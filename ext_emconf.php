<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'FGTCLB: Academic Persons',
    'description' => 'Adds a person database to TYPO3 with plugins to show them in the frontend.',
    'version' => '2.3.5',
    'category' => 'plugin',
    'state' => 'beta',
    'author' => 'FGTCLB',
    'author_email' => 'hello@fgtclb.com',
    'author_company' => 'FGTCLB GmbH',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.22-13.4.99',
            'extbase' => '12.4.22-13.4.99',
            'fluid' => '12.4.22-13.4.99',
            'frontend' => '12.4.22-13.4.99',
            'install' => '12.4.22-13.4.99',
            'rte_ckeditor' => '12.4.22-13.4.99',
            'academic_base' => '2.3.5',
        ],
        'suggests' => [
            'numbered_pagination' => '2.1.0-2.99.99',
        ],
    ],
];
