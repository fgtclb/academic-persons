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

final class Version20230706063400 extends AbstractDataHandlerMigration
{
    public function up(Schema $schema): void
    {
        $uuidResolver = GeneralUtility::makeInstance(UuidResolverFactory::class)->getResolverForTable('pages');
        $profileStorageUid = $uuidResolver->getUidForUuid('0948a6b7-ee15-49db-9876-eef0f1314364') ?? 0;

        $this->dataMap = [
            'tx_academicpersons_domain_model_profile' => [
                'NEW1688616258' => [
                    'uuid' => '15fbae96-d5f8-4e4d-a77a-0b3e6b736d7e',
                    'pid' => $profileStorageUid,
                    'gender' => 'mr',
                    'title' => 'Doctor',
                    'first_name' => 'Johnny',
                    'middle_name' => 'the rocket',
                    'last_name' => 'English',
                    'website' => 'https://mi7.test',
                ],
                'NEW1688621185' => [
                    'uuid' => '43972c72-8bff-4f3f-ad3d-f644e9f27bd0',
                    'pid' => $profileStorageUid,
                    'gender' => 'diverse',
                    'title' => '',
                    'first_name' => 'Dagobert',
                    'last_name' => 'Duck',
                ],
            ],
        ];

        parent::up($schema);
    }
}
