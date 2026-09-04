<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersons\Tests\Functional\Service;

use FGTCLB\AcademicPersons\Service\ProfileImageMetadataService;
use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Testbase;

/**
 * Tests for {@see ProfileImageMetadataService} (ACE-506).
 *
 * The fixture is a default-language profile (uid 1) with one image reference (uid 1
 * on file 1). Each test adds what it needs: a translation with a localized reference,
 * an editor-maintained `sys_file_metadata` row, a name with awkward whitespace.
 */
final class ProfileImageMetadataServiceTest extends AbstractAcademicPersonsTestCase
{
    private const TABLE_PROFILE = 'tx_academicpersons_domain_model_profile';
    private const TABLE_REFERENCE = 'sys_file_reference';

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/RecordSynchronizer/Fixtures/ProfileWithRelations.csv');
    }

    /**
     * The translation's own reference gets the text composed from the translation
     * row - its translatable `title` included - while the default-language reference
     * and the file metadata are left alone: the file is shared between the languages,
     * its `sys_file_metadata` row belongs to the backend editor.
     */
    #[Test]
    public function updateWritesTheNameOfTheProfileRecordIntoItsOwnReferenceOnly(): void
    {
        $this->insertRecord(self::TABLE_PROFILE, [
            'uid' => 501,
            'pid' => 100,
            'sys_language_uid' => 1,
            'l10n_parent' => 1,
            'l10n_source' => 1,
            'title' => 'Prof.',
            'first_name' => 'Erika',
            'last_name' => 'Beispiel',
            'image' => 1,
        ]);
        $this->insertRecord(self::TABLE_REFERENCE, [
            'uid' => 38,
            'pid' => 100,
            'sys_language_uid' => 1,
            'l10n_parent' => 1,
            'uid_local' => 1,
            'uid_foreign' => 501,
            'tablenames' => self::TABLE_PROFILE,
            'fieldname' => 'image',
            'sorting_foreign' => 1,
        ]);
        $this->insertRecord('sys_file_metadata', [
            'uid' => 1,
            'pid' => 0,
            'file' => 1,
            'title' => 'Editor title',
            'alternative' => 'Editor alternative',
        ]);

        $metadata = $this->get(ProfileImageMetadataService::class)->updateForProfileUid(501);

        $this->assertSame(['title' => 'Prof. Erika Beispiel', 'alternative' => 'Prof. Erika Beispiel'], $metadata);
        $this->assertSame(
            [
                ['uid' => 1, 'title' => '', 'alternative' => ''],
                ['uid' => 38, 'title' => 'Prof. Erika Beispiel', 'alternative' => 'Prof. Erika Beispiel'],
            ],
            $this->fetchReferenceMetadata(),
        );
        $this->assertSame(
            ['title' => 'Editor title', 'alternative' => 'Editor alternative'],
            $this->getConnectionPool()
                ->getConnectionForTable('sys_file_metadata')
                ->select(['title', 'alternative'], 'sys_file_metadata', ['file' => 1])
                ->fetchAssociative(),
        );
    }

    #[Test]
    public function updateComposesTheTextFromTitleAndNamesAndCollapsesWhitespace(): void
    {
        $this->getConnectionPool()->getConnectionForTable(self::TABLE_PROFILE)->update(
            self::TABLE_PROFILE,
            ['title' => " Prof.\t Dr. ", 'middle_name' => 'von', 'last_name' => 'Muster  frau'],
            ['uid' => 1],
        );

        $metadata = $this->get(ProfileImageMetadataService::class)->updateForProfileUid(1);

        $this->assertSame(
            ['title' => 'Prof. Dr. Erika von Muster frau', 'alternative' => 'Prof. Dr. Erika von Muster frau'],
            $metadata,
        );
        $this->assertSame(
            [['uid' => 1, 'title' => 'Prof. Dr. Erika von Muster frau', 'alternative' => 'Prof. Dr. Erika von Muster frau']],
            $this->fetchReferenceMetadata(),
        );
    }

    #[Test]
    public function updateReturnsNullAndWritesNothingForAProfileWithoutImage(): void
    {
        $this->getConnectionPool()
            ->getConnectionForTable(self::TABLE_REFERENCE)
            ->update(self::TABLE_REFERENCE, ['deleted' => 1], ['uid' => 1]);

        $this->assertNull($this->get(ProfileImageMetadataService::class)->updateForProfileUid(1));
        $this->assertSame([['uid' => 1, 'title' => '', 'alternative' => '']], $this->fetchReferenceMetadata());
    }

    #[Test]
    public function updateReturnsNullForAMissingProfile(): void
    {
        $this->assertNull($this->get(ProfileImageMetadataService::class)->updateForProfileUid(4711));
        $this->assertNull($this->get(ProfileImageMetadataService::class)->updateForProfileUid(0));
    }

    /**
     * Inserts a row with an explicit uid and re-aligns the table's sequence. An
     * explicitly inserted uid does not advance the PostgreSQL sequence, so the next
     * row the DataHandler writes would collide with it - `importCSVDataSet()` does
     * the same reset, raw inserts do not.
     *
     * @param array<string, mixed> $row
     */
    private function insertRecord(string $tableName, array $row): void
    {
        $connection = $this->getConnectionPool()->getConnectionForTable($tableName);
        $connection->insert($tableName, $row);
        Testbase::resetTableSequences($connection, $tableName);
    }

    /**
     * @return list<array{uid: int, title: string, alternative: string}>
     */
    private function fetchReferenceMetadata(): array
    {
        $rows = $this->getConnectionPool()
            ->getConnectionForTable(self::TABLE_REFERENCE)
            ->executeQuery('SELECT uid, title, alternative FROM sys_file_reference ORDER BY uid')
            ->fetchAllAssociative();
        return array_map(
            static fn(array $row): array => [
                'uid' => (int)$row['uid'],
                'title' => (string)$row['title'],
                'alternative' => (string)$row['alternative'],
            ],
            $rows,
        );
    }
}
