<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersons\Domain\Repository;

use FGTCLB\AcademicPersons\DemandValues\GroupByValues;
use FGTCLB\AcademicPersons\DemandValues\SortByValues;
use FGTCLB\AcademicPersons\Domain\Model\Dto\DemandInterface;
use FGTCLB\AcademicPersons\Domain\Model\Profile;
use FGTCLB\AcademicPersons\Event\ModifyProfileDemandEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Qom\ConstraintInterface;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * @extends Repository<Profile>
 */
class ProfileRepository extends Repository
{
    protected EventDispatcherInterface $eventDispatcher;

    public function injectEventDispatcher(EventDispatcherInterface $eventDispatcher): void
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * The enable fields the frontend user synchronization lifts, as Extbase names them.
     *
     * @var list<string>
     */
    private const SYNCHRONIZATION_IGNORED_ENABLE_FIELDS = ['disabled', 'starttime', 'endtime', 'fe_group'];

    /**
     * Applied whenever nothing else asks for an order, so that an unordered result is
     * reproducible rather than left to the DBMS. The plugin offers "none" as a sorting
     * option, and "no sorting the editor chose" still has to mean the same list twice.
     *
     * "uid" ascending is what every DBMS returns in practice - PostgreSQL without
     * promising it - so no installation sees its list change.
     *
     * @var array<string, string>
     */
    private const FALLBACK_ORDERINGS = ['uid' => QueryInterface::ORDER_ASCENDING];

    /**
     * @return QueryResultInterface<int, Profile>
     */
    public function findAll(): QueryResultInterface
    {
        $query = $this->createQuery();
        // @todo Completely ignoring storage pages is a bad design, special for multi site instances.
        //       Needs a better way to deal with this hear and in other places.
        $query->getQuerySettings()->setRespectStoragePage(false);
        $query->setOrderings(self::FALLBACK_ORDERINGS);
        return $query->execute();
    }

    /**
     * @return QueryResultInterface<int, Profile>
     */
    public function findByDemand(DemandInterface $demand): QueryResultInterface
    {
        $query = $this->createQuery();
        $demand = $this->eventDispatcher->dispatch(new ModifyProfileDemandEvent($demand))->getDemand();
        $this->applyDemandSettings($query, $demand);
        $this->applyDemandForQuery($query, $demand);
        return $query->execute();
    }

    /**
     * @param QueryInterface<Profile> $query
     */
    private function applyDemandSettings(QueryInterface $query, DemandInterface $demand): void
    {
        // @todo Remove method_exists() level (unnesting block) with next major, when added breaking to DemandInterface
        //       and deprecation layer in ProfileController::adoptSettings().
        if (method_exists($demand, 'getStoragePages')) {
            if ($demand->getStoragePages() !== '') {
                $query->getQuerySettings()->setStoragePageIds(
                    GeneralUtility::intExplode(',', $demand->getStoragePages(), true)
                );
            } else {
                $query->getQuerySettings()->setRespectStoragePage(false);
            }
        } else {
            trigger_error(
                sprintf(
                    'Class "%s" does not implement methods "%s" and "%s", which is deprecated, and will be added '
                    . 'breaking with 1.x to interface "%s". Interface already includes commented method signature.',
                    $demand::class,
                    'setStoragePages',
                    'getStoragePages',
                    DemandInterface::class,
                ),
                E_USER_DEPRECATED
            );
        }

        /**
         * Introduced with https://github.com/fgtclb/academic-persons/pull/30 to have the option to display profiles in
         * fallback mode even when site language (non-default) is configured to be in strict mode.
         *
         * {@see AcademicPersonsListAndDetailPluginTest::fullyLocalizedListDisplaysLocalizedSelectedProfilesForRequestedLanguageInSelectedOrder()}
         * {@see AcademicPersonsListPluginTest::fullyLocalizedListDisplaysLocalizedSelectedProfilesForRequestedLanguageInSelectedOrder()}
         *
         * @todo    Remove `method_exists()' check with next major, when added breaking to DemandInterface and deprecation
         *          layer in ProfileController::adoptSettings().
         */
        if (method_exists($demand, 'getFallbackForNonTranslated')) {
            if ($demand->getFallbackForNonTranslated() === 1) {
                $currentLanguageAspect = $query->getQuerySettings()->getLanguageAspect();
                // @see self::findByUids().
                $changedLanguageAspect = new LanguageAspect(
                    $currentLanguageAspect->getId(),
                    $currentLanguageAspect->getContentId(),
                    LanguageAspect::OVERLAYS_MIXED
                );
                $query->getQuerySettings()->setLanguageAspect($changedLanguageAspect);
            }
        } else {
            trigger_error(
                sprintf(
                    'Class "%s" does not implement methods "%s" and "%s", which is deprecated, and will be added '
                    . 'breaking with 1.x to interface "%s". Interface already includes commented method signature.',
                    $demand::class,
                    'setFallbackForNonTranslated',
                    'getFallbackForNonTranslated',
                    DemandInterface::class,
                ),
                E_USER_DEPRECATED
            );
        }

        // @todo Remove method_exists() level (unnesting block) with next major, when added breaking to DemandInterface
        //       and deprecation layer in ProfileController::adoptSettings().
        if (method_exists($demand, 'getShowHiddenRecords')) {
            if ($demand->getShowHiddenRecords() === true) {
                $this->includeHiddenRecords($query);
            }
        } else {
            trigger_error(
                sprintf(
                    'Class "%s" does not implement methods "%s" and "%s", which is deprecated, and will be added '
                    . 'breaking with 1.x to interface "%s". Interface already includes commented method signature.',
                    $demand::class,
                    'setShowHiddenRecords',
                    'getShowHiddenRecords',
                    DemandInterface::class,
                ),
                E_USER_DEPRECATED
            );
        }
    }

