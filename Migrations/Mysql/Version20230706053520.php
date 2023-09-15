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

final class Version20230706053520 extends AbstractDataHandlerMigration
{
    public function up(Schema $schema): void
    {
        $uuidPageResolver = GeneralUtility::makeInstance(UuidResolverFactory::class)->getResolverForTable('pages');
        $uuidCategoryResolver = GeneralUtility::makeInstance(UuidResolverFactory::class)->getResolverForTable('sys_category');
        $profileStorageUid = $uuidPageResolver->getUidForUuid('0948a6b7-ee15-49db-9876-eef0f1314364') ?? 0;

        $this->dataMap = [
            'tx_academicpersons_domain_model_address' => [
                'NEW1688616404' => [
                    'uuid' => '780213a4-bff8-4382-8d0a-a7d25359082a',
                    'pid' => $profileStorageUid,
                    'employee_type' => $uuidCategoryResolver->getUidForUuid('c14ff637-f496-425c-b1f2-42532c069625'),
                    'organisational_level_1' => $uuidCategoryResolver->getUidForUuid('be90ab8f-5787-45c2-8b41-c638387a1bbd'),
                    'organisational_level_2' => $uuidCategoryResolver->getUidForUuid('3a70e698-33ea-4ed7-98c9-b5ec27bad82c'),
                    'organisational_level_3' => $uuidCategoryResolver->getUidForUuid('a516b539-af9b-48f0-aa8a-94676fcbdecd'),
                    'street' => 'Buckingham Palace',
                    'street_number' => '1',
                    'zip' => 'SW1A 1AA',
                    'city' => 'London',
                    'country' => 'United Kingdom',
                    'type' => 'business',
                ],
                'NEW1688621196' => [
                    'uuid' => '0013eeb0-3726-4172-a7cb-6428b564c354',
                    'pid' => $profileStorageUid,
                    'employee_type' => $uuidCategoryResolver->getUidForUuid('24f4ed51-35a5-4448-9e80-e188fe85643e'),
                    'organisational_level_1' => $uuidCategoryResolver->getUidForUuid('7e2f9179-88e6-41b7-8263-5535d9ac8656'),
                    'organisational_level_2' => $uuidCategoryResolver->getUidForUuid('e313f769-4d5b-4d56-b4fb-801d6a21fe84'),
                    'organisational_level_3' => $uuidCategoryResolver->getUidForUuid('33a2e74d-a297-46a7-8307-02bb3cc1c48d'),
                    'street' => 'Geldtresor',
                    'street_number' => '1',
                    'zip' => '12345',
                    'city' => 'Entenhausen',
                    'country' => 'United States of America',
                    'type' => 'business',
                ],
            ],
        ];

        parent::up($schema);
    }
}
