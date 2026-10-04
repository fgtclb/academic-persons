<?php

declare(strict_types=1);

use FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider;

// A replacement left in the file of the backend registry, which the frontend does not read.
return [
    'tx-academicbase-info-phone' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:test_profile_icon_replacement/Resources/Public/Icons/replaced.svg',
    ],
];
