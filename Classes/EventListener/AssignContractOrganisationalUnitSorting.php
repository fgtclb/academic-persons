<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersons\EventListener;

use FGTCLB\AcademicPersons\Domain\Model\Contract;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject;
use TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface;
use TYPO3\CMS\Extbase\Event\Persistence\EntityAddedToPersistenceEvent;
use TYPO3\CMS\Extbase\Event\Persistence\EntityUpdatedInPersistenceEvent;

/**
 * Keeps `organisational_unit_sorting` filled for contracts written through
 * Extbase rather than through the DataHandler.
 *
 * {@see \FGTCLB\AcademicPersons\Hook\ContractSortingHook} covers the backend, and
 * it covers it alone: a DataHandler hook never sees an Extbase write. The profile
 * editing frontend of `academic_persons_edit` creates and updates contracts
 * through `ContractRepository`, with the organisational unit taken from its form,
 * so without this listener every contract an editor creates there keeps `0` and
 * sorts above everything in that unit's list, tied with the others.
 *
 * Both persistence events are listened to rather than the repository being
 * decorated, because they are what *every* Extbase write passes through -
 * `PersistenceManager::update()` called without a repository included, which is
 * how `academic_persons_edit` reorders a profile's records.
 *
 * The rule is the one the DataHandler hook applies: **a contract that joins an
 * organisational unit is appended to the end of that unit's list**, whether it is
 * created with one or given one later. A contract that keeps the unit it already
 * had keeps its position, so an arrangement made in the unit form survives every
 * later save from the frontend. Clearing the unit resets the column.
 *
 * Whether the unit changed is read off the entity, not off the database: the row
 * has already been written when the event fires, but `_memorizeCleanState()` runs
 * only afterwards (`Extbase\Persistence\Generic\Backend::persistObject()`), so the
 * clean property still holds the unit the contract had before this write.
 */
final class AssignContractOrganisationalUnitSorting
{
    private const TABLE = 'tx_academicpersons_domain_model_contract';
    private const PARENT_FIELD = 'organisational_unit';
    private const SORT_FIELD = 'organisational_unit_sorting';
    private const PROPERTY = 'organisationalUnit';

    public function __construct(
        private readonly ConnectionPool $connectionPool,
    ) {}

    public function afterEntityAdded(EntityAddedToPersistenceEvent $event): void
    {
        $this->assignRank($event->getObject(), true);
    }

    public function afterEntityUpdated(EntityUpdatedInPersistenceEvent $event): void
    {
        $this->assignRank($event->getObject(), false);
    }

    private function assignRank(DomainObjectInterface $object, bool $isNew): void
    {
        if (!$object instanceof Contract) {
            return;
        }
        // The row Extbase wrote, which is not `uid` for a language overlay: the data
        // mapper leaves `uid` at the default-language record and puts the
        // translation's own uid into `_localizedUid`, which is what
        // `Backend::updateObject()` writes to.
        $uid = (int)($object->_getProperty(AbstractDomainObject::PROPERTY_LOCALIZED_UID) ?? $object->getUid() ?? 0);
        if ($uid <= 0) {
            return;
        }
        $unitUid = (int)($object->getOrganisationalUnit()?->getUid() ?? 0);
        $storedRank = $this->fetchStoredRank($uid);
        if ($unitUid <= 0) {
            // The contract has no unit any more; a rank in a list it left would be
            // restored together with the unit by a later, unrelated save.
            if ($storedRank > 0) {
                $this->writeRank($uid, 0);
            }
            return;
        }
        $previousUnit = $object->_getCleanProperty(self::PROPERTY);
        $previousUnitUid = $previousUnit instanceof DomainObjectInterface ? (int)($previousUnit->getUid() ?? 0) : 0;
        $joinedThisUnit = $isNew || $previousUnitUid !== $unitUid;
        if (!$joinedThisUnit && $storedRank > 0) {
            return;
        }
        $rank = $this->positionToAppend($unitUid, $uid);
        if ($rank === null || $rank === $storedRank) {
            // Nothing to move: either the unit's list has not been seeded yet, or the
            // position is the one the record already has - which is what the second of
            // the two events sees for a contract that was just created.
            return;
        }
        $this->writeRank($uid, $rank);
    }

    /**
     * The position to append to the unit's list, or `null` while that list has none
     * to append to.
     *
     * A list whose rows all sit at `0` has not been seeded yet: the upgrade wizard
     * has not run, and it is the wizard that knows the order those rows are in
     * today. Numbering one of them now - because it happened to be saved first -
     * would make the wizard treat it as arranged and append the rest behind it. The
     * contract itself is excluded from both numbers: its row already carries the new
     * unit when this runs.
     */
    private function positionToAppend(int $unitUid, int $uid): ?int
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE);
        $queryBuilder->getRestrictions()->removeAll();
        $row = $queryBuilder
            ->selectLiteral(
                'MAX(' . $queryBuilder->quoteIdentifier(self::SORT_FIELD) . ') AS ' . $queryBuilder->quoteIdentifier('highest_rank'),
                'COUNT(' . $queryBuilder->quoteIdentifier('uid') . ') AS ' . $queryBuilder->quoteIdentifier('other_rows'),
            )
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->eq(
                    self::PARENT_FIELD,
                    $queryBuilder->createNamedParameter($unitUid, Connection::PARAM_INT),
                ),
                $queryBuilder->expr()->neq(
                    'uid',
                    $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT),
                ),
            )
            ->executeQuery()
            ->fetchAssociative();
        $highest = (int)($row['highest_rank'] ?? 0);
        if ($highest === 0 && (int)($row['other_rows'] ?? 0) > 0) {
            return null;
        }
        return $highest + 1;
    }

    private function fetchStoredRank(int $uid): int
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE);
        $queryBuilder->getRestrictions()->removeAll();
        return (int)$queryBuilder
            ->select(self::SORT_FIELD)
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT),
                ),
            )
            ->executeQuery()
            ->fetchOne();
    }

    private function writeRank(int $uid, int $rank): void
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE);
        $queryBuilder
            ->update(self::TABLE)
            ->set(self::SORT_FIELD, $rank, true, Connection::PARAM_INT)
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT),
                ),
            )
            ->executeStatement();
    }
}
