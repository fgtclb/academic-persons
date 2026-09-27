<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TESTS: Academic Persons frontend read-only fields',
    'description' => 'Fields locked for the frontend editor only, for the functional tests of the frontendreadonly flag',
    'version' => '2.4.0',
    'category' => 'misc',
    'state' => 'beta',
    'author' => 'FGTCLB GmbH',
    'author_email' => 'hello@fgtclb.com',
    'author_company' => 'FGTCLB GmbH',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.22-13.4.99',
            'academic_persons' => '2.4.0',
        ],
    ],
];
