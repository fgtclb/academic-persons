<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Upgrades;

use Doctrine\DBAL\Schema\Column;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Attribute\UpgradeWizard;
use TYPO3\CMS\Install\Updates\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

#[UpgradeWizard('academicPersons_MigrateListTypeToCTypeContentElements')]
final class ListTypeToCTypeUpgradeWizard implements UpgradeWizardInterface
{
    public function __construct(
        private readonly ConnectionPool $connectionPool,
    ) {}

    /**
     * This must return an array containing the "list_type" to "CType" mapping.
     * The array key is the exact value of corresponding "tt_content.list_type" DB records,
     * the array value is the new "CType" value.
     * not plugin
     *
     *  Example:
     *
     *  [
     *      'pi_plugin1' => 'pi_plugin1',
     *      'pi_plugin2' => 'new_content_element',
     *  ]
     *
     * Note that string keys with integer values like '4' will be treated as INT by
     * PHP internally, which is why string-casting is performed later on.
     * @see https://3v4l.org/JNPfU
     *
     * @return array<string|int, string>
     */
    protected function getListTypeToCTypeMapping(): array
    {
        return [
            'academicpersons_detail' => 'academicpersons_detail',
            'academicpersons_list' => 'academicpersons_list',
            'academicpersons_listanddetail' => 'academicpersons_listanddetail',
            'academicpersons_selectedcontracts' => 'academicpersons_selectedcontracts',
            'academicpersons_selectedprofiles' => 'academicpersons_selectedprofiles',
        ];
    }

    public function getTitle(): string
    {
        return 'Migrate "EXT:academic_persons" from list elements to content elements (list_type => CType)';
    }

