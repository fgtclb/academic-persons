<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Import;

use FGTCLB\AcademicPersons\Import\ImportedRecordFinder;
use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

final class ImportedRecordFinderTest extends AbstractAcademicPersonsTestCase
{
    private const PROFILE_TABLE = 'tx_academicpersons_domain_model_profile';

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ImportedRecords.csv');
    }

    /**
     * An import updates what it wrote before, whether an editor has hidden it
     * or limited its time on the site since.
     *
     * @return \Generator<string, array{string, int}>
     */
    public static function liveRecords(): \Generator
    {
        yield 'hidden' => ['fe_users:1', 1];
        yield 'start time ahead' => ['fe_users:2', 2];
        yield 'end time passed' => ['fe_users:3', 3];
        yield 'all languages' => ['fe_users:10', 10];
    }

    #[DataProvider('liveRecords')]
    #[Test]
    public function aLiveRecordIsFoundWhateverItsVisibility(string $importIdentifier, int $expectedUid): void
    {
        $this->assertSame($expectedUid, $this->get(ImportedRecordFinder::class)->findUid(self::PROFILE_TABLE, $importIdentifier));
    }

    /**
     * @return \Generator<string, array{string}>
     */
    public static function recordsThatAreNotFound(): \Generator
    {
        yield 'deleted' => ['fe_users:4'];
        yield 'created in a workspace' => ['fe_users:5'];
        yield 'translation only' => ['fe_users:9'];
        yield 'unknown' => ['fe_users:99'];
        yield 'empty' => [''];
    }

    #[DataProvider('recordsThatAreNotFound')]
    #[Test]
    public function aRecordThatIsNotLiveOrNotThereIsNotFound(string $importIdentifier): void
    {
        $this->assertNull($this->get(ImportedRecordFinder::class)->findUid(self::PROFILE_TABLE, $importIdentifier));
    }

    /**
     * Record 6 is a workspace version of record 7 with the same identifier, and
     * has the lower uid.
     */
    #[Test]
    public function theLiveRecordIsFoundRatherThanItsWorkspaceVersion(): void
    {
        $this->assertSame(7, $this->get(ImportedRecordFinder::class)->findUid(self::PROFILE_TABLE, 'fe_users:7'));
    }

    #[Test]
    public function theOldestOfTwoRecordsWithOneIdentifierIsFound(): void
    {
        $this->assertSame(11, $this->get(ImportedRecordFinder::class)->findUid(self::PROFILE_TABLE, 'fe_users:11'));
    }

    /**
     * MySQL and MariaDB compare `HR:4711` equal to `hr:4711` and `hr:4712 `
     * equal to `hr:4712`, PostgreSQL and SQLite do not. Record 13 has the lower
     * uid, so only an exact comparison finds record 14.
     */
    #[Test]
    public function theIdentifierIsComparedExactlyOnEveryDatabase(): void
    {
        $finder = $this->get(ImportedRecordFinder::class);

        $this->assertSame(14, $finder->findUid(self::PROFILE_TABLE, 'hr:4711'));
        $this->assertNull($finder->findUid(self::PROFILE_TABLE, 'hr:4712'));
    }

    #[Test]
    public function aRecordOfAnotherPersonTableIsFound(): void
    {
        $this->assertSame(
            1,
            $this->get(ImportedRecordFinder::class)->findUid('tx_academicpersons_domain_model_phone_number', 'telephone:fe_users:1'),
        );
    }

    /**
     * @return \Generator<string, array{string}>
     */
    public static function tablesWithoutImportIdentifier(): \Generator
    {
        yield 'person table without the column' => ['tx_academicpersons_domain_model_profile_information'];
        yield 'core table' => ['pages'];
        yield 'unknown table' => ['tx_unknown_table'];
    }

    #[DataProvider('tablesWithoutImportIdentifier')]
    #[Test]
    public function aTableWithoutImportIdentifierIsRefused(string $tableName): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1790866812);

        $this->get(ImportedRecordFinder::class)->findUid($tableName, 'fe_users:1');
    }
}
