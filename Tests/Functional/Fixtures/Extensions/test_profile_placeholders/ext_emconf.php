<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TESTS: Academic Persons Profile Placeholders',
    'description' => 'Ships profile image placeholders the way a site package does, for functional tests',
    'version' => '3.0.0',
    'category' => 'plugin',
    'state' => 'beta',
    'author' => 'Stefan Bürk',
    'author_email' => 'hello@fgtclb.com',
    'author_company' => 'FGTCLB GmbH',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.35-14.3.99',
            'core' => '13.4.35-14.3.99',
            'academic_persons' => '3.0.0',
        ],
    ],
];
