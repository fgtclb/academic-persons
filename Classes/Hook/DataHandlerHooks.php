<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersons\Hook;

use FGTCLB\AcademicPersons\Service\ProfileImageMetadataService;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * DataHandler hooks of the profile table, registered in `ext_localconf.php`. The
 * class is a public, constructor-injected service and holds no state of its own.
 */
final class DataHandlerHooks
{
    private const PROFILE_TABLE = 'tx_academicpersons_domain_model_profile';

    /**
     * The profile columns the image metadata is composed from.
     */
    private const NAME_COLUMNS = ['title', 'first_name', 'middle_name', 'last_name'];

    /**
     * The image column: a save that replaces the image writes the metadata onto a
     * different reference, even when no name column is part of the same save.
     */
    private const IMAGE_COLUMN = 'image';

    public function __construct(
        private readonly ProfileImageMetadataService $profileImageMetadataService,
    ) {}

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
        if ($table !== self::PROFILE_TABLE || !in_array($status, ['new', 'update'], true)) {
            return;
        }

        $profileUid = (int)($dataHandler->substNEWwithIDs[$id] ?? $id);
        if ($profileUid <= 0) {
            // A `NEW…` id that was never substituted: there is no record to flush a
            // cache tag for, and `profile_detail_view_0` is not a tag anything holds.
            return;
        }
        // The image reference metadata follows the name - of a created, updated or
        // localized profile, and of the translations `DataMapProcessor` added to the
        // map while propagating an exclude column. A record whose relation is still on
        // the remap stack gets this hook *deferred* by the DataHandler until the stack
        // ran (`hook_processDatamap_afterDatabaseOperations()`), which is why the
        // reference of a profile that is new in the run is already wired to it here.
        $touchedColumns = array_keys($fieldArray);
        if (array_intersect(self::NAME_COLUMNS, $touchedColumns) !== []
            || in_array(self::IMAGE_COLUMN, $touchedColumns, true)
        ) {
            // A DataHandler hook gets no request; this is the boundary that resolves
            // it, so a listener of `ModifyProfileImageMetadataEvent` sees the backend
            // request a save happens in. There is none in a command line run.
            $request = $GLOBALS['TYPO3_REQUEST'] ?? null;
            $this->profileImageMetadataService->updateForProfileUid(
                $profileUid,
                $request instanceof ServerRequestInterface ? $request : null,
            );
        }
        $cacheManager = GeneralUtility::makeInstance(CacheManager::class);
        $cacheManager->flushCachesByTags([
            'profile_list_view',
            sprintf('profile_detail_view_%d', $profileUid),
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
