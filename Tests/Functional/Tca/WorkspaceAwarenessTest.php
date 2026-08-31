<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Tca;

use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Table;
use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Pins that all nine record tables of this extension are workspace aware.
 *
 * Until ACE-477 none of them was, and nothing reported it. The TYPO3 v14
 * auto-migration "TcaMigration::addWorkspaceAwarenessToInlineChildren()" repairs an
 * inline child only when its parent is already declared workspace aware, and here
 * the inline parents were unflagged themselves, so it never fired. TYPO3 v13 has no
 * such migration at all.
 *
 * The inline chain is "profile" -> "contract" -> "address" | "email" |
 * "phone_number", "profile" -> "profile_information", and "organisational_unit" ->
 * "contract" as a second parent. "function_type" and "location" hang off "contract"
 * as plain select targets and are flagged for consistency rather than by necessity.
 *
 * These assertions read the migrated TCA, so they are deliberately asymmetric across
 * the two supported core versions and only the v13 run is strict. Dropping the flag
 * from an inline child fails here on v13, while v14 puts it back during the TCA
 * migration and reports a deprecation from "TcaFactory" instead - which does not fail
 * the run, because it is raised while the test instance boots rather than inside a
 * test. Dropping it from "function_type" or "location" fails on both versions; they
 * are select targets that no migration covers.
 */
final class WorkspaceAwarenessTest extends AbstractAcademicPersonsTestCase
{
    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function recordTableDataProvider(): \Generator
    {
        yield 'profile' => ['tx_academicpersons_domain_model_profile'];
        yield 'profile information' => ['tx_academicpersons_domain_model_profile_information'];
        yield 'contract' => ['tx_academicpersons_domain_model_contract'];
        yield 'address' => ['tx_academicpersons_domain_model_address'];
        yield 'email' => ['tx_academicpersons_domain_model_email'];
        yield 'phone number' => ['tx_academicpersons_domain_model_phone_number'];
        yield 'organisational unit' => ['tx_academicpersons_domain_model_organisational_unit'];
        yield 'function type' => ['tx_academicpersons_domain_model_function_type'];
        yield 'location' => ['tx_academicpersons_domain_model_location'];
    }

    #[Test]
    #[DataProvider('recordTableDataProvider')]
    public function recordTableIsDeclaredWorkspaceAware(string $table): void
    {
        $this->assertTrue(
            $GLOBALS['TCA'][$table]['ctrl']['versioningWS'] ?? false,
            sprintf('Table "%s" is not declared workspace aware in its TCA "ctrl" section.', $table),
        );
    }

    /**
     * What "DefaultTcaSchema" derives from the declaration above. The functional
     * schema is rebuilt from the current TCA on every run, so this cannot fail while
     * the declaration holds - it is here to name the four columns an installation has
     * to gain, which is what the "Important" changelog entry sends integrators to the
     * database analyzer for.
     */
    #[Test]
    #[DataProvider('recordTableDataProvider')]
    public function recordTableCarriesTheWorkspaceColumns(string $table): void
    {
        $schema = $this->introspectTable($table);

        foreach (['t3ver_oid', 't3ver_wsid', 't3ver_state', 't3ver_stage'] as $column) {
            $this->assertTrue(
                $schema->hasColumn($column),
                sprintf('Table "%s" does not carry the workspace column "%s".', $table, $column),
            );
        }
    }

    /**
     * The index over ("t3ver_oid", "t3ver_wsid") is derived from the same declaration
     * and is the part of it that is easy to lose: "DefaultTcaSchema" adds it only when
     * the table does not already define an index of the name "t3ver_oid" itself, and
     * every workspace lookup joins on those two columns, so losing it costs a table
     * scan that nothing reports.
     *
     * Asserted by its columns rather than by its name on purpose - SQLite index names
     * are unique per database rather than per table, so the name the schema manager
     * reports back carries a generated suffix ("t3ver_oid_93953e74") and differs per
     * DBMS and per table.
     */
    #[Test]
    #[DataProvider('recordTableDataProvider')]
    public function recordTableCarriesTheWorkspaceIndex(string $table): void
    {
        $indexedColumnSets = array_map(
            static fn(Index $index): array => $index->getColumns(),
            array_values($this->introspectTable($table)->getIndexes()),
        );

        $this->assertContains(
            ['t3ver_oid', 't3ver_wsid'],
            $indexedColumnSets,
            sprintf(
                'Table "%s" carries no index over "t3ver_oid" and "t3ver_wsid". Present indexes: %s.',
                $table,
                json_encode($indexedColumnSets, JSON_THROW_ON_ERROR),
            ),
        );
    }

    private function introspectTable(string $table): Table
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable($table)
            ->createSchemaManager()
            ->introspectTable($table);
    }
}
