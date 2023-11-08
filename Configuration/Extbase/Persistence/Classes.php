<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

return [
    \Fgtclb\AcademicPersons\Domain\Model\Profile::class => [
        'tableName' => 'tx_academicpersons_domain_model_profile',
        'recordType' => \Fgtclb\AcademicPersons\Domain\Model\Profile::class,
    ],
    \Fgtclb\AcademicPersons\Domain\Model\Contract::class => [
        'tableName' => 'tx_academicpersons_domain_model_contract',
        'recordType' => \Fgtclb\AcademicPersons\Domain\Model\Contract::class,
        'properties' => [
            'employeeType' => [
                'fieldName' => 'employee_type',
            ],
            'organisationalLevel1' => [
                'fieldName' => 'organisational_level_1',
            ],
            'organisationalLevel2' => [
                'fieldName' => 'organisational_level_2',
            ],
            'organisationalLevel3' => [
                'fieldName' => 'organisational_level_3',
            ],
        ],
    ],
    \Fgtclb\AcademicPersons\Domain\Model\Address::class => [
        'tableName' => 'tx_academicpersons_domain_model_address',
        'recordType' => \Fgtclb\AcademicPersons\Domain\Model\Address::class,
        'properties' => [
            'employeeType' => [
                'fieldName' => 'employee_type',
            ],
            'organisationalLevel1' => [
                'fieldName' => 'organisational_level_1',
            ],
            'organisationalLevel2' => [
                'fieldName' => 'organisational_level_2',
            ],
            'organisationalLevel3' => [
                'fieldName' => 'organisational_level_3',
            ],
        ],
    ],
    \Fgtclb\AcademicPersons\Domain\Model\Email::class => [
        'tableName' => 'tx_academicpersons_domain_model_email',
        'recordType' => \Fgtclb\AcademicPersons\Domain\Model\Email::class,
    ],
    \Fgtclb\AcademicPersons\Domain\Model\PhoneNumber::class => [
        'tableName' => 'tx_academicpersons_domain_model_phone_number',
        'recordType' => \Fgtclb\AcademicPersons\Domain\Model\PhoneNumber::class,
    ],
    \Fgtclb\AcademicPersons\Domain\Model\Location::class => [
        'tableName' => 'tx_academicpersons_domain_model_location',
        'recordType' => \Fgtclb\AcademicPersons\Domain\Model\Location::class,
    ],
    \Fgtclb\AcademicPersons\Domain\Model\ProfileInformation::class => [
        'tableName' => 'tx_academicpersons_domain_model_profile_information',
        'subclasses' => [
            'curriculum_vitae' => \Fgtclb\AcademicPersons\Domain\Model\ProfileInformation::class,
            'membership' => \Fgtclb\AcademicPersons\Domain\Model\ProfileInformation::class,
            'cooperation' => \Fgtclb\AcademicPersons\Domain\Model\ProfileInformation::class,
            'publication' => \Fgtclb\AcademicPersons\Domain\Model\ProfileInformation::class,
            'lecture' => \Fgtclb\AcademicPersons\Domain\Model\ProfileInformation::class,
        ],
    ],
];
