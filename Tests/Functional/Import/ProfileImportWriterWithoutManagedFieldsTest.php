<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersons\Tests\Functional\Import;

use FGTCLB\AcademicPersons\Import\ImportedContract;
use FGTCLB\AcademicPersons\Import\ImportedProfile;
use FGTCLB\AcademicPersons\Import\ImportedRecordOutcome;
use FGTCLB\AcademicPersons\Import\ProfileImportWriter;
use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;

/**
 * The shipped settings declare no managed field, so an import creates what is
 * new and changes nothing that exists.
 */
final class ProfileImportWriterWithoutManagedFieldsTest extends AbstractAcademicPersonsTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ImportWriter.csv');
    }

    #[Test]
    public function anExistingRecordIsNotChanged(): void
    {
        $result = $this->get(ProfileImportWriter::class)->write(new ImportedProfile(
            identifier: 'his:4711',
            pid: 20,
            fields: ['title' => 'Prof. Dr.'],
            contracts: [
                new ImportedContract(identifier: 'his:4711-1', fields: ['position' => 'Professor']),
                new ImportedContract(identifier: 'his:4711-3', fields: ['position' => 'Dean']),
            ],
        ));

        $this->assertSame([], $result->errors);
        $this->assertSame(
            [ImportedRecordOutcome::Unchanged, ImportedRecordOutcome::Unchanged, ImportedRecordOutcome::Created],
            array_map(static fn($record) => $record->outcome, $result->records),
        );
        $this->assertSame('Dr.', $this->fetchColumn('tx_academicpersons_domain_model_profile', 1, 'title'));
        $this->assertSame('Lecturer', $this->fetchColumn('tx_academicpersons_domain_model_contract', 1, 'position'));
    }

    private function fetchColumn(string $tableName, int $uid, string $column): string
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable($tableName);
        $queryBuilder->getRestrictions()->removeAll()->add(new DeletedRestriction());
        return (string)$queryBuilder
            ->select($column)
            ->from($tableName)
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)))
            ->executeQuery()
            ->fetchOne();
    }
}
