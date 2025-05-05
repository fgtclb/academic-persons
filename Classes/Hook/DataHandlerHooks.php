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
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class DataHandlerHooks
{
    public function processDatamap_beforeStart(DataHandler $dataHandler): void
    {
        $this->setAlphaValuesForProfile($dataHandler);
    }

    /**
     * @param array<string, mixed> $fieldArray
     */
    public function processDatamap_afterDatabaseOperations(
        string $status,
        string $table,
        string $id,
        array $fieldArray,
        DataHandler $dataHandler
    ): void {
        if ($table !== 'tx_academicpersons_domain_model_profile' || $status !== 'update') {
            return;
        }

        $cacheManager = GeneralUtility::makeInstance(CacheManager::class);
        $cacheManager->flushCachesByTags([
            'profile_list_view',
            sprintf('profile_detail_view_%d', $id),
        ]);
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
