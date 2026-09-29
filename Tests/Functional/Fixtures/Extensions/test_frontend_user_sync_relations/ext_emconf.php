<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TESTS: Academic Persons frontend user synchronisation of contract relations',
    'description' => 'A frontend user synchronisation map that assigns the organisational unit and the function type, for the functional tests of the synchronisation',
    'version' => '3.0.0',
    'category' => 'plugin',
    'state' => 'beta',
    'author' => 'FGTCLB GmbH',
    'author_email' => 'hello@fgtclb.com',
    'author_company' => 'FGTCLB GmbH',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.0-14.3.99',
            'academic_persons' => '3.0.0',
        ],
    ],
];
