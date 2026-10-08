<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersons\Hook;

use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * DataHandler hooks of the profile table, registered in `ext_localconf.php` as a
 * `processDatamapClass` and a `processCmdmapClass`.
 */
final class DataHandlerHooks
{
    public function processDatamap_beforeStart(DataHandler $dataHandler): void
    {
        $this->setAlphaValuesForProfile($dataHandler);
    }

    /**
     * Flushes the list and the detail view of a profile that is created or saved. A
     * created profile, a localized one included, arrives with its `NEW…` id, which the
     * DataHandler has substituted by now.
     *
     * @param array<string, mixed> $fieldArray
     */
    public function processDatamap_afterDatabaseOperations(
        string $status,
        string $table,
        string $id,
        array $fieldArray,
        DataHandler $dataHandler
    ): void {
        if ($table !== 'tx_academicpersons_domain_model_profile' || !in_array($status, ['new', 'update'], true)) {
            return;
        }
        $this->flushProfileViews((int)($dataHandler->substNEWwithIDs[$id] ?? $id));
    }

    /**
     * Flushes the list and the detail view of a profile that is deleted or restored.
     * {@see processDatamap_afterDatabaseOperations()} covers creations and saves only, and the core
     * flushes the page of the record and the tags of its table and uid, which the
     * plugins do not carry.
     *
     * @param int|string $id
     */
    public function processCmdmap_postProcess(string $command, string $table, $id, mixed $value, DataHandler $dataHandler): void
    {
        if ($table !== 'tx_academicpersons_domain_model_profile' || !in_array($command, ['delete', 'undelete'], true)) {
            return;
        }
        $this->flushProfileViews((int)$id);
    }

    /**
     * The detail view is tagged with the uid of the default-language record, so a
     * translation flushes the tag of its parent too.
     */
    private function flushProfileViews(int $profileUid): void
    {
        if ($profileUid <= 0) {
            // A `NEW…` id that was never substituted: there is no record whose views
            // could be cached.
            return;
        }
        $tags = ['profile_list_view', sprintf('profile_detail_view_%d', $profileUid)];
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_academicpersons_domain_model_profile');
        $queryBuilder->getRestrictions()->removeAll();
        $parentUid = (int)$queryBuilder
            ->select('l10n_parent')
            ->from('tx_academicpersons_domain_model_profile')
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($profileUid, Connection::PARAM_INT)))
            ->executeQuery()
            ->fetchOne();
        if ($parentUid > 0) {
            $tags[] = sprintf('profile_detail_view_%d', $parentUid);
        }
        GeneralUtility::makeInstance(CacheManager::class)->flushCachesByTags($tags);
    }

    private function setAlphaValuesForProfile(DataHandler $dataHandler): void
    {
        if (!isset($dataHandler->datamap['tx_academicpersons_domain_model_profile'])) {
            return;
        }

        $alphaColumns = $this->getProfileAlphaColumns();

        foreach ($dataHandler->datamap['tx_academicpersons_domain_model_profile'] as $uid => &$data) {
            foreach ($alphaColumns as $alphaColumnName => $correspondingFieldName) {
                if (empty($data[$correspondingFieldName])) {
                    continue;
                }

                $data[$alphaColumnName] = strtolower(mb_substr((string)$data[$correspondingFieldName], 0, 1));
            }
        }
    }

    /**
     * @return array<string, string> Alpha column name as key and corresponding column name as value
     */
    private function getProfileAlphaColumns(): array
    {
        $alphaColumns = [];
        $profileColumns = $GLOBALS['TCA']['tx_academicpersons_domain_model_profile']['columns'] ?? [];

        foreach (array_keys($profileColumns) as $profileColumnName) {
            $profileColumnName = (string)$profileColumnName;
            if (!str_ends_with($profileColumnName, '_alpha')) {
                continue;
            }

            $alphaStringLength = mb_strlen('_alpha');
            $correspondingColumnName = mb_substr($profileColumnName, 0, mb_strlen($profileColumnName) - $alphaStringLength);

            if (isset($profileColumns[$correspondingColumnName])) {
                $alphaColumns[$profileColumnName] = $correspondingColumnName;
            }
        }

        return $alphaColumns;
    }
}
