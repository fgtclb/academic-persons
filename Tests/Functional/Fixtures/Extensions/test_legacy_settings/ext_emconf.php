<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TESTS: Legacy academic_persons settings override',
    'description' => 'Ships the pre-3.0 validations override the 2.x manual told integrators to write',
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
