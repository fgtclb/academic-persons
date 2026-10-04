<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TESTS: Academic Persons profile icon replacement',
    'description' => 'A site package that replaces public profile icons, one in the right file and one in the wrong one, for tests',
    'version' => '3.0.0',
    'category' => 'misc',
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
