<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersons\Domain\Repository;

use FGTCLB\AcademicPersons\Backend\FormEngine\ContractSelectScopeResolver;
use FGTCLB\AcademicPersons\Domain\Model\Contract;
use FGTCLB\AcademicPersons\Event\ModifyContractQueryEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Qom\ConstraintInterface;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * @extends Repository<Contract>
 */
class ContractRepository extends Repository
{
    /**
     * TYPO3 v12's Extbase `Repository` declares no event dispatcher at all - it arrives on the
     * parent with v13 - so this repository has to take one of its own to be able to dispatch on
     * both supported versions. `ProfileRepository` does the same, for the same reason.
     */
    protected EventDispatcherInterface $eventDispatcher;

    public function injectEventDispatcher(EventDispatcherInterface $eventDispatcher): void
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @return QueryResultInterface<int, Contract>
     */
    public function findAll(): QueryResultInterface
    {
        $query = $this->createQuery();
        // @todo Completely ignoring storage pages is a bad design, special for multi site instances.
        //       Needs a better way to deal with this hear and in other places.
        $query->getQuerySettings()->setRespectStoragePage(false);
        // Without this the order is whatever the DBMS yields, which is not the same
        // list twice once an index gives the planner an alternative (ACE-491). The table
        // carries TCA ctrl `sortby`/`default_sortby`, but Extbase reads neither - and its
        // `sorting` is scoped per parent profile through the inline relation, so a global
        // ORDER BY sorting would interleave meaninglessly across profiles. `uid` it is.
        $query->setOrderings(['uid' => QueryInterface::ORDER_ASCENDING]);
        return $query->execute();
    }

    /**
     * Resolve the contract records used to build the select items for the
     * `itemsProcFunc` of contract selection fields (TCA and FlexForm).
     *
     * The FormEngine `itemsProcFunc` parameters are passed through so the
     * query can be narrowed by context (effective pid, site, ...) later on
     * without touching the calling handler again.
     *
     * @param array{
     *      items: array<int, array{
     *       label?: string|null,
     *       value?: mixed,
     *       icon?: string|null,
     *       group?: string|null,
     *      }>,
     *      config: array<string, mixed>,
     *      TSconfig: array<string, mixed>|null,
     *      table: string,
     *      row: array<string, mixed>,
     *      field: string,
     *      effectivePid: int,
     *      site: Site|null,
     *      flexParentDatabaseRow?: array<string, mixed>|null,
     *      inlineParentUid?: int,
     *      inlineParentTableName?: string,
     *      inlineParentFieldName?: string,
     *      inlineParentConfig?: array<string, mixed>,
     *      inlineTopMostParentUid?: int,
     *      inlineTopMostParentTableName?: string,
     *      inlineTopMostParentFieldName?: string,
     *  } $parameters
     * @return QueryResultInterface<int, Contract>
     */
    public function getContractItemsForTcaItemsProcFunc(array $parameters): QueryResultInterface
    {
        $scope = GeneralUtility::makeInstance(ContractSelectScopeResolver::class)->resolve($parameters);

        return $this->findForBackendSelect($scope->storagePageIds, $scope->alwaysIncludeUids);
    }

    /**
     * The contracts a backend select offers, restricted to the pages an integrator listed
     * for the field in page TSconfig.
     *
     * An empty $storagePageIds means no restriction, which is what the selects did before
     * the setting existed: respecting the Extbase storage page instead empties them
     * wherever none is configured (ACE-431).
     *
     * @param int[] $storagePageIds
     * @param int[] $alwaysIncludeUids Offered wherever they are stored, so that opening and
     *                                 saving a record never drops a relation it already has
     * @return QueryResultInterface<int, Contract>
     */
    public function findForBackendSelect(array $storagePageIds, array $alwaysIncludeUids = []): QueryResultInterface
    {
        if ($storagePageIds === []) {
            return $this->findAll();
        }

        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);
        // This is the Extbase `in()`, not the query builder one of
        // docs/architecture/database-queries.md: `Typo3DbQueryParser` rejects an empty
        // list with `BadConstraintException` 1484828466 before it reaches any database,
        // so the two lists are checked rather than quoted.
        $constraints = [$query->in('pid', $storagePageIds)];
        if ($alwaysIncludeUids !== []) {
            $constraints[] = $query->in('uid', $alwaysIncludeUids);
        }
        $query->matching(count($constraints) === 1 ? $constraints[0] : $query->logicalOr(...$constraints));
        // Same ordering as `findAll()`, for the same reason - see there.
        $query->setOrderings(['uid' => QueryInterface::ORDER_ASCENDING]);

        return $query->execute();
    }

    /**
     * @param int[] $uids
     * @return QueryResultInterface<int, Contract>
     */
    public function findByUids(array $uids, bool $showHidden = false): QueryResultInterface
    {
        $query = $this->createQuery();
        // Selected uid's are default language and we need to configure extbase in away to
        // properly handle the overlay. This is adopted from the generic extbase backend
        // implementation.
        $currentLanguageAspect = $query->getQuerySettings()->getLanguageAspect();
        $changedLanguageAspect = new LanguageAspect(
            $currentLanguageAspect->getId(),
            $currentLanguageAspect->getContentId(),
            $currentLanguageAspect->getOverlayType() === LanguageAspect::OVERLAYS_OFF ? LanguageAspect::OVERLAYS_ON_WITH_FLOATING : $currentLanguageAspect->getOverlayType()
        );
        $query->getQuerySettings()->setLanguageAspect($changedLanguageAspect);
        $query->getQuerySettings()->setRespectStoragePage(false);
        $query->getQuerySettings()->setRespectSysLanguage(false);
        if ($showHidden === true) {
            // Include hidden (disabled) records; other enable fields
            // (deleted, start-/endtime, fe_group) stay in effect.
            $query->getQuerySettings()->setIgnoreEnableFields(true);
            $query->getQuerySettings()->setEnableFieldsToBeIgnored(['disabled']);
        }
        // The repository's own constraint comes first and the constraints the listeners collected
        // follow, combined with a logical AND. Same shape as `ProfileRepository::applyQuery()` -
        // see there for why a constraint a listener set with `matching()` is folded in, and why a
        // single constraint is not wrapped.
        /** @var ModifyContractQueryEvent $event */
        $event = $this->eventDispatcher->dispatch(new ModifyContractQueryEvent($query));
        $constraints = $event->getConstraints();
        $constraintSetByAListener = $query->getConstraint();
        if ($constraintSetByAListener instanceof ConstraintInterface) {
            $constraints[] = $constraintSetByAListener;
        }
        array_unshift($constraints, $query->in('uid', $uids));
        $query->matching(count($constraints) === 1 ? $constraints[0] : $query->logicalAnd(...$constraints));
        // Deterministic order only (ACE-491) - the order of the editor's selection is
        // deliberately not reproduced here: `in()` does not preserve it, and honouring
        // it would be a behaviour change beyond making the list reproducible. It is set after
        // the dispatch, so a listener that calls `setOrderings()` itself is overwritten.
        $query->setOrderings(['uid' => QueryInterface::ORDER_ASCENDING]);

        return $query->execute();
    }
}
