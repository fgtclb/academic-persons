<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Fgtclb\AcademicPersons\Domain\Repository;

use Fgtclb\AcademicPersons\DemandValues\GroupByValues;
use Fgtclb\AcademicPersons\DemandValues\SortByValues;
use Fgtclb\AcademicPersons\Domain\Model\Dto\DemandInterface;
use Fgtclb\AcademicPersons\Domain\Model\Profile;
use Fgtclb\AcademicPersons\Event\ModifyProfileDemandEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
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
     * @return QueryResultInterface<Profile>
     */
    public function findByDemand(DemandInterface $demand): QueryResultInterface
    {
        $query = $this->createQuery();

        /** @var ModifyProfileDemandEvent $event */
        $event = $this->eventDispatcher->dispatch(new ModifyProfileDemandEvent($demand));
        $demand = $event->getDemand();

        $this->applyDemandForQuery($query, $demand);

        return $query->execute();
    }

    /**
     * @param QueryInterface<Profile> $query
     */
    private function applyDemandForQuery(QueryInterface $query, DemandInterface $demand): void
    {
        $filters = $this->setFilters($query, $demand);
        if ($filters !== null) {
            $query->matching($filters);
        }

        if (empty($demand->getProfileList())) {
            $query->setOrderings($this->getOrderingsFromDemand($demand));
        }
    }

    /**
     * @param QueryInterface<Profile> $query
     */
    private function setFilters(QueryInterface $query, DemandInterface $demand): ?ConstraintInterface
    {
        $filters = [];

        if (!empty($demand->getProfileList())) {
            $profileUidArray = GeneralUtility::intExplode(',', $demand->getProfileList(), true);
            $filters[] = $query->in('uid', $profileUidArray);
        }

        if ($demand->getAlphabetFilter() != '') {
            $filters[] = $query->like('last_name', $demand->getAlphabetFilter() . '%');
        }

        if (empty($filters)) {
            return null;
        }

        return $query->logicalAnd(...$filters);
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
        
        if (
            in_array($demand->getSortBy(), $allowedSortByValues, true) &&
            in_array($demand->getSortByDirection(), $allowedSortByDirectionValues, true)
        ) {
            $orderings[$demand->getSortBy()] = strtoupper($demand->getSortByDirection());
        }

        return $orderings;
    }
}
