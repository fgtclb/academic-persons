<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Service;

use Doctrine\DBAL\Result;
use FGTCLB\AcademicPersons\Domain\Model\Dto\Syncronizer\SynchronizerContext;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @internal being experimental for now until implementation has been streamlined, tested and covered with tests.
 * @final not marked as final for functional testing reasons (for now). Class should not be extended otherwise.
 */
#[AsAlias(id: RecordSynchronizerInterface::class, public: true)]
#[Autoconfigure(public: true)]
class RecordSynchronizer implements RecordSynchronizerInterface
{
    public function __construct(
        private readonly ConnectionPool $connectionPool,
    ) {}

    public function synchronize(SynchronizerContext $context): void
    {
        $this->synchronizeRecord($context, []);
    }

    /**
     * @param array<string, int|float|string|bool|null> $values
     * @throws \Doctrine\DBAL\Exception
     */
    private function synchronizeRecord(SynchronizerContext $context, array $values): void
    {
        $defaultRecord = $this->getDefaultRecord(
            $context->tableName,
            $context->uid,
            $context->defaultLanguage->getLanguageId(),
        );
        if ($defaultRecord === null) {
            return;
        }
        $tcaColumns = $GLOBALS['TCA'][$context->tableName]['columns'];
        foreach ($context->allowedSiteLanguages as $allowedSiteLanguage) {
            $translatedRecord = $this->getTranslatedRecord(
                $context->tableName,
                $context->uid,
                $allowedSiteLanguage->getLanguageId(),
            );
            if ($translatedRecord !== null) {
                $this->updateTranslation(
                    $context,
                    $defaultRecord,
                    $translatedRecord,
                );
                continue;
            }
            $translatedRecord = $this->createTranslation(
                $context->tableName,
                $defaultRecord,
                $allowedSiteLanguage->getLanguageId(),
                $values,
            );
            if ($translatedRecord === null) {
                // Failed to create translation record, skip relation synchronization.
                continue;
            }
            foreach ($tcaColumns as $columnName => $columnDefinition) {
                $columnType = $columnDefinition['type'] ?? 'unknown';
                if (!($columnType === 'inline' && $columnName !== 'sys_file_reference')) {
                    // Non inline fields or column `sys_file_reference` should be skipped.
                    // @todo Column name `sys_file_reference` exclude does not make sense and should be most likely
                    //       `foreign_table` and will investigated at a later point, kept for now during moving code
                    //       around to prepare for better testability and avoiding a side task for now.
                    continue;
                }
                $inlineTable = $columnDefinition['config']['foreign_table'];
                $inlineField = $columnDefinition['config']['foreign_field'];
                $inlineChilds = $this->getInlineChilds(
                    $inlineTable,
                    $inlineField,
                    $defaultRecord['uid'],
                    $context->defaultLanguage->getLanguageId(),
                );
                if ($inlineChilds === null) {
                    // No inline children. Skip to next loop iteration.
                    continue;
                }
                while ($inlineChild = $inlineChilds->fetchAssociative()) {
                    $this->synchronizeRecord(
                        $context->withRecord($inlineTable, $inlineChild['uid']),
                        [
                            (string)$inlineField => $translatedRecord['uid'],
                        ],
                    );
                }
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function getDefaultRecord(
        string $table,
        int $uid,
        int $defaultLanguageId,
    ): ?array {
        $tcaCtrl = $GLOBALS['TCA'][$table]['ctrl'];

        $queryBuilder = $this->getQueryBuilder($table);
        $queryBuilder->select('*')
            ->from($table)
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    $tcaCtrl['languageField'],
                    $queryBuilder->createNamedParameter($defaultLanguageId, Connection::PARAM_INT)
                )
            )
            ->setMaxResults(1);

        $resultArray = $queryBuilder->executeQuery()->fetchAssociative();

        return $resultArray ?: null;
    }

    /**
     * @param string $table
     * @param int $uid
     * @param int $languageUid
     * @return array<string, mixed>
     */
    private function getTranslatedRecord(
        string $table,
        int $uid,
        int $languageUid
    ): ?array {
        $tcaCtrl = $GLOBALS['TCA'][$table]['ctrl'];

        $queryBuilder = $this->getQueryBuilder($table);
        $queryBuilder->select('*')
            ->from($table)
            ->where(
                $queryBuilder->expr()->eq(
                    $tcaCtrl['translationSource'] ?? $tcaCtrl['transOrigPointerField'],
                    $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    $tcaCtrl['languageField'],
                    $queryBuilder->createNamedParameter((int)$languageUid, Connection::PARAM_INT)
                )
            )
            ->setMaxResults(1);

        $resultArray = $queryBuilder->executeQuery()->fetchAssociative();

        return $resultArray ?: null;
    }

    /**
     * @param string $tableName
     * @param array<string, mixed> $defaultRecord
     * @param int $languageUid
     * @param array<string, mixed> $values
     * @return array<string, mixed>|null
     */
    private function createTranslation(
        string $tableName,
        array $defaultRecord,
        int $languageUid,
        array $values = []
    ): ?array {
        $defaultRecoredUid = $defaultRecord['uid'];
        $tcaColumns = $GLOBALS['TCA'][$tableName]['columns'];
        $tcaCtrl = $GLOBALS['TCA'][$tableName]['ctrl'];

        // Exclude inline columns from the default record
        $excludeColumns = array_merge(
            ['uid', 'l10n_diffsource', 't3ver_oid', 't3ver_wsid', 't3ver_state', 't3ver_stage'],
            array_keys($values)
        );
        foreach ($tcaColumns as $columnName => $columnDefinition) {
            if ($columnDefinition['config']['type'] === 'inline') {
                $excludeColumns[] = $columnName;
            }
        }

        // Merge default record values with the given values
        foreach ($defaultRecord as $columnName => $value) {
            if (!in_array($columnName, $excludeColumns)) {
                $values[$columnName] = $value;
            }
        }

        // Override language specific values
        $values['sys_language_uid'] = $languageUid;
        if (isset($tcaCtrl['transOrigPointerField'])) {
            $values[$tcaCtrl['transOrigPointerField']] = $defaultRecoredUid;
        }
        if (isset($tcaCtrl['translationSource'])) {
            $values[$tcaCtrl['translationSource']] = $defaultRecoredUid;
        }
        $values['crdate'] = $GLOBALS['EXEC_TIME'];
        $values['tstamp'] = $GLOBALS['EXEC_TIME'];

        $queryBuilder = $this->getQueryBuilder($tableName);
        $queryBuilder->insert($tableName);
        $queryBuilder->values($values);

        $queryBuilder->executeStatement();

        return $this->getTranslatedRecord($tableName, $defaultRecoredUid, $languageUid);
    }

    /**
     * @param array<string, mixed> $defaultRecord
     * @param array<string, mixed> $translatedRecord
     */
    private function updateTranslation(
        SynchronizerContext $context,
        array $defaultRecord,
        array $translatedRecord
    ): void {
        $tcaColumns = $GLOBALS['TCA'][$context->tableName]['columns'];
        $updateColumns = [];
        foreach ($tcaColumns as $columnName => $columnDefinition) {
            if (isset($columnDefinition['config']['type'])
                && is_string($columnDefinition['config']['type'])
                && $columnDefinition['config']['type'] !== 'inline'
                && isset($columnDefinition['l10n_mode'])
                && $columnDefinition['l10n_mode'] === 'exclude'
            ) {
                $updateColumns[] = $columnName;
            }
        }

        // Skip if there are no columns to update
        if (empty($updateColumns)) {
            return;
        }

        $queryBuilder = $this->getQueryBuilder($context->tableName);
        $queryBuilder->update($context->tableName)
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($translatedRecord['uid'], Connection::PARAM_INT)
                ),
            );

        foreach ($updateColumns as $columnName) {
            $queryBuilder->set($columnName, $defaultRecord[$columnName]);
        }

        $queryBuilder->executeStatement();
    }

    /**
     * @return Result|null
     */
    private function getInlineChilds(
        string $tableName,
        string $field,
        int $uid,
        int $defaultLanguageId,
    ): ?Result {
        $tcaCtrl = $GLOBALS['TCA'][$tableName]['ctrl'];
        if (!isset($tcaCtrl['languageField'])) {
            return null;
        }
        $queryBuilder = $this->getQueryBuilder($tableName);
        $queryBuilder->select('*')
            ->from($tableName)
            ->where(
                $queryBuilder->expr()->eq(
                    $field,
                    $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    $tcaCtrl['languageField'],
                    $queryBuilder->createNamedParameter($defaultLanguageId, Connection::PARAM_INT)
                )
            );

        return $queryBuilder->executeQuery();
    }

    /**
     * Get a query builder for a table.
     *
     * @param string $table Table name present in $GLOBALS['TCA']
     * @return QueryBuilder
     */
    private function getQueryBuilder(string $table): QueryBuilder
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        return $queryBuilder;
    }
}