    public function getDescription(): string
    {
        return '';
    }

    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }

    public function updateNecessary(): bool
    {
        return $this->updateNecessaryContentElements()
            || $this->updateNecessaryBackendUserGroups();
    }

    public function executeUpdate(): bool
    {
        if ($this->updateNecessaryContentElements()) {
            $this->updateContentElements();
        }
        if ($this->updateNecessaryBackendUserGroups()) {
            $this->updateBackendUserGroups();
        }

        return true;
    }

    protected function columnsExistInContentTable(): bool
    {
        $schemaManager = $this->connectionPool
            ->getConnectionForTable('tt_content')
            ->createSchemaManager();

        $tableColumnNames = array_flip(
            array_map(
                static fn(Column $column) => $column->getName(),
                $schemaManager->listTableColumns('tt_content')
            )
        );

        foreach (['CType', 'list_type'] as $column) {
            if (!isset($tableColumnNames[$column])) {
                return false;
            }
        }

        return true;
    }

    protected function columnsExistInBackendUserGroupsTable(): bool
    {
        $schemaManager = $this->connectionPool
            ->getConnectionForTable('be_groups')
            ->createSchemaManager();

        return isset($schemaManager->listTableColumns('be_groups')['explicit_allowdeny']);
    }

    protected function hasContentElementsToUpdate(): bool
    {
        $listTypesToUpdate = array_keys($this->getListTypeToCTypeMapping());

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder
            ->count('uid')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq('CType', $queryBuilder->createNamedParameter('list')),
                $queryBuilder->expr()->in(
                    'list_type',
                    $queryBuilder->createNamedParameter($listTypesToUpdate, Connection::PARAM_STR_ARRAY)
                ),
            );

        return (bool)$queryBuilder->executeQuery()->fetchOne();
    }

    protected function hasBackendUserGroupsToUpdate(): bool
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('be_groups');
        $queryBuilder->getRestrictions()->removeAll();

        $searchConstraints = [];
        foreach (array_keys($this->getListTypeToCTypeMapping()) as $listType) {
            $searchConstraints[] = $queryBuilder->expr()->like(
                'explicit_allowdeny',
                $queryBuilder->createNamedParameter(
                    '%' . $queryBuilder->escapeLikeWildcards('tt_content:list_type:' . $listType) . '%'
                )
            );
        }

        $queryBuilder
            ->count('uid')
            ->from('be_groups')
            ->where(
                $queryBuilder->expr()->or(...$searchConstraints),
            );

        return (bool)$queryBuilder->executeQuery()->fetchOne();
    }

    /**
     * Returns true, if no legacy explicit_allowdeny be_groups configuration is found.
     */
    protected function hasNoLegacyBackendGroupsExplicitAllowDenyConfiguration(): bool
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('be_groups');
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder
            ->count('uid')
            ->from('be_groups')
            ->where(
                $queryBuilder->expr()->like(
                    'explicit_allowdeny',
                    $queryBuilder->createNamedParameter(
                        '%ALLOW%'
                    )
                ),
            );
        return (int)$queryBuilder->executeQuery()->fetchOne() === 0;
    }

    /**
     * @param string $listType
     * @return array<int, array<string, mixed>>
     * @throws \Doctrine\DBAL\Exception
     */
    protected function getContentElementsToUpdate(string $listType): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder
            ->select('uid')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq('CType', $queryBuilder->createNamedParameter('list')),
                $queryBuilder->expr()->eq('list_type', $queryBuilder->createNamedParameter($listType)),
            );

        return $queryBuilder->executeQuery()->fetchAllAssociative();
    }

    /**
     * @param string $listType
     * @return array<int, array<string, mixed>>
     * @throws \Doctrine\DBAL\Exception
     */
    protected function getBackendUserGroupsToUpdate(string $listType): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('be_groups');
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder
            ->select('uid', 'explicit_allowdeny')
            ->from('be_groups')
            ->where(
                $queryBuilder->expr()->like(
                    'explicit_allowdeny',
                    $queryBuilder->createNamedParameter(
                        '%' . $queryBuilder->escapeLikeWildcards('tt_content:list_type:' . $listType) . '%'
                    )
                ),
            );
        return $queryBuilder->executeQuery()->fetchAllAssociative();
    }

    protected function updateContentElements(): void
    {
        $connection = $this->connectionPool->getConnectionForTable('tt_content');

        foreach ($this->getListTypeToCTypeMapping() as $listType => $contentType) {
            foreach ($this->getContentElementsToUpdate((string)$listType) as $record) {
                $connection->update(
                    'tt_content',
                    [
                        'CType' => $contentType,
                        'list_type' => '',
                    ],
                    ['uid' => (int)$record['uid']]
                );
            }
        }
    }

    protected function updateBackendUserGroups(): void
    {
        $connection = $this->connectionPool->getConnectionForTable('be_groups');

        foreach ($this->getListTypeToCTypeMapping() as $listType => $contentType) {
            foreach ($this->getBackendUserGroupsToUpdate((string)$listType) as $record) {
                $fields = GeneralUtility::trimExplode(',', $record['explicit_allowdeny'], true);
                foreach ($fields as $key => $field) {
                    if ($field === 'tt_content:list_type:' . $listType) {
                        unset($fields[$key]);
                        $fields[] = 'tt_content:CType:' . $contentType;
                    }
                }

                $connection->update(
                    'be_groups',
                    [
                        'explicit_allowdeny' => implode(',', array_unique($fields)),
                    ],
                    ['uid' => (int)$record['uid']]
                );
            }
        }
    }

    private function updateNecessaryContentElements(): bool
    {
        return
            $this->getListTypeToCTypeMapping() !== [] &&
            $this->columnsExistInContentTable() &&
            $this->hasContentElementsToUpdate()
        ;
    }

    private function updateNecessaryBackendUserGroups(): bool
    {
        return
            $this->getListTypeToCTypeMapping() !== [] &&
            $this->columnsExistInBackendUserGroupsTable()
            && $this->hasNoLegacyBackendGroupsExplicitAllowDenyConfiguration()
            && $this->hasBackendUserGroupsToUpdate()
        ;
    }
}
