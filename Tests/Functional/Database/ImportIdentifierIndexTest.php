<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Database;

use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * The schema of the test instance is built from `ext_tables.sql` by the
 * database compare, so an index declared there exists here. The index is
 * matched by its column: on SQLite TYPO3 appends a hash to the index name.
 */
final class ImportIdentifierIndexTest extends AbstractAcademicPersonsTestCase
{
    /**
     * @return \Generator<string, array{string}>
     */
    public static function tablesWithImportIdentifier(): \Generator
    {
        yield 'profile' => ['tx_academicpersons_domain_model_profile'];
        yield 'contract' => ['tx_academicpersons_domain_model_contract'];
        yield 'e-mail address' => ['tx_academicpersons_domain_model_email'];
        yield 'phone number' => ['tx_academicpersons_domain_model_phone_number'];
        yield 'physical address' => ['tx_academicpersons_domain_model_address'];
        yield 'location' => ['tx_academicpersons_domain_model_location'];
        yield 'organisational unit' => ['tx_academicpersons_domain_model_organisational_unit'];
        yield 'function type' => ['tx_academicpersons_domain_model_function_type'];
    }

    #[DataProvider('tablesWithImportIdentifier')]
    #[Test]
    public function theImportIdentifierIsIndexed(string $tableName): void
    {
        $indexes = $this->getConnectionPool()
            ->getConnectionForTable($tableName)
            ->createSchemaManager()
            ->listTableIndexes($tableName);

        $indexedColumns = [];
        foreach ($indexes as $index) {
            $indexedColumns[] = $index->getColumns();
        }
        $this->assertContains(['import_identifier'], $indexedColumns);
    }
}
