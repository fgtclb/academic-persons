<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TESTS: Academic Persons managed fields',
    'description' => 'Managed fields of synchronised person records, for the functional tests of the backend lock',
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
