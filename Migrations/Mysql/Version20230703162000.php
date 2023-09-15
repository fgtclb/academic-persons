<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Fgtclb\AcademicPersons\Migrations\Mysql;

use Doctrine\DBAL\Schema\Schema;
use KayStrobach\Migrations\Migration\AbstractDataHandlerMigration;

final class Version20230703162000 extends AbstractDataHandlerMigration
{
    public function up(Schema $schema): void
    {
        $this->dataMap = [
            'pages' => [
                'NEW123' => [
                    'uuid' => 'e4d8e1a9-a3f8-410f-b756-b3e311d44cf7',
                    'pid' => 0,
                    'doktype' => 1,
                    'title' => 'Academic Persons',
                    'slug' => '/',
                    'is_siteroot' => 1,
                    'hidden' => 0,
                ],
            ],
        ];

        parent::up($schema);
    }
}
