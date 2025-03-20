<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TESTS: Academic Persons Plugin Templates',
    'description' => 'Provide plugin template overrides for academic-persons for functional tests',
    'category' => 'plugin',
    'author' => 'Stefan Bürk',
    'author_email' => 'stefan@buerk.tech',
    'author_company' => 'web-vision GmbH',
    'state' => 'beta',
    'version' => '1.1.5',
    'clearCacheOnLoad' => true,
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.0-13.4.99',
            'academic_persons' => '1.1.5',
        ],
        'conflicts' => [
        ],
        'suggests' => [
        ],
    ],
];
