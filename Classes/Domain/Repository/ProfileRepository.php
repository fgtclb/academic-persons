<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersons\Domain\Repository;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use FGTCLB\AcademicBase\Domain\Model\Dto\PluginControllerActionContextInterface;
use FGTCLB\AcademicPersons\DemandValues\AlphabetFilterLetters;
use FGTCLB\AcademicPersons\DemandValues\GroupByValues;
use FGTCLB\AcademicPersons\DemandValues\SortByValues;
use FGTCLB\AcademicPersons\Domain\Model\Dto\DemandInterface;
use FGTCLB\AcademicPersons\Domain\Model\Profile;
use FGTCLB\AcademicPersons\Event\ModifyProfileDemandEvent;
use FGTCLB\AcademicPersons\Event\ModifyProfileQueryEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Qom\ConstraintInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Storage\Typo3DbQueryParser;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * @extends Repository<Profile>
 */
class ProfileRepository extends Repository
{
    protected EventDispatcherInterface $eventDispatcher;

    protected TcaSchemaFactory $tcaSchemaFactory;

    public function injectEventDispatcher(EventDispatcherInterface $eventDispatcher): void
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function injectTcaSchemaFactory(TcaSchemaFactory $tcaSchemaFactory): void
    {
        $this->tcaSchemaFactory = $tcaSchemaFactory;
    }

    /**
     * The enable fields the frontend user synchronization lifts, keyed by the name Extbase
     * takes, each with the capability telling whether the profile table declares it.
     *
     * @var array<string, TcaSchemaCapability>
     */
    private const SYNCHRONIZATION_IGNORED_ENABLE_FIELDS = [
        'disabled' => TcaSchemaCapability::RestrictionDisabledField,
        'starttime' => TcaSchemaCapability::RestrictionStartTime,
        'endtime' => TcaSchemaCapability::RestrictionEndTime,
        'fe_group' => TcaSchemaCapability::RestrictionUserGroup,
    ];

    /**
     * Applied whenever nothing else asks for an order, so that an unordered result is
     * reproducible rather than left to the DBMS. The plugin offers "none" as a sorting
     * option, and "no sorting the editor chose" still has to mean the same list twice.
     * A demanded ordering gets it appended as a tiebreaker instead (array union, the
     * demand wins on a key collision), so profiles equal in the demanded ordering - two
     * people sharing a last name - keep a stable relative order as well (ACE-491).
     *
     * "uid" ascending is what SQLite, MySQL and MariaDB return in practice, so installations
     * on them see no change; PostgreSQL promises no order, and an index lets it return another.
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
     * @param PluginControllerActionContextInterface|null $context The plugin the query is rendered
     *        for, handed to the listeners of {@see ModifyProfileQueryEvent}. A caller outside the
     *        plugins - a command, a backend module - passes none.
     * @return QueryResultInterface<int, Profile>
     */
    public function findByDemand(
        DemandInterface $demand,
        ?PluginControllerActionContextInterface $context = null,
    ): QueryResultInterface {
        $query = $this->createQuery();
        $demand = $this->eventDispatcher->dispatch(new ModifyProfileDemandEvent($demand))->getDemand();
        $this->applyDemandSettings($query, $demand);
        [$constraint, $orderings] = $this->resolveDemandForQuery($query, $demand);
        $this->applyQuery($query, $constraint, $orderings, $demand, $context);
        return $query->execute();
    }

