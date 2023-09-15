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

final class Version20230706053510 extends AbstractDataHandlerMigration
{
    public function up(Schema $schema): void
    {
        $uuidResolver = GeneralUtility::makeInstance(UuidResolverFactory::class)->getResolverForTable('pages');
        $profileStorageUid = $uuidResolver->getUidForUuid('0948a6b7-ee15-49db-9876-eef0f1314364') ?? 0;

        $this->dataMap = [
            'sys_category' => array_merge(
                $this->getEmployeeTypes($profileStorageUid),
                $this->getOrganizationLevel1($profileStorageUid),
                $this->getOrganizationLevel2($profileStorageUid),
                $this->getOrganizationLevel3($profileStorageUid)
            ),
        ];

        parent::up($schema);
    }

    /**
     * @return array<string, array<string, string|int>>
     */
    private function getEmployeeTypes(int $profileStorageUid): array
    {
        return [
            'NEW1692612013' => [
                'uuid' => '8f5ae7b5-8293-4d46-89cb-53b7bda0e957',
                'pid' => $profileStorageUid,
                'title' => 'Employee Type',
                'hidden' => 0,
            ],
            'NEW1692612023' => [
                'uuid' => 'c14ff637-f496-425c-b1f2-42532c069625',
                'pid' => $profileStorageUid,
                'title' => 'Professor',
                'hidden' => 0,
                'parent' => 'NEW1692612013',
            ],
            'NEW1692612033' => [
                'uuid' => '24f4ed51-35a5-4448-9e80-e188fe85643e',
                'pid' => $profileStorageUid,
                'title' => 'Scientific Employee',
                'hidden' => 0,
                'parent' => 'NEW1692612013',
            ],
        ];
    }

    /**
     * @return array<string, array<string, string|int>>
     */
    private function getOrganizationLevel1(int $profileStorageUid): array
    {
        return [
            'NEW1692612113' => [
                'uuid' => 'a4265851-1913-424f-bdaa-9de392a12171',
                'pid' => $profileStorageUid,
                'title' => 'Organizational Level 1',
                'hidden' => 0,
            ],
            'NEW1692612123' => [
                'uuid' => 'be90ab8f-5787-45c2-8b41-c638387a1bbd',
                'pid' => $profileStorageUid,
                'title' => 'Faculty',
                'hidden' => 0,
                'parent' => 'NEW1692612113',
            ],
            'NEW1692612133' => [
                'uuid' => '7e2f9179-88e6-41b7-8263-5535d9ac8656',
                'pid' => $profileStorageUid,
                'title' => 'Operating Unit',
                'hidden' => 0,
                'parent' => 'NEW1692612113',
            ],
        ];
    }

    /**
     * @return array<string, array<string, string|int>>
     */
    private function getOrganizationLevel2(int $profileStorageUid): array
    {
        return [
            'NEW1692612213' => [
                'uuid' => 'bfa00efe-8ac6-4096-99c1-cfdd8742be6a',
                'pid' => $profileStorageUid,
                'title' => 'Organizational Level 2',
                'hidden' => 0,
            ],
            'NEW1692612223' => [
                'uuid' => '3a70e698-33ea-4ed7-98c9-b5ec27bad82c',
                'pid' => $profileStorageUid,
                'title' => 'Department 1',
                'hidden' => 0,
                'parent' => 'NEW1692612213',
            ],
            'NEW1692612233' => [
                'uuid' => 'e313f769-4d5b-4d56-b4fb-801d6a21fe84',
                'pid' => $profileStorageUid,
                'title' => 'Department 2',
                'hidden' => 0,
                'parent' => 'NEW1692612213',
            ],
        ];
    }

    /**
     * @return array<string, array<string, string|int>>
     */
    private function getOrganizationLevel3(int $profileStorageUid): array
    {
        return [
            'NEW1692612313' => [
                'uuid' => 'e738d3fd-cf60-49cf-8718-e08c077ff8e5',
                'pid' => $profileStorageUid,
                'title' => 'Organizational Level 3',
                'hidden' => 0,
            ],
            'NEW1692612323' => [
                'uuid' => 'a516b539-af9b-48f0-aa8a-94676fcbdecd',
                'pid' => $profileStorageUid,
                'title' => 'Team 1',
                'hidden' => 0,
                'parent' => 'NEW1692612313',
            ],
            'NEW1692612333' => [
                'uuid' => '33a2e74d-a297-46a7-8307-02bb3cc1c48d',
                'pid' => $profileStorageUid,
                'title' => 'Team 2',
                'hidden' => 0,
                'parent' => 'NEW1692612313',
            ],
        ];
    }
}