    /**
     * Include hidden (disabled) records by ignoring only the `disabled`
     * (`hidden`) enable field. Other enable fields (deleted, start-/endtime,
     * fe_group) stay in effect.
     *
     * @param QueryInterface<Profile> $query
     */
    private function includeHiddenRecords(QueryInterface $query): void
    {
        $query->getQuerySettings()->setIgnoreEnableFields(true);
        $query->getQuerySettings()->setEnableFieldsToBeIgnored(['disabled']);
    }

    /**
     * Include every profile the frontend user synchronization has to keep up to date: the
     * hidden flag, start time, end time and frontend user group are ignored, as far as the
     * profile table declares them as enable columns. They decide when and to whom a profile
     * is shown, not whether the person behind it exists - and a command-line run has no
     * frontend user group to match anyway. Deleted profiles stay excluded, and an enable
     * field added later beyond these four stays in effect. The enable columns are read from
     * the TCA directly, the TCA schema API does not exist on TYPO3 v12.
     *
     * Synchronization only. Display paths use {@see self::includeHiddenRecords()}, which
     * keeps the visibility window in place.
     *
     * @param QueryInterface<Profile> $query
     */
    private function includeRestrictedRecordsForSynchronization(QueryInterface $query): void
    {
        $enableColumns = $GLOBALS['TCA']['tx_academicpersons_domain_model_profile']['ctrl']['enablecolumns'] ?? [];
        if (!is_array($enableColumns)) {
            $enableColumns = [];
        }
        $enableFieldsToBeIgnored = array_values(array_filter(
            self::SYNCHRONIZATION_IGNORED_ENABLE_FIELDS,
            static fn(string $enableField): bool => isset($enableColumns[$enableField]),
        ));
        $query->getQuerySettings()->setIgnoreEnableFields(true);
        $query->getQuerySettings()->setEnableFieldsToBeIgnored($enableFieldsToBeIgnored);
    }

    /**
     * Prepare a query that matches records by uid taken from a manual selection.
     *
     * FormEngine persists such a selection as **default language** uids, so the language
     * restriction has to come off - otherwise nothing matches in a translation. That
     * alone is not enough: with `fallbackType: free` the aspect carries `OVERLAYS_OFF`,
     * and the default language rows would then be handed to the frontend unoverlaid. So
     * the aspect is lifted to `OVERLAYS_ON_WITH_FLOATING` first, and only then is the
     * restriction dropped.
     *
     * Adopted from the generic Extbase backend implementation, and the same shape as
     * `ContractRepository`, `EXT:academic_contacts4pages` `ContactRepository` and
     * `EXT:academic_partners` `PartnershipRepository`.
     *
     * @param QueryInterface<Profile> $query
     */
    private function matchSelectedUidsAcrossLanguages(QueryInterface $query): void
    {
        $currentLanguageAspect = $query->getQuerySettings()->getLanguageAspect();
        $changedLanguageAspect = new LanguageAspect(
            $currentLanguageAspect->getId(),
            $currentLanguageAspect->getContentId(),
            $currentLanguageAspect->getOverlayType() === LanguageAspect::OVERLAYS_OFF ? LanguageAspect::OVERLAYS_ON_WITH_FLOATING : $currentLanguageAspect->getOverlayType()
        );
        $query->getQuerySettings()->setLanguageAspect($changedLanguageAspect);
        $query->getQuerySettings()->setRespectSysLanguage(false);
    }

