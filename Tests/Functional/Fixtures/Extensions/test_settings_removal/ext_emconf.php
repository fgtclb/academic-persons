<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TESTS: academic_persons settings removal',
    'description' => 'Removes one profile field with a tilde and changes nothing else',
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
