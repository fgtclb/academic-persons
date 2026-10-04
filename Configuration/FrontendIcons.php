<?php

use FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider;

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

/*
 * The icons of the public profile detail view, registered in the frontend icon registry
 * of EXT:academic_base and rendered with its `ab:icon` ViewHelper by the partials below
 * `Resources/Private/Partials/Profile/PublicProfile/`. The backend never shows them, so
 * they are not in `Configuration/Icons.php`. A site package that depends on this
 * extension replaces one by registering its identifier in its own
 * `Configuration/FrontendIcons.php`.
 *
 * Drawn in `currentColor` (Bootstrap Icons, MIT) and inlined by the provider, so they
 * take the text colour of the page.
 */
return [
    'academic-persons-envelope' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_persons/Resources/Public/Icons/envelope.svg',
    ],
    'academic-persons-phone' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_persons/Resources/Public/Icons/phone.svg',
    ],
    'academic-persons-address' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_persons/Resources/Public/Icons/address.svg',
    ],
    'academic-persons-room' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_persons/Resources/Public/Icons/room.svg',
    ],
    'academic-persons-clock' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_persons/Resources/Public/Icons/clock.svg',
    ],
    'academic-persons-detail-plus' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_persons/Resources/Public/Icons/detail-plus.svg',
    ],
    'academic-persons-detail-minus' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_persons/Resources/Public/Icons/detail-minus.svg',
    ],
];
