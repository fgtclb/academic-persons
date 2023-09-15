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

final class Version20230707140200 extends AbstractDataHandlerMigration
{
    public function up(Schema $schema): void
    {
        $uuidResolver = GeneralUtility::makeInstance(UuidResolverFactory::class)->getResolverForTable('pages');
        $rootPageUid = $uuidResolver->getUidForUuid('e4d8e1a9-a3f8-410f-b756-b3e311d44cf7');

        $this->dataMap = [
            'sys_template' => [
                'NEW123' => [
                    'pid' => $rootPageUid,
                    'title' => 'Academic Persons',
                    'root' => 1,
                    'include_static_file' => implode(',', [
                        'EXT:bootstrap_package/Configuration/TypoScript/',
                        'EXT:academic_persons/Configuration/TypoScript/',
                    ]),
                    'config' => '',
                ],
            ],
        ];

        parent::up($schema);
    }
}
