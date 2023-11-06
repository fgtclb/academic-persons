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

final class Version20231106101300 extends AbstractDataHandlerMigration
{
    public function up(Schema $schema): void
    {
        $uuidPageResolver = GeneralUtility::makeInstance(UuidResolverFactory::class)->getResolverForTable('pages');
        $uuidContractResolver = GeneralUtility::makeInstance(UuidResolverFactory::class)->getResolverForTable('tx_academicpersons_domain_model_contract');
        $profileStorageUid = $uuidPageResolver->getUidForUuid('0948a6b7-ee15-49db-9876-eef0f1314364');

        $this->dataMap = [
            'tx_academicpersons_domain_model_location' => [
                'NEW1699262174' => [
                    'pid' => $profileStorageUid,
                    'title' => 'Location 1',
                    'uuid' => '8c928587-239b-4d86-a633-7f57d5c62c0b',
                ],
                'NEW1699262220' => [
                    'pid' => $profileStorageUid,
                    'title' => 'Location 2',
                    'uuid' => 'ab6f7f4d-8f4f-446f-a92f-5424d887e00a',
                ],
            ],
            'tx_academicpersons_domain_model_contract' => [
                $uuidContractResolver->getUidForUuid('4b522c04-6c14-47fa-91c4-a7da7aa775bf') => [
                    'location' => 'NEW1699262174',
                ],
                $uuidContractResolver->getUidForUuid('2ad954ae-1f0e-4b74-ae31-be1bddb7a72e') => [
                    'location' => 'NEW1699262220',
                ],
            ],
        ];

        parent::up($schema);
    }
}
