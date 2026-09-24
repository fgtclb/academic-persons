<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersons\Hook;

use FGTCLB\AcademicPersons\DataHandling\ProfileWriteCorrelation;
use FGTCLB\AcademicPersons\Domain\Repository\ProfileRepository;
use FGTCLB\AcademicPersons\Event\AfterProfileUpdateEvent;
use FGTCLB\AcademicPersons\Event\ProfileUpdateOrigin;
use FGTCLB\AcademicPersons\Service\ProfileImageMetadataService;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;
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
        private readonly ProfileRepository $profileRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly SiteFinder $siteFinder,
        private readonly ConnectionPool $connectionPool,
    ) {}

    public function processDatamap_beforeStart(DataHandler $dataHandler): void
    {
        $this->setAlphaValuesForProfile($dataHandler);
    }

    /**
     * Announces every live, default-language profile in the datamap of the run, once,
     * after all of it is written - so the translations follow a backend save and a
     * DataHandler based import the way they follow a frontend edit. A row the save
     * left unchanged, or one the DataHandler refused, is in the datamap too, and is
     * announced all the same; the listeners repeat what is already in place.
     *
     * Not announced:
     * - a nested instance: the DataHandler writes a copied or localized record through
     *   one, and commands are not announced; a run started from inside another run's
     *   hook - a listener of this very announcement - is nested as well;
     * - a run marked {@see ProfileWriteCorrelation::Internal}: the translation
     *   synchronisation and the profile image writes, whose update is announced by
     *   the code that started them;
     * - a run in a workspace: only live saves are announced.
     */
    public function processDatamap_afterAllOperations(DataHandler $dataHandler): void
    {
        if (!isset($dataHandler->datamap[self::PROFILE_TABLE])
            || !$dataHandler->isOuterMostInstance()
            || (int)$dataHandler->BE_USER->workspace !== 0
        ) {
            return;
        }
        $correlation = ProfileWriteCorrelation::fromCorrelationId($dataHandler->getCorrelationId());
        if ($correlation === ProfileWriteCorrelation::Internal) {
            return;
        }
        $origin = $correlation === ProfileWriteCorrelation::Import
            ? ProfileUpdateOrigin::Import
            : ProfileUpdateOrigin::Backend;
        $profileUids = [];
        foreach (array_keys($dataHandler->datamap[self::PROFILE_TABLE]) as $id) {
            $profileUid = (int)($dataHandler->substNEWwithIDs[$id] ?? $id);
            if ($profileUid > 0) {
                $profileUids[$profileUid] = $profileUid;
            }
        }
        foreach ($this->findLiveDefaultLanguageProfiles(array_values($profileUids)) as $profileUid => $pageUid) {
            $profile = $this->profileRepository->findByUidForSynchronization($profileUid);
            if ($profile === null) {
                continue;
            }
            $this->eventDispatcher->dispatch(
                new AfterProfileUpdateEvent($profile, $this->findSite($pageUid), $origin),
            );
        }
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

    /**
     * The datamap also holds the translations the save touched, and those the
     * DataHandler added to it while propagating an excluded column: only the
     * default-language rows are announced, the synchronisation starts from them.
     *
     * @param list<int> $profileUids
     * @return array<int, int> The page uid of each profile, keyed by the profile uid
     */
    private function findLiveDefaultLanguageProfiles(array $profileUids): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::PROFILE_TABLE);
        $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $rows = $queryBuilder
            ->select('uid', 'pid')
            ->from(self::PROFILE_TABLE)
            ->where(
                $queryBuilder->expr()->in('uid', $queryBuilder->quoteArrayBasedValueListToIntegerList($profileUids)),
                $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('t3ver_wsid', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
            )
            ->orderBy('uid')
            ->executeQuery()
            ->fetchAllAssociative();
        $pageUids = [];
        foreach ($rows as $row) {
            $pageUids[(int)$row['uid']] = (int)$row['pid'];
        }
        return $pageUids;
    }

    private function findSite(int $pageUid): ?Site
    {
        try {
            return $this->siteFinder->getSiteByPageId($pageUid);
        } catch (SiteNotFoundException) {
            return null;
        }
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
