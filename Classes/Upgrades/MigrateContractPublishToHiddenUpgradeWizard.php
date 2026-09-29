<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersons\Upgrades;

use Symfony\Component\DependencyInjection\Attribute\Exclude;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Install\Attribute\UpgradeWizard;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

/**
 * Carries the removed contract flag `publish` into `hidden`: a contract that was
 * not published is hidden.
 *
 * **Not registered.** No public view ever read `publish`, and every contract of an
 * installation that gave it no meaning of its own carries its default `0`. Offered
 * there, the wizard would hide every contract of the site, and `upgrade:run`
 * without a name would do it unasked. The `Exclude` attribute keeps the class out
 * of the service container of this extension, so the core does not list it. A
 * project whose own code honoured the flag declares the class in the
 * `Services.yaml` of its site package with `autoconfigure: true`, which reads the
 * `UpgradeWizard` attribute and registers it:
 *
 * ```yaml
 * services:
 *   FGTCLB\AcademicPersons\Upgrades\MigrateContractPublishToHiddenUpgradeWizard:
 *     autowire: true
 *     autoconfigure: true
 * ```
 *
 * **The default language record decides.** `hidden` is `l10n_mode: exclude`, so a
 * translation has to carry the value of its default language record. The core
 * copies such a column only inside a DataHandler write, so the wizard writes the
 * translations itself: every translation of a record that was not published is
 * hidden as well, and the flag of the translation is not read. A record without
 * a default language record, a translation of its own or one whose default
 * record is gone, decides by its own flag.
 *
 * **Every row is migrated**, live records and workspace versions, deleted ones
 * included, so that a version that is published or a record that is restored
 * keeps the meaning it had. A workspace version of a translation keeps the uid
 * of the live default record in `l10n_parent`, so it follows the version of that
 * record in its own workspace where there is one, and the live record otherwise:
 * the record it is shown with. A published contract keeps its visibility, hidden
 * or not: a hidden contract was left out before as well.
 *
 * **The source column** is `publish`, or `zzz_deleted_publish` once the database
 * analyser has renamed it for removal. With neither, there is nothing to migrate.
 * The wizard asks the database for the column, since the TCA no longer knows it.
 * It uses `listTableColumns()`, as the other wizards do: the replacements of
 * doctrine/dbal 4.4 are missing from the 4.2 and 4.3 that earlier TYPO3 13.4
 * releases require.
 */
