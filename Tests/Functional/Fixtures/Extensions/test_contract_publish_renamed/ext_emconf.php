<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TESTS: Academic Persons renamed contract publish column',
    'description' => 'The contract column publish as the database analyser leaves it once renamed for removal',
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
