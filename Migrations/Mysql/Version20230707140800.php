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

final class Version20230707140800 extends AbstractDataHandlerMigration
{
    public function up(Schema $schema): void
    {
        $uuidResolver = GeneralUtility::makeInstance(UuidResolverFactory::class)->getResolverForTable('pages');
        $profileListPageUid = $uuidResolver->getUidForUuid('46e0243d-8608-4317-8bc7-0ec32a680f56');
        $profileStorageUid = $uuidResolver->getUidForUuid('0948a6b7-ee15-49db-9876-eef0f1314364');

        $this->dataMap = [
            'tt_content' => [
                'NEW1688731717' => [
                    'uuid' => '416a7ab3-0762-4f56-b641-1f6d702d5d34',
                    'pid' => $profileListPageUid,
                    'CType' => 'list',
                    'list_type' => 'academicpersons_listanddetail',
                    'pages' => (string)$profileStorageUid,
                ],
            ],
        ];

        parent::up($schema);
    }
}
