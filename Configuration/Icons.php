<?php

declare(strict_types=1);

use FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider;

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

/*
 * The icons the backend shows for this extension: Font Awesome Free solid, drawn in
 * `currentColor` and inlined by the provider of EXT:academic_base, so they take the text
 * colour of the backend in both colour schemes. Licence and origin of the files of this
 * extension: Resources/Public/Icons/LICENSE-font-awesome.txt.
 *
 * Identifiers follow `tx-academicpersons-<group>-<name>`, files `Icons/<group>/<name>.svg`:
 * `record` for the icon of a TCA table, `plugin` for a content element, named by its TCA
 * CType item and by its new content element wizard entry alike. Where the meaning is one
 * of the shared glyphs of EXT:academic_base, the file is taken from there. The icons of
 * the public profile are frontend icons, the shared `tx-academicbase-*` identifiers of the
 * frontend icon registry of EXT:academic_base, and are not registered here.
 */
return [
    'tx-academicpersons-record-address' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_persons/Resources/Public/Icons/record/address.svg',
    ],
    'tx-academicpersons-record-contract' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_base/Resources/Public/Icons/info/contract.svg',
    ],
    'tx-academicpersons-record-email' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_base/Resources/Public/Icons/info/email.svg',
    ],
    'tx-academicpersons-record-function-type' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_persons/Resources/Public/Icons/record/function-type.svg',
    ],
    'tx-academicpersons-record-location' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_base/Resources/Public/Icons/info/location.svg',
    ],
    'tx-academicpersons-record-organisational-unit' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_persons/Resources/Public/Icons/record/organisational-unit.svg',
    ],
    'tx-academicpersons-record-phone-number' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_base/Resources/Public/Icons/info/phone.svg',
    ],
    'tx-academicpersons-record-profile' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_persons/Resources/Public/Icons/record/profile.svg',
    ],
    'tx-academicpersons-record-profile-information' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_base/Resources/Public/Icons/info/information.svg',
    ],
    'tx-academicpersons-plugin-persons' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_persons/Resources/Public/Icons/plugin/persons.svg',
    ],
    'tx-academicpersons-plugin-card' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_persons/Resources/Public/Icons/plugin/card.svg',
    ],
    'tx-academicpersons-plugin-selected-profiles' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_persons/Resources/Public/Icons/plugin/selected-profiles.svg',
    ],
];
