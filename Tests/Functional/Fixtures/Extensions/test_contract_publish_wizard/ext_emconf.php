<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TESTS: Academic Persons contract publish wizard registration',
    'description' => 'A site package that registers the contract publish to hidden upgrade wizard in its own Services.yaml',
    'version' => '3.0.0',
    'category' => 'misc',
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
