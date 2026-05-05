<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TESTS: Academic Persons Plugin Templates',
    'description' => 'Provide plugin template overrides for academic-persons for functional tests',
    'version' => '2.3.3',
    'category' => 'plugin',
    'state' => 'beta',
    'author' => 'Stefan Bürk',
    'author_email' => 'hello@fgtclb.com',
    'author_company' => 'FGTCLB GmbH',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.22-13.4.99',
            'academic_persons' => '2.3.3',
        ],
    ],
];
