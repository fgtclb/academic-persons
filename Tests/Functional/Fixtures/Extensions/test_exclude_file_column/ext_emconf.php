<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TESTS: Exclude-mode file column on profiles',
    'description' => 'Adds a file column with l10n_mode exclude to the profile table for the translation synchronisation tests',
    'version' => '3.0.0',
    'category' => 'misc',
    'state' => 'beta',
    'author' => 'Stefan Bürk',
    'author_email' => 'hello@fgtclb.com',
    'author_company' => 'FGTCLB GmbH',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.0-14.3.99',
            'academic_persons' => '3.0.0',
        ],
    ],
];