    /**
     * @param QueryInterface<Profile> $query
     */
    private function applyDemandForQuery(QueryInterface $query, DemandInterface $demand): void
    {
        // Direct selected profiles make all other filters and orderings obsolete and is handled first.
        if ($demand->getProfileList() !== '') {
            $profileUidArray = GeneralUtility::intExplode(',', $demand->getProfileList(), true);
            $this->matchSelectedUidsAcrossLanguages($query);
            $query->matching($query->in('uid', $profileUidArray));
            return;
        }

        $filters = $this->setFilters($query, $demand);
        if ($filters !== null) {
            $query->matching($filters);
        }
        $query->setOrderings($this->getOrderingsFromDemand($demand) ?: self::FALLBACK_ORDERINGS);
    }

    /**
     * @param QueryInterface<Profile> $query
     */
    private function setFilters(QueryInterface $query, DemandInterface $demand): ?ConstraintInterface
    {
        $filters = [];

        if (method_exists($demand, 'getFunctionTypes')
            && $demand->getFunctionTypes() !== []) {
            $filters[] = $query->in('contracts.functionType', $demand->getFunctionTypes());
        }

        if (method_exists($demand, 'getOrganisationalUnits')
            && $demand->getOrganisationalUnits() !== []) {
            $filters[] = $query->in('contracts.organisationalUnit', $demand->getOrganisationalUnits());
        }

        if ($demand->getAlphabetFilter() != '') {
            $filters[] = $query->like('last_name', $demand->getAlphabetFilter() . '%');
        }

        return ($filters === [])
            ? null
            : $query->logicalAnd(...$filters);
    }

    /**
     * @return array<string, string>
     */
    private function getOrderingsFromDemand(DemandInterface $demand): array
    {
        $orderings = [];
        $allowedGroupingValues = array_keys(GeneralUtility::makeInstance(GroupByValues::class)->getAll());
        $allowedSortByValues = array_keys(GeneralUtility::makeInstance(SortByValues::class)->getAll());
        $allowedSortByDirectionValues = ['asc', 'desc'];
        if (in_array($demand->getGroupBy(), $allowedGroupingValues, true)) {
            $orderings[$demand->getGroupBy()] = QueryInterface::ORDER_ASCENDING;
        }
        if (in_array($demand->getSortBy(), $allowedSortByValues, true)
            && in_array($demand->getSortByDirection(), $allowedSortByDirectionValues, true)
        ) {
            $orderings[$demand->getSortBy()] = strtoupper($demand->getSortByDirection());
        }
        return $orderings;
    }

    /**
     * @param int[] $uids
     * @return QueryResultInterface<int, Profile>
     */
    public function findByUids(array $uids, bool $showHidden = false): QueryResultInterface
    {
        $query = $this->createQuery();
        $this->matchSelectedUidsAcrossLanguages($query);
        $query->getQuerySettings()->setRespectStoragePage(false);
        if ($showHidden === true) {
            $this->includeHiddenRecords($query);
        }

        $query->matching($query->in('uid', $uids));
        return $query->execute();
    }

    /**
     * Resolve a single profile by uid including hidden (disabled) records.
     *
     * Used by {@see \FGTCLB\AcademicPersons\Controller\ProfileController::initializeDetailAction()}
     * to allow the detail view to display a hidden profile when the plugin
     * option "show hidden records" is enabled, since the default Extbase
     * argument mapping respects enable fields.
     */
    public function findByUidIncludingHidden(int $uid): ?Profile
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);
        $this->includeHiddenRecords($query);
        $query->matching($query->equals('uid', $uid));
        /** @var Profile|null $profile */
        $profile = $query->execute()->getFirst();
        return $profile;
    }

    /**
     * Find profiles for a frontend user. `$showHidden` exists for the synchronization and lifts
     * every enable field of the profile - hidden flag, start time, end time and frontend user
     * group - so that the data of a hidden, scheduled, expired or group restricted profile keeps
     * being updated, without its visibility ever being changed. It is not meant for display: the
     * frontend keeps the default (`$showHidden = false`), which respects all of them.
     *
     * @param int $frontendUserUid
     * @param bool $showHidden
     * @return QueryResultInterface<int, Profile>
     */
    public function findByFrontendUser(int $frontendUserUid, bool $showHidden = false): QueryResultInterface
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);
        if ($showHidden === true) {
            $this->includeRestrictedRecordsForSynchronization($query);
        }

        return $query
            ->matching(
                $query->contains('frontendUsers', $frontendUserUid)
            )
            ->execute();
    }
}
