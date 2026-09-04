<?php

use FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider;
use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */
return [
    'tx_academicpersons_domain_model_address' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:academic_persons/Resources/Public/Icons/tx_academicpersons_domain_model_address.svg',
    ],
    'tx_academicpersons_domain_model_contract' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:academic_persons/Resources/Public/Icons/tx_academicpersons_domain_model_contract.svg',
    ],
    'tx_academicpersons_domain_model_email' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:academic_persons/Resources/Public/Icons/tx_academicpersons_domain_model_email.svg',
    ],
    'tx_academicpersons_domain_model_function_type' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:academic_persons/Resources/Public/Icons/tx_academicpersons_domain_model_function_type.svg',
    ],
    'tx_academicpersons_domain_model_organisational_unit' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:academic_persons/Resources/Public/Icons/tx_academicpersons_domain_model_organisational_unit.svg',
    ],
    'tx_academicpersons_domain_model_phone_number' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:academic_persons/Resources/Public/Icons/tx_academicpersons_domain_model_phone_number.svg',
    ],
    'tx_academicpersons_domain_model_profile' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:academic_persons/Resources/Public/Icons/tx_academicpersons_domain_model_profile.svg',
    ],
    'tx_academicpersons_domain_model_location' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:academic_persons/Resources/Public/Icons/tx_academicpersons_domain_model_location.svg',
    ],
    'tx_academicpersons_domain_model_profile_information' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:academic_persons/Resources/Public/Icons/tx_academicpersons_domain_model_profile_information.svg',
    ],
    'persons_icon' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:academic_persons/Resources/Public/Icons/persons_icon.svg',
    ],
    // The controls of the public profile detail view, unlike the record icons above: drawn in
    // `currentColor` (Bootstrap Icons, MIT) and inlined by the provider so they take the text
    // colour of the page. Rendered by the partials below `Resources/Private/Partials/Profile/PublicProfile/`.
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
    'academic-persons-detail-plus' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_persons/Resources/Public/Icons/detail-plus.svg',
    ],
    'academic-persons-detail-minus' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_persons/Resources/Public/Icons/detail-minus.svg',
    ],
];
