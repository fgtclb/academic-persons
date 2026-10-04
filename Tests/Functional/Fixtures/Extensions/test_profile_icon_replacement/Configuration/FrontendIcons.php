<?php

declare(strict_types=1);

use FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider;

// The way a site package replaces an icon of academic_persons for the frontend.
return [
    'academic-persons-envelope' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:test_profile_icon_replacement/Resources/Public/Icons/replaced.svg',
    ],
];
