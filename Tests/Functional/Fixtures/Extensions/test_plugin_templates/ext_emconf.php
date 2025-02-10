<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TESTS: Academic Persons Plugin Templates',
    'description' => 'Provide plugin template overrides for academic-persons for functional tests',
    'category' => 'plugin',
    'author' => 'Stefan Bürk',
    'author_email' => 'stefan@buerk.tech',
    'author_company' => 'web-vision GmbH',
    'state' => 'beta',
    'version' => '0.3.1',
    'clearCacheOnLoad' => true,
    'constraints' => [
        'depends' => [
            'typo3' => '*',
            'academic_persons' => '*',
        ],
        'conflicts' => [
        ],
        'suggests' => [
        ],
    ],
];
