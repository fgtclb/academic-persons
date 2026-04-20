<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TESTS: Academic Persons Language Files',
    'description' => 'Extension providing language files for tests',
    'category' => 'fe,be',
    'author' => 'Stefan Bürk',
    'author_email' => 'stefan@buerk.tech',
    'author_company' => 'web-vision GmbH',
    'state' => 'beta',
    'version' => '2.3.1',
    'clearCacheOnLoad' => true,
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.22-13.4.99',
            'academic_persons' => '2.3.1',
        ],
        'conflicts' => [
        ],
        'suggests' => [
        ],
    ],
];
