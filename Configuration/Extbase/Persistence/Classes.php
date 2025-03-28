<?php

declare(strict_types=1);

use Fgtclb\AcademicPersons\Domain\Model\Address;
use Fgtclb\AcademicPersons\Domain\Model\Contract;
use Fgtclb\AcademicPersons\Domain\Model\Email;
use Fgtclb\AcademicPersons\Domain\Model\FunctionType;
use Fgtclb\AcademicPersons\Domain\Model\Location;
use Fgtclb\AcademicPersons\Domain\Model\OrganisationalUnit;
use Fgtclb\AcademicPersons\Domain\Model\PhoneNumber;
use Fgtclb\AcademicPersons\Domain\Model\Profile;
use Fgtclb\AcademicPersons\Domain\Model\ProfileInformation;

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

return [
    Address::class => [
        'tableName' => 'tx_academicpersons_domain_model_address',
        'recordType' => Address::class,
        'properties' => [
            'employeeType' => [
                'fieldName' => 'employee_type',
            ],
        ],
    ],
    Contract::class => [
        'tableName' => 'tx_academicpersons_domain_model_contract',
        'recordType' => Contract::class,
        'properties' => [
            'employeeType' => [
                'fieldName' => 'employee_type',
            ],
        ],
    ],
    Email::class => [
        'tableName' => 'tx_academicpersons_domain_model_email',
        'recordType' => Email::class,
    ],
    FunctionType::class => [
        'tableName' => 'tx_academicpersons_domain_model_function_type',
        'recordType' => FunctionType::class,
    ],
    Location::class => [
        'tableName' => 'tx_academicpersons_domain_model_location',
        'recordType' => Location::class,
    ],
    OrganisationalUnit::class => [
        'tableName' => 'tx_academicpersons_domain_model_organisational_unit',
        'recordType' => OrganisationalUnit::class,
    ],
    PhoneNumber::class => [
        'tableName' => 'tx_academicpersons_domain_model_phone_number',
        'recordType' => PhoneNumber::class,
    ],
    Profile::class => [
        'tableName' => 'tx_academicpersons_domain_model_profile',
        'recordType' => Profile::class,
    ],
    ProfileInformation::class => [
        'tableName' => 'tx_academicpersons_domain_model_profile_information',
        'subclasses' => [
            'curriculum_vitae' => ProfileInformation::class,
            'membership' => ProfileInformation::class,
            'cooperation' => ProfileInformation::class,
            'publication' => ProfileInformation::class,
            'lecture' => ProfileInformation::class,
        ],
    ],
];