#[Exclude]
#[UpgradeWizard('academicPersons_migrateContractPublishToHidden')]
final readonly class MigrateContractPublishToHiddenUpgradeWizard implements UpgradeWizardInterface
{
    private const TABLE = 'tx_academicpersons_domain_model_contract';

    /**
     * The names the removed column can have, in the order they are looked for.
     */
    private const SOURCE_COLUMNS = ['publish', 'zzz_deleted_publish'];

    /**
     * How many uids one statement carries at most. The list is inlined into the SQL
     * by the quoting helper, so the bound is the statement length a driver accepts,
     * not its placeholder limit.
     */
    private const UPDATE_CHUNK_SIZE = 500;

    public function __construct(
        private ConnectionPool $connectionPool,
    ) {}

    public function getTitle(): string
    {
        return 'Hide academic contracts that were not published';
    }

    public function getDescription(): string
    {
        return 'The contract field "Show this contract online?" (publish) is removed in 3.0 in favour of'
            . ' the visibility of the contract (hidden). This wizard hides every contract that was not'
            . ' published, and its translations. Run it only when your own code gave the field a meaning:'
            . ' otherwise every contract carries the default "not published" and all of them are hidden.';
    }

    public function updateNecessary(): bool
    {
        $sourceColumn = $this->findSourceColumn();
        return $sourceColumn !== null && $this->findUidsToHide($sourceColumn) !== [];
    }

    public function executeUpdate(): bool
    {
        $sourceColumn = $this->findSourceColumn();
        if ($sourceColumn === null) {
            return true;
        }
        foreach (array_chunk($this->findUidsToHide($sourceColumn), self::UPDATE_CHUNK_SIZE) as $uids) {
            $this->hide($uids);
        }
        return true;
    }

    /**
     * @return list<class-string>
     */
    public function getPrerequisites(): array
    {
        return [];
    }

    private function findSourceColumn(): ?string
    {
        $columns = $this->connectionPool
            ->getConnectionForTable(self::TABLE)
            ->createSchemaManager()
            ->listTableColumns(self::TABLE);
        foreach (self::SOURCE_COLUMNS as $columnName) {
            if (isset($columns[$columnName])) {
                return $columnName;
            }
        }
        return null;
    }

    /**
     * The visible rows the migration hides, in uid order.
     *
     * The rule needs the flag of a row's default record in the row's own
     * workspace, which a single statement per table cannot express on every
     * database system, so the rows are read once and decided here. The wizard
     * runs once per installation, and the table holds a few columns per contract.
     *
     * @return list<int>
     */
    private function findUidsToHide(string $sourceColumn): array
    {
        $rows = $this->fetchRows($sourceColumn);
        /** @var array<int, bool> $publishedByUid the flag of every record that decides, by uid */
        $publishedByUid = [];
        /** @var array<string, bool> $publishedByVersion the flag of every workspace version of such a record, by "live uid:workspace" */
        $publishedByVersion = [];
        foreach ($rows as $row) {
            if ($row['l10n_parent'] !== 0) {
                continue;
            }
            $publishedByUid[$row['uid']] = $row['published'];
            if ($row['t3ver_oid'] > 0) {
                $publishedByVersion[$row['t3ver_oid'] . ':' . $row['t3ver_wsid']] = $row['published'];
            }
        }
        $uids = [];
        foreach ($rows as $row) {
            if ($row['hidden']) {
                continue;
            }
            $published = $row['published'];
            if ($row['l10n_parent'] !== 0) {
                $published = $publishedByVersion[$row['l10n_parent'] . ':' . $row['t3ver_wsid']]
                    ?? $publishedByUid[$row['l10n_parent']]
                    ?? $published;
            }
            if (!$published) {
                $uids[] = $row['uid'];
            }
        }
        return $uids;
    }

    /**
     * Every row, deleted ones and workspace versions included, ordered by uid.
     *
     * @return list<array{uid: int, l10n_parent: int, t3ver_oid: int, t3ver_wsid: int, hidden: bool, published: bool}>
     */
    private function fetchRows(string $sourceColumn): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE);
        $queryBuilder->getRestrictions()->removeAll();
        $result = $queryBuilder
            ->select('uid', 'l10n_parent', 't3ver_oid', 't3ver_wsid', 'hidden', $sourceColumn)
            ->from(self::TABLE)
            ->orderBy('uid')
            ->executeQuery();
        $rows = [];
        while ($row = $result->fetchAssociative()) {
            $rows[] = [
                'uid' => (int)$row['uid'],
                'l10n_parent' => (int)$row['l10n_parent'],
                't3ver_oid' => (int)$row['t3ver_oid'],
                't3ver_wsid' => (int)$row['t3ver_wsid'],
                'hidden' => (bool)$row['hidden'],
                'published' => (bool)$row[$sourceColumn],
            ];
        }
        return $rows;
    }

    /**
     * The statement is built on the query builder that executes it, since `set()`
     * creates a named parameter of its own (`docs/architecture/database-queries.md`,
     * rule 2), and the uid list is quoted with the helper meant for it (rule 1).
     *
     * @param list<int> $uids
     */
    private function hide(array $uids): void
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE);
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder
            ->update(self::TABLE)
            ->set('hidden', 1, true, Connection::PARAM_INT)
            ->where(
                $queryBuilder->expr()->in('uid', $queryBuilder->quoteArrayBasedValueListToIntegerList($uids)),
            )
            ->executeStatement();
    }
}
