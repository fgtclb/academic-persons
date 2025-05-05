<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Upgrades;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Install\Attribute\UpgradeWizard;
use TYPO3\CMS\Install\Updates\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

#[UpgradeWizard('academicPersons_pluginContent')]
final class PluginUpgradeWizard implements UpgradeWizardInterface
{
    private const MIGRATE_CONTENT_TYPES_LIST = [
        'academicpersons_detail' => 'academicpersons_detail',
        'academicpersons_list' => 'academicpersons_list',
        'academicpersons_listanddetail' => 'academicpersons_listanddetail',
        'academicpersons_selectedcontracts' => 'academicpersons_selectedcontracts',
        'academicpersons_selectedprofiles' => 'academicpersons_selectedprofiles',
    ];

    public function __construct(
        private ConnectionPool $connectionPool,
    ) {}

    public function getTitle(): string
    {
        return 'Migrate plugin list element from academic_persons to normal content elements';
    }

    public function getDescription(): string
    {
        return '';
    }

    public function executeUpdate(): bool
    {
        foreach (self::MIGRATE_CONTENT_TYPES_LIST as $oldName => $newName) {
            $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tt_content');
            $queryBuilder->getRestrictions()->removeAll();
            $queryBuilder
                ->update('tt_content')
                ->set('CType', $newName)
                ->set('list_type', '')
                ->where(
                    $queryBuilder->expr()->eq('CType', $queryBuilder->createNamedParameter('list')),
                    $queryBuilder->expr()->eq('list_type', $queryBuilder->createNamedParameter($oldName)),
                )->executeStatement();
        }
        return true;
    }

    public function updateNecessary(): bool
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()->removeAll();
        return (int)($queryBuilder
                ->count('*')
                ->from('tt_content')
                ->where(
                    $queryBuilder->expr()->eq('CType', $queryBuilder->createNamedParameter('list')),
                    $queryBuilder->expr()->in(
                        'list_type',
                        $queryBuilder->quoteArrayBasedValueListToStringList(array_keys(self::MIGRATE_CONTENT_TYPES_LIST))
                    ),
                )
                ->executeQuery()
                ->fetchOne()) > 0;
    }

    /**
     * @return class-string[]
     */
    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }
}
