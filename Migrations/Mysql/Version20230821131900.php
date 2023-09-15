<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Fgtclb\AcademicPersons\Migrations\Mysql;

use AndreasWolf\Uuid\UuidResolverFactory;
use Doctrine\DBAL\Schema\Schema;
use KayStrobach\Migrations\Migration\AbstractDataHandlerMigration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class Version20230821131900 extends AbstractDataHandlerMigration
{
    public function up(Schema $schema): void
    {
        $this->createContractWithOneAddressOneEmailOnePhoneNumber();
        $this->createContractWithTwoAddressesTwoEmailsTwoPhoneNumbers();

        parent::up($schema);
    }

    private function createContractWithOneAddressOneEmailOnePhoneNumber(): void
    {
        $uuidPageResolver = GeneralUtility::makeInstance(UuidResolverFactory::class)->getResolverForTable('pages');
        $uuidCategoryResolver = GeneralUtility::makeInstance(UuidResolverFactory::class)->getResolverForTable('sys_category');
        $uuidProfileResolver = GeneralUtility::makeInstance(UuidResolverFactory::class)->getResolverForTable('tx_academicpersons_domain_model_profile');
        $uuidAddressResolver = GeneralUtility::makeInstance(UuidResolverFactory::class)->getResolverForTable('tx_academicpersons_domain_model_address');
        $profileStorageUid = $uuidPageResolver->getUidForUuid('0948a6b7-ee15-49db-9876-eef0f1314364');

        $this->dataMap = array_merge_recursive(
            $this->dataMap,
            [
                'tx_academicpersons_domain_model_email' => [
                    'NEW1688616732' => [
                        'pid' => $profileStorageUid,
                        'email' => 'johnny.english@test.uk',
                        'type' => 'business',
                    ],
                ],
                'tx_academicpersons_domain_model_phone_number' => [
                    'NEW1688616746' => [
                        'pid' => $profileStorageUid,
                        'phone_number' => '+44 12345 67890',
                        'type' => 'business',
                    ],
                ],
                'tx_academicpersons_domain_model_contract' => [
                    'NEW1692617975' => [
                        'uuid' => '4b522c04-6c14-47fa-91c4-a7da7aa775bf',
                        'pid' => $profileStorageUid,
                        'publish' => 1,
                        'employee_type' => $uuidCategoryResolver->getUidForUuid('c14ff637-f496-425c-b1f2-42532c069625'),
                        'organisational_level_1' => $uuidCategoryResolver->getUidForUuid('be90ab8f-5787-45c2-8b41-c638387a1bbd'),
                        'organisational_level_2' => $uuidCategoryResolver->getUidForUuid('3a70e698-33ea-4ed7-98c9-b5ec27bad82c'),
                        'organisational_level_3' => $uuidCategoryResolver->getUidForUuid('a516b539-af9b-48f0-aa8a-94676fcbdecd'),
                        'physical_addresses_from_organisation' => $uuidAddressResolver->getUidForUuid('780213a4-bff8-4382-8d0a-a7d25359082a'),
                        'phone_numbers' => 'NEW1688616746',
                        'email_addresses' => 'NEW1688616732',
                        'position' => 'Agent',
                        'room' => '007',
                    ],
                ],
                'tx_academicpersons_domain_model_profile' => [
                    $uuidProfileResolver->getUidForUuid('15fbae96-d5f8-4e4d-a77a-0b3e6b736d7e') => [
                        'contracts' => 'NEW1692617975',
                    ],
                ],

            ]
        );
    }

    private function createContractWithTwoAddressesTwoEmailsTwoPhoneNumbers(): void
    {
        $uuidPageResolver = GeneralUtility::makeInstance(UuidResolverFactory::class)->getResolverForTable('pages');
        $uuidCategoryResolver = GeneralUtility::makeInstance(UuidResolverFactory::class)->getResolverForTable('sys_category');
        $uuidProfileResolver = GeneralUtility::makeInstance(UuidResolverFactory::class)->getResolverForTable('tx_academicpersons_domain_model_profile');
        $uuidAddressResolver = GeneralUtility::makeInstance(UuidResolverFactory::class)->getResolverForTable('tx_academicpersons_domain_model_address');
        $profileStorageUid = $uuidPageResolver->getUidForUuid('0948a6b7-ee15-49db-9876-eef0f1314364');

        $this->dataMap = array_merge_recursive(
            $this->dataMap,
            [
                'tx_academicpersons_domain_model_email' => [
                    'NEW1688621201' => [
                        'pid' => $profileStorageUid,
                        'email' => 'dd@geldspeicher.com',
                        'type' => 'business',
                    ],
                    'NEW1688621429' => [
                        'pid' => $profileStorageUid,
                        'email' => 'dagobert.duck@entenhausen.com',
                        'type' => 'private',
                    ],
                ],
                'tx_academicpersons_domain_model_phone_number' => [
                    'NEW11688621208' => [
                        'pid' => $profileStorageUid,
                        'phone_number' => '13245 / 789',
                        'type' => 'business',
                    ],
                    'NEW1688621447' => [
                        'pid' => $profileStorageUid,
                        'phone_number' => '64561 / 8761134',
                        'type' => 'private',
                    ],
                ],
                'tx_academicpersons_domain_model_contract' => [
                    'NEW1692618084' => [
                        'uuid' => '2ad954ae-1f0e-4b74-ae31-be1bddb7a72e',
                        'pid' => $profileStorageUid,
                        'publish' => 1,
                        'employee_type' => $uuidCategoryResolver->getUidForUuid('24f4ed51-35a5-4448-9e80-e188fe85643e'),
                        'organisational_level_1' => $uuidCategoryResolver->getUidForUuid('7e2f9179-88e6-41b7-8263-5535d9ac8656'),
                        'organisational_level_2' => $uuidCategoryResolver->getUidForUuid('e313f769-4d5b-4d56-b4fb-801d6a21fe84'),
                        'organisational_level_3' => $uuidCategoryResolver->getUidForUuid('33a2e74d-a297-46a7-8307-02bb3cc1c48d'),
                        'physical_addresses_from_organisation' => $uuidAddressResolver->getUidForUuid('0013eeb0-3726-4172-a7cb-6428b564c354'),
                        'phone_numbers' => 'NEW11688621208,NEW1688621447',
                        'email_addresses' => 'NEW1688621201,NEW1688621429',
                    ],
                ],
                'tx_academicpersons_domain_model_profile' => [
                    $uuidProfileResolver->getUidForUuid('43972c72-8bff-4f3f-ad3d-f644e9f27bd0') => [
                        'contracts' => 'NEW1692618084',
                    ],
                ],
            ]
        );
    }
}
