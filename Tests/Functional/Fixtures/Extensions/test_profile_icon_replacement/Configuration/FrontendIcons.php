<?php

declare(strict_types=1);

use FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider;

// The way a site package replaces an icon of the public profile for the frontend: the
// shared identifier of academic_base the profile renders.
return [
    'tx-academicbase-info-email' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:test_profile_icon_replacement/Resources/Public/Icons/replaced.svg',
    ],
];
