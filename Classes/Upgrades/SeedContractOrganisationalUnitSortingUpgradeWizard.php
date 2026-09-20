<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersons\Upgrades;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Install\Attribute\UpgradeWizard;
use TYPO3\CMS\Install\Updates\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

/**
 * Seeds `organisational_unit_sorting`, the sort column the organisational unit
 * relation gained in 2.4.
 *
 * A contract is an inline child of its profile and of its organisational unit.
 * Both relations wrote the shared `sorting` column until now, so saving a unit
 * renumbered its contracts across the profiles that own them. The unit relation
 * orders by `organisational_unit_sorting` from 2.4 on, and every contract that
 * existed before the update carries `0` in it - which would leave the unit form
 * showing its contracts in whatever order the database happens to return.
 *
 * This wizard appends every contract that has no rank yet to the list of its
 * organisational unit, in the current (`sorting`, `uid`) order - the order the unit
 * form shows today, with its ties settled by uid. On an installation that has just
 * updated nothing has a rank, so that numbers everything 1..n in exactly that order.
 *
 * **Appending rather than renumbering** is what makes it safe to run again: the
 * order in the unit form is an arrangement from this version on, and a second run
 * that renumbered from `sorting` would discard it without a word. Records the
 * wizard has already numbered are left alone. Running it later is therefore a
 * repair, not a reset - though there should be nothing left to repair, because
 * {@see \FGTCLB\AcademicPersons\Hook\ContractSortingHook} gives every contract
 * that joins an organisational unit afterwards a rank of its own.
 *
 * **Every row carrying an organisational unit is numbered**, deleted ones
 * included. The column is a rank within the unit, and ordering only ever
 * compares rows: leaving a row at `0` would put it above every numbered sibling
 * the moment it is restored. Numbering by the parent uid the row carries is the
 * same grouping `RelationHandler` itself uses when it writes the column, so the
 * relative order of any set of rows it lists is the order this wizard seeds.
 */
#[UpgradeWizard('academicPersons_seedContractOrganisationalUnitSorting')]
final class SeedContractOrganisationalUnitSortingUpgradeWizard implements UpgradeWizardInterface
{
    private const TABLE = 'tx_academicpersons_domain_model_contract';

    /**
     * The secondary inline relations of the table, as parent column => sort column.
     *
     * @var array<non-empty-string, non-empty-string>
     */
    private const RELATIONS = [
        'organisational_unit' => 'organisational_unit_sorting',
    ];

    /**
     * The manual sort field of the table, which the owning relation writes. It is the
     * order the secondary forms showed before their own column existed, so it is the
     * order rows without a rank are appended in.
     */
    private const SORT_FIELD_FALLBACK = 'sorting';

    /**
     * How many uids one UPDATE statement carries at most. The list is inlined into
     * the SQL by the quoting helper, so the bound is the statement length a driver
     * accepts, not its placeholder limit.
     */
    private const UPDATE_CHUNK_SIZE = 500;

    public function __construct(
        private readonly ConnectionPool $connectionPool,
    ) {}

    public function getTitle(): string
    {
        return 'Seed the contract sort order of academic organisational units';
    }

    public function getDescription(): string
    {
        return 'Contracts are inline children of their profile and of their organisational unit, and'
            . ' both relations wrote the same sort column until now, so saving an organisational unit'
            . ' could rearrange the contracts of a profile. The organisational unit relation has a sort'
            . ' column of its own from 2.4 on. This wizard fills it with the order the organisational'
            . ' unit forms show today.';
    }

    public function updateNecessary(): bool
    {
        $columns = $this->connectionPool
            ->getConnectionForTable(self::TABLE)
            ->createSchemaManager()
            ->listTableColumns(self::TABLE);
        foreach (self::RELATIONS as $parentField => $sortField) {
            if (!isset($columns[$sortField])) {
                // The column comes with "ext_tables.sql", and the Upgrade Wizard module
                // evaluates updateNecessary() before the DatabaseUpdatedPrerequisite has
                // run. A column that is not there yet means there is work to do, not that
                // there is none.
                return true;
            }
            if ($this->countUnseededRows($parentField, $sortField) > 0) {
                return true;
            }
        }
        return false;
    }

