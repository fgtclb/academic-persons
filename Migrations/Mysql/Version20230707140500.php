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

final class Version20230707140500 extends AbstractDataHandlerMigration
{
    public function up(Schema $schema): void
    {
        $uuidResolver = GeneralUtility::makeInstance(UuidResolverFactory::class)->getResolverForTable('pages');
        $rootPageUid = $uuidResolver->getUidForUuid('e4d8e1a9-a3f8-410f-b756-b3e311d44cf7');

        $this->dataMap = [
            'pages' => [
                'NEW1688731594' => [
                    'uuid' => '46e0243d-8608-4317-8bc7-0ec32a680f56',
                    'pid' => $rootPageUid,
                    'doktype' => 1,
                    'hidden' => 0,
                    'title' => 'Profiles',
                    'slug' => '/profiles',
                ],
            ],
        ];

        parent::up($schema);
    }
}
