<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TESTS: Academic Persons frontend user synchronisation events',
    'description' => 'Listeners of the frontend user synchronisation events and a profile factory that declines, for the functional tests of the synchronisation',
    'version' => '3.0.0',
    'category' => 'plugin',
    'state' => 'beta',
    'author' => 'FGTCLB GmbH',
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