    public function executeUpdate(): bool
    {
        foreach (self::RELATIONS as $parentField => $sortField) {
            $this->seedRelation($parentField, $sortField);
        }
        return true;
    }

    /**
     * @return list<class-string>
     */
    public function getPrerequisites(): array
    {
        return [DatabaseUpdatedPrerequisite::class];
    }

    /**
     * Numbers the rows of one relation that have no rank yet, **appending** them to
     * the rows that have one.
     *
     * Append rather than renumber, because the wizard is a command an administrator
     * can repeat: renumbering from ({@see SORT_FIELD_FALLBACK}, `uid`) would discard
     * the arrangement the secondary form exists to make possible, silently and on a
     * second run. On the first run nothing has a rank, so appending numbers
     * everything 1..n in exactly that order.
     */
    private function seedRelation(string $parentField, string $sortField): void
    {
        /** @var array<int, int> $highestRank The highest rank taken, per parent uid. */
        $highestRank = [];
        /** @var array<int, list<int>> $unranked The uids without a rank, per parent uid, in the order they are to be appended in. */
        $unranked = [];
        foreach ($this->fetchRowsToNumber($parentField, $sortField) as $row) {
            $parentUid = (int)$row[$parentField];
            $rank = (int)$row[$sortField];
            if ($rank > 0) {
                $highestRank[$parentUid] = max($highestRank[$parentUid] ?? 0, $rank);
                continue;
            }
            $unranked[$parentUid][] = (int)$row['uid'];
        }

        /** @var array<int, list<int>> $uidsByRank */
        $uidsByRank = [];
        foreach ($unranked as $parentUid => $uids) {
            $rank = $highestRank[$parentUid] ?? 0;
            foreach ($uids as $uid) {
                $uidsByRank[++$rank][] = $uid;
            }
        }
        foreach ($uidsByRank as $rank => $uids) {
            foreach (array_chunk($uids, self::UPDATE_CHUNK_SIZE) as $chunk) {
                $this->writeSorting($sortField, $chunk, $rank);
            }
        }
    }

    private function countUnseededRows(string $parentField, string $sortField): int
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE);
        $queryBuilder->getRestrictions()->removeAll();
        return (int)$queryBuilder
            ->count('uid')
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->gt(
                    $parentField,
                    $queryBuilder->createNamedParameter(0, Connection::PARAM_INT),
                ),
                $queryBuilder->expr()->eq(
                    $sortField,
                    $queryBuilder->createNamedParameter(0, Connection::PARAM_INT),
                ),
            )
            ->executeQuery()
            ->fetchOne();
    }

    /**
     * Every row that carries an organisational unit, grouped by it and in the order
     * the unit form shows today. The ordering is part of the result, so it is the
     * statement that has to produce it - a re-sort in PHP would have to reproduce the
     * collation.
     *
     * @return list<array<string, int|string>>
     */
    private function fetchRowsToNumber(string $parentField, string $sortField): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE);
        $queryBuilder->getRestrictions()->removeAll();
        /** @var list<array<string, int|string>> $rows */
        $rows = $queryBuilder
            ->select('uid', $parentField, $sortField)
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->gt(
                    $parentField,
                    $queryBuilder->createNamedParameter(0, Connection::PARAM_INT),
                ),
            )
            ->orderBy($parentField)
            ->addOrderBy(self::SORT_FIELD_FALLBACK)
            ->addOrderBy('uid')
            ->executeQuery()
            ->fetchAllAssociative();
        return $rows;
    }

    /**
     * One statement per rank rather than per record: the ranks of a table run 1..n
     * per parent, so their number is the size of the largest list, while the number
     * of records is the size of the whole table.
     *
     * Everything is built on the query builder that executes it: a named parameter
     * belongs to the builder that created it, and `set()` creates one of its own
     * (see `docs/architecture/database-queries.md`, rule 2). The uid list is quoted
     * with the helper meant for it (rule 1).
     *
     * @param list<int> $uids
     */
    private function writeSorting(string $sortField, array $uids, int $sorting): void
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE);
        $queryBuilder
            ->update(self::TABLE)
            ->set($sortField, $sorting, true, Connection::PARAM_INT)
            ->where(
                $queryBuilder->expr()->in(
                    'uid',
                    $queryBuilder->quoteArrayBasedValueListToIntegerList($uids),
                ),
            )
            ->executeStatement();
    }
}