    /**
     * Which letters of the letter navigation lead to a list that is not empty: every letter of
     * {@see AlphabetFilterLetters::LETTERS}, `true` when the list this demand renders for that
     * letter holds at least one profile.
     *
     * The list query is built the way {@see self::findByDemand()} builds it - both events, the
     * demand settings, the filters and the plugin context - from a copy of the demand without
     * its letter, so the active letter never narrows the others. It is then run the way core's
     * own `count()` runs it, with one conditional aggregate per letter as the select list: one
     * statement, whatever the number of profiles. Each aggregate uses the predicate of the
     * letter filter itself - `LIKE`, `ILIKE` on PostgreSQL, against `last_name` - so a name is
     * available under exactly the letter the list files it under on the DBMS at hand. That is a
     * question of collation, and a first character compared in PHP would answer it differently.
     *
     * A manual selection ignores the letter filter, so every letter yields the whole selection
     * and is `true`.
     *
     * Two limits, both shared with the list's own pagination count, which is SQL only as well:
     * in a workspace preview a profile deleted or hidden only in the workspace still counts, and
     * a listener of `ModifyListProfilesEvent` that replaces the result is not reflected. Live
     * and in the frontend, the answer is exact. Outside the frontend Extbase applies no
     * versioning rules to the records of a query, so there the letters agree with the list's
     * count - live records only - and not necessarily with its records.
     *
     * The query parser is internal to Extbase. It is used here exactly as
     * `Typo3DbBackend::getObjectCountByQuery()` uses it, identically on TYPO3 v13 and v14,
     * because it is the only source of the list's language, visibility and join handling that
     * does not duplicate it. Should it go, 26 `count()` calls give the same answer.
     *
     * @param PluginControllerActionContextInterface|null $context The plugin the list is rendered
     *        for, handed to the listeners of {@see ModifyProfileQueryEvent} as the list query does.
     * @return array<string, bool>
     */
    public function findAlphabetFilterLetters(
        DemandInterface $demand,
        ?PluginControllerActionContextInterface $context = null,
    ): array {
        $demand = clone $demand;
        $demand->setAlphabetFilter('');
        // Cloned again: a listener may hand back an object it keeps using, and one with a
        // letter of its own, which still must not narrow the other letters.
        /** @var ModifyProfileDemandEvent $demandEvent */
        $demandEvent = $this->eventDispatcher->dispatch(new ModifyProfileDemandEvent($demand));
        $demand = clone $demandEvent->getDemand();
        $demand->setAlphabetFilter('');
        if ($demand->getProfileList() !== '') {
            return array_fill_keys(AlphabetFilterLetters::LETTERS, true);
        }

        $query = $this->createQuery();
        $this->applyDemandSettings($query, $demand);
        [$constraint] = $this->resolveDemandForQuery($query, $demand);
        $this->applyQuery($query, $constraint, [], $demand, $context);

        $queryBuilder = GeneralUtility::makeInstance(Typo3DbQueryParser::class)
            ->convertQueryToDoctrineQueryBuilder($query)
            ->resetOrderBy()
            ->resetGroupBy();
        $workspaceId = (int)GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('workspace', 'id');
        $queryBuilder->getRestrictions()->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, $workspaceId));

        // The table or its alias is quoted already, the column is not - as in core's count().
        $source = $queryBuilder->getFrom()[0];
        $lastName = ($source->alias ?: $source->table) . '.' . $queryBuilder->quoteIdentifier('last_name');
        $operator = $queryBuilder->getConnection()->getDatabasePlatform() instanceof PostgreSQLPlatform ? 'ILIKE' : 'LIKE';
        $aggregates = [];
        foreach (AlphabetFilterLetters::LETTERS as $letter) {
            // MAX() makes the duplicate rows of the contract joins irrelevant, and without a
            // GROUP BY there is exactly one row, also for an empty list.
            $aggregates[] = sprintf(
                'MAX(CASE WHEN %s %s %s THEN 1 ELSE 0 END) AS %s',
                $lastName,
                $operator,
                $queryBuilder->quote($letter . '%'),
                $queryBuilder->quoteIdentifier('letter_' . $letter),
            );
        }
        $row = $queryBuilder->selectLiteral(...$aggregates)->executeQuery()->fetchAssociative() ?: [];

        $letters = [];
        foreach (AlphabetFilterLetters::LETTERS as $letter) {
            $letters[$letter] = (int)($row['letter_' . $letter] ?? 0) === 1;
        }
        return $letters;
    }

    /**
     * Dispatch {@see ModifyProfileQueryEvent} and apply what this repository and the listeners
     * together ask of the query.
     *
     * The repository's own constraint comes first and the collected ones follow, combined with a
     * logical AND: a constraint narrows the result and can never widen it. That guarantee covers
     * the constraints and nothing else - the query settings, the limit and the offset a listener
     * can reach through the query object are read after this method and are not guarded. The
     * ordering is set after the dispatch and is therefore always this repository's, which is the
     * editor's choice in the content element and not a listener's.
     *
     * A listener that expressed its condition with `matching()` on the query instead of
     * {@see ModifyProfileQueryEvent::addConstraint()} is folded in rather than dropped: nothing
     * has called `matching()` at this point, so anything sitting there came from a listener, and
     * taking it as one more constraint keeps the narrowing guarantee without losing what it
     * meant. It is not the documented way, because `matching()` overwrites and only the last
     * listener to call it would survive.
     *
     * A single constraint is passed on unwrapped: `Query::logicalAnd()` pads a one element list
     * with an always true `uid > 0` (`case 1`, identical on v13 and v14), which would otherwise
     * sit in every query of every installation that has no listener.
     *
     * @param QueryInterface<Profile> $query
     * @param array<string, string> $orderings
     */
    private function applyQuery(
        QueryInterface $query,
        ?ConstraintInterface $ownConstraint,
        array $orderings,
        ?DemandInterface $demand,
        ?PluginControllerActionContextInterface $context,
    ): void {
        /** @var ModifyProfileQueryEvent $event */
        $event = $this->eventDispatcher->dispatch(new ModifyProfileQueryEvent($query, $demand, $context));
        $constraints = $event->getConstraints();
        $constraintSetByAListener = $query->getConstraint();
        if ($constraintSetByAListener instanceof ConstraintInterface) {
            $constraints[] = $constraintSetByAListener;
        }
        if ($ownConstraint !== null) {
            array_unshift($constraints, $ownConstraint);
        }
        if ($constraints !== []) {
            $query->matching(count($constraints) === 1 ? $constraints[0] : $query->logicalAnd(...$constraints));
        }
        $query->setOrderings($orderings);
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
     * profile table declares them. They decide when and to whom a profile is shown, not
     * whether the person behind it exists - and a command-line run has no frontend user
     * group to match anyway. Deleted profiles stay excluded, and an enable field added later
     * beyond these four stays in effect.
     *
     * Synchronization only. Display paths use {@see self::includeHiddenRecords()}, which
     * keeps the visibility window in place.
     *
     * @param QueryInterface<Profile> $query
     */
    private function includeRestrictedRecordsForSynchronization(QueryInterface $query): void
    {
        $schema = $this->tcaSchemaFactory->get('tx_academicpersons_domain_model_profile');
        $enableFieldsToBeIgnored = [];
        foreach (self::SYNCHRONIZATION_IGNORED_ENABLE_FIELDS as $enableField => $capability) {
            if ($schema->hasCapability($capability)) {
                $enableFieldsToBeIgnored[] = $enableField;
            }
        }
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
     * What the demand asks of the query: the query settings it implies are applied to $query
     * directly, its constraint and its orderings are returned so that {@see self::applyQuery()}
     * can apply them after the listeners had their say.
     *
     * @param QueryInterface<Profile> $query
     * @return array{0: ConstraintInterface|null, 1: array<string, string>}
     */
    private function resolveDemandForQuery(QueryInterface $query, DemandInterface $demand): array
    {
        // Direct selected profiles make all filters and the demanded ordering obsolete and are
        // handled first. The order of the selection is not reproducible in the query - `in()`
        // does not preserve it - so `ProfileController::listAction()` restores it in PHP and
        // paginates the restored list. The query still gets the deterministic fallback
        // ordering, because the result is what listeners of `ModifyListProfilesEvent` receive
        // and an unordered result is not the same list twice (ACE-482, ACE-491).
        if ($demand->getProfileList() !== '') {
            $profileUidArray = GeneralUtility::intExplode(',', $demand->getProfileList(), true);
            $this->matchSelectedUidsAcrossLanguages($query);
            return [$query->in('uid', $profileUidArray), self::FALLBACK_ORDERINGS];
        }

        return [
            $this->setFilters($query, $demand),
            $this->getOrderingsFromDemand($demand) + self::FALLBACK_ORDERINGS,
        ];
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
     * The signature is deliberately unchanged: a project that XCLASSes or overrides this method
     * keeps loading. The plugins call {@see self::findByUidsWithContext()} instead, so such an
     * override no longer reaches them - see the `Breaking-*.rst` of this change.
     *
     * @param int[] $uids
     * @return QueryResultInterface<int, Profile>
     */
    public function findByUids(array $uids, bool $showHidden = false): QueryResultInterface
    {
        return $this->findByUidsWithContext($uids, null, $showHidden);
    }

    /**
     * The uid lookup of the selected-profiles plugin. It differs from {@see self::findByUids()}
     * in nothing but the context it hands to the listeners of {@see ModifyProfileQueryEvent},
     * which a listener such as a consent filter needs to read the content element's settings.
     *
     * @param int[] $uids
     * @return QueryResultInterface<int, Profile>
     */
    public function findByUidsWithContext(
        array $uids,
        ?PluginControllerActionContextInterface $context,
        bool $showHidden = false,
    ): QueryResultInterface {
        $query = $this->createQuery();
        $this->matchSelectedUidsAcrossLanguages($query);
        $query->getQuerySettings()->setRespectStoragePage(false);
        if ($showHidden === true) {
            $this->includeHiddenRecords($query);
        }

        // Deterministic order only (ACE-491) - the order of the editor's selection is
        // deliberately not reproduced here: `in()` does not preserve it, and honouring
        // it would be a behaviour change beyond making the list reproducible.
        $this->applyQuery($query, $query->in('uid', $uids), self::FALLBACK_ORDERINGS, null, $context);
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
     * Resolve a single profile by uid for an announcement of its update, ignoring the hidden
     * flag, start time, end time and frontend user group like {@see self::findByFrontendUser()}
     * with `$showHidden`: a profile outside its visibility window is kept up to date all the
     * same. Not meant for display - {@see self::findByUidIncludingHidden()} is.
     *
     * The row is returned as it is stored, whatever language the context is in, so an
     * announcement carries the default-language record, never a translation overlay.
     * The caller passes the uid of a default-language profile: the finder does not
     * check the language, a translation uid returns the translation row.
     *
     * @internal for the announcement of DataHandler saves, not part of the public API.
     */
    public function findByUidForSynchronization(int $uid): ?Profile
    {
        $query = $this->createQuery();
        $query->getQuerySettings()
            ->setRespectStoragePage(false)
            ->setRespectSysLanguage(false)
            ->setLanguageAspect(new LanguageAspect(0, 0, LanguageAspect::OVERLAYS_OFF));
        $this->includeRestrictedRecordsForSynchronization($query);
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

        $query->setOrderings(self::FALLBACK_ORDERINGS);

        return $query
            ->matching(
                $query->contains('frontendUsers', $frontendUserUid)
            )
            ->execute();
    }
}
