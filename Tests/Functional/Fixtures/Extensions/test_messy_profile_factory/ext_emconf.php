<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TESTS: Academic Persons Language Files',
    'description' => 'Extension providing language files for tests',
    'version' => '3.0.0',
    'category' => 'misc',
    'state' => 'beta',
    'author' => 'Stefan Bürk',
    'author_email' => 'hello@fgtclb.com',
    'author_company' => 'FGTCLB GmbH',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.22-13.4.99',
            'academic_persons' => '3.0.0',
        ],
    ],
];
