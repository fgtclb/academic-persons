<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersons\Tests\Functional\Service;

use FGTCLB\AcademicPersons\Service\ProfileImageRelationWriter;
use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Testbase;

/**
 * Tests for {@see ProfileImageRelationWriter} (ACE-506). The writer is a thin adapter
 * around the DataHandler; what it promises is the shape of the rows afterwards, so
 * this is a functional test on real tables rather than a unit test over mocked
 * query builders.
 *
 * The fixture holds a default-language profile (uid 1) and its translation (uid 2),
 * both without an image, and three indexed files. Reference rows are added per test.
 *
 * Rows are asserted per profile, never as one uid-ordered list: an explicitly
 * inserted uid does not advance the PostgreSQL sequence, so a reference the
 * DataHandler creates during a test sorts *before* the fixture rows there and
 * after them on SQLite and MariaDB.
 */
final class ProfileImageRelationWriterTest extends AbstractAcademicPersonsTestCase
{
    use SiteBasedTestTrait;

    private const TABLE_PROFILE = 'tx_academicpersons_domain_model_profile';
    private const TABLE_REFERENCE = 'sys_file_reference';

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
        'DE' => ['id' => 1, 'title' => 'Deutsch', 'locale' => 'de_DE.UTF8', 'iso' => 'de', 'hrefLang' => 'de-DE', 'direction' => ''],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ProfileImageRelationWriter/ProfileFamily.csv');
        $this->writeSiteConfiguration(
            identifier: 'writer-test',
            site: $this->buildSiteConfiguration(rootPageId: 1, base: '/'),
            languages: [
                $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
                $this->buildLanguageConfiguration(identifier: 'DE', base: '/de/'),
            ],
        );
    }

    protected function tearDown(): void
    {
        GeneralUtility::rmdir($this->instancePath . '/typo3conf/sites', true);
        parent::tearDown();
    }

    /**
     * The write reaches the translation too: it is in the `parent` state, so the
     * core localizes the new reference into it - the writer never touches the
     * translation itself, that is `DataMapProcessor` doing its job on the datamap.
     */
    #[Test]
    public function replaceCreatesTheFirstReferenceOfADefaultLanguageProfile(): void
    {
        $replacedFileUids = $this->getWriter()->replace(1, $this->getFile(1));

        $this->assertSame([], $replacedFileUids);
        $references = $this->fetchReferences(1);
        $this->assertCount(1, $references);
        $this->assertSame(1, $references[0]['uid_foreign']);
        $this->assertSame(1, $references[0]['uid_local']);
        $this->assertSame(0, $references[0]['sys_language_uid']);
        $this->assertSame(0, $references[0]['l10n_parent']);
        $this->assertSame(1, $references[0]['sorting_foreign']);
        $this->assertSame(1, $this->fetchImageCounter(1));
        $this->assertNull($this->fetchImageState(1));
        $localizedReferences = $this->fetchReferences(2);
        $this->assertCount(1, $localizedReferences);
        $this->assertSame($references[0]['uid'], $localizedReferences[0]['l10n_parent'], 'The localized reference does not point at the default-language one.');
        $this->assertSame(1, $localizedReferences[0]['uid_local']);
        $this->assertSame(1, $this->fetchImageCounter(2));
        $this->assertSame('parent', $this->fetchImageState(2));
    }

    /**
     * A single reference the profile owns keeps its uid: metadata, sorting and the
     * reference index entry of the old row are updated, not replaced.
     */
    #[Test]
    public function replaceRePointsTheSingleOwnReferenceInPlace(): void
    {
        $this->insertReference(uid: 10, profileUid: 1, fileUid: 1);
        $this->setImageCounter(1, 1);

        $replacedFileUids = $this->getWriter()->replace(1, $this->getFile(2));

        $this->assertSame([1], $replacedFileUids);
        $references = $this->fetchReferences(1);
        $this->assertCount(1, $references);
        $this->assertSame(10, $references[0]['uid']);
        $this->assertSame(2, $references[0]['uid_local']);
        $this->assertSame(1, $this->fetchImageCounter(1));
    }

    /**
     * The in-place write submits the profile row too, so a relation counter that had
     * drifted from the reference rows is corrected by the DataHandler.
     */
    #[Test]
    public function replaceInPlaceCorrectsAStaleRelationCounter(): void
    {
        $this->insertReference(uid: 10, profileUid: 1, fileUid: 1);
        $this->setImageCounter(1, 3);

        $this->getWriter()->replace(1, $this->getFile(1));

        $this->assertSame(1, $this->fetchImageCounter(1));
    }

    /**
     * A translation that follows the default-language image holds a *localized*
     * reference (`l10n_parent` set). Its own image is a new, independent reference,
     * the localized one is deleted, and the column switches to the `custom` state.
     */
    #[Test]
    public function replaceOnAFollowingTranslationCreatesAnIndependentReference(): void
    {
        $this->insertReference(uid: 10, profileUid: 1, fileUid: 1);
        $this->insertReference(uid: 11, profileUid: 2, fileUid: 1, languageUid: 1, localizationParent: 10);
        $this->setImageCounter(1, 1);
        $this->setImageCounter(2, 1);

        $replacedFileUids = $this->getWriter()->replace(2, $this->getFile(2));

        $this->assertSame([1], $replacedFileUids);
        $defaultReferences = $this->fetchReferences(1);
        $this->assertCount(1, $defaultReferences);
        $this->assertSame(10, $defaultReferences[0]['uid']);
        $this->assertSame(1, $defaultReferences[0]['uid_local']);
        $translationReferences = $this->fetchReferences(2);
        $this->assertCount(1, $translationReferences);
        $this->assertNotSame(11, $translationReferences[0]['uid']);
        $this->assertSame(2, $translationReferences[0]['uid_local']);
        $this->assertSame(1, $translationReferences[0]['sys_language_uid']);
        $this->assertSame(0, $translationReferences[0]['l10n_parent']);
        $this->assertSame(1, $this->fetchImageCounter(2));
        $this->assertSame('custom', $this->fetchImageState(2));
        $this->assertNull($this->fetchImageState(1));
    }

    #[Test]
    public function replaceReducesLegacyDuplicatesToOneReference(): void
    {
        $this->insertReference(uid: 10, profileUid: 1, fileUid: 1, sorting: 1);
        $this->insertReference(uid: 11, profileUid: 1, fileUid: 2, sorting: 2);
        $this->setImageCounter(1, 2);

        $replacedFileUids = $this->getWriter()->replace(1, $this->getFile(1));

        $this->assertSame([1, 2], $replacedFileUids);
        $references = $this->fetchReferences(1);
        $this->assertCount(1, $references);
        $this->assertNotContains($references[0]['uid'], [10, 11]);
        $this->assertSame(1, $references[0]['uid_local']);
        $this->assertSame(1, $this->fetchImageCounter(1));
    }

    #[Test]
    public function removeDropsTheImageAndDetachesTheTranslation(): void
    {
        $this->insertReference(uid: 10, profileUid: 1, fileUid: 1);
        $this->insertReference(uid: 11, profileUid: 2, fileUid: 1, languageUid: 1, localizationParent: 10);
        $this->setImageCounter(1, 1);
        $this->setImageCounter(2, 1);

        $replacedFileUids = $this->getWriter()->remove(2);

        $this->assertSame([1], $replacedFileUids);
        $this->assertSame([], $this->fetchReferences(2));
        $defaultReferences = $this->fetchReferences(1);
        $this->assertCount(1, $defaultReferences);
        $this->assertSame(10, $defaultReferences[0]['uid']);
        $this->assertSame(0, $this->fetchImageCounter(2));
        $this->assertSame('custom', $this->fetchImageState(2));
        $this->assertSame(1, $this->fetchImageCounter(1));
    }

    /**
     * Removing the default-language image reaches a following translation through
     * the DataHandler itself: the delete command on the reference cascades into its
     * localizations (`deleteL10nOverlayRecords()`), and the datamap resets the
     * translation's counter. The translation stays in the `parent` state.
     */
    #[Test]
    public function removeOnADefaultLanguageProfileCascadesIntoFollowingTranslations(): void
    {
        $this->insertReference(uid: 10, profileUid: 1, fileUid: 1);
        $this->insertReference(uid: 11, profileUid: 2, fileUid: 1, languageUid: 1, localizationParent: 10);
        $this->setImageCounter(1, 1);
        $this->setImageCounter(2, 1);

        $replacedFileUids = $this->getWriter()->remove(1);

        $this->assertSame([1], $replacedFileUids);
        $this->assertSame([], $this->fetchReferences());
        $this->assertSame(0, $this->fetchImageCounter(1));
        $this->assertNull($this->fetchImageState(1));
        $this->assertSame(0, $this->fetchImageCounter(2));
        $this->assertNotSame('custom', $this->fetchImageState(2));
    }

    /**
     * The order is the one Extbase resolves the single rendered reference in:
     * `sorting_foreign` first, `uid` as the tiebreaker - not insertion order.
     */
    #[Test]
    public function findImageReferenceReturnsTheFirstBySortingThenUid(): void
    {
        $this->insertReference(uid: 10, profileUid: 1, fileUid: 1, sorting: 2);
        $this->insertReference(uid: 11, profileUid: 1, fileUid: 2, sorting: 1);
        $this->insertReference(uid: 12, profileUid: 1, fileUid: 3, sorting: 1, deleted: 1);

        $reference = $this->getWriter()->findImageReference(1);

        $this->assertSame(
            ['uid' => 11, 'uid_local' => 2, 'languageUid' => 0, 'translationParentUid' => 0],
            $reference,
        );
        $this->assertNull($this->getWriter()->findImageReference(2));
    }

    #[Test]
    public function countFileReferencesCountsNonDeletedReferencesOfEveryTable(): void
    {
        $this->insertReference(uid: 10, profileUid: 1, fileUid: 1);
        $this->insertReference(uid: 11, profileUid: 2, fileUid: 1, deleted: 1);
        $this->insertRecord(self::TABLE_REFERENCE, [
            'uid' => 12,
            'pid' => 100,
            'uid_local' => 1,
            'uid_foreign' => 7,
            'tablenames' => 'tt_content',
            'fieldname' => 'image',
            'hidden' => 1,
        ]);

        $this->assertSame(2, $this->getWriter()->countFileReferences($this->getFile(1)));
        $this->assertSame(0, $this->getWriter()->countFileReferences($this->getFile(2)));
    }

    #[Test]
    public function deleteUnreferencedFilesDeletesOnlyFilesNoReferenceUses(): void
    {
        $this->createPhysicalFiles();
        $this->insertReference(uid: 10, profileUid: 1, fileUid: 1);

        $this->getWriter()->deleteUnreferencedFiles([1, 2, 3, 4711], retainedFileUid: 3);

        $this->assertSame([1, 3], $this->fetchFileUids());
        $this->assertFileExists($this->instancePath . '/fileadmin/images/portrait.png');
        $this->assertFileDoesNotExist($this->instancePath . '/fileadmin/images/portrait-2.png');
        $this->assertFileExists($this->instancePath . '/fileadmin/images/portrait-3.png');
    }

    /**
     * The translation's image column is in the `parent` state (no `l10n_state`), so
     * re-submitting the default-language relation makes core localize the reference
     * into the translation and correct its stale counter - a `custom` translation
     * stays as it is.
     */
    #[Test]
    public function propagateToTranslationsCarriesTheDefaultImageIntoFollowingTranslations(): void
    {
        $this->insertReference(uid: 10, profileUid: 1, fileUid: 1);
        $this->setImageCounter(1, 1);
        $this->setImageCounter(2, 1);
        $this->insertRecord(self::TABLE_PROFILE, [
            'uid' => 3,
            'pid' => 100,
            'sys_language_uid' => 1,
            'l10n_parent' => 1,
            'l10n_source' => 1,
            'first_name' => 'Erika',
            'last_name' => 'Musterfrau',
            'image' => 0,
            'l10n_state' => '{"image":"custom"}',
        ]);

        $this->getWriter()->propagateToTranslations(1);

        $this->assertCount(2, $this->fetchReferences());
        $defaultReferences = $this->fetchReferences(1);
        $this->assertCount(1, $defaultReferences);
        $this->assertSame(10, $defaultReferences[0]['uid']);
        $localizedReferences = $this->fetchReferences(2);
        $this->assertCount(1, $localizedReferences);
        $this->assertSame(1, $localizedReferences[0]['uid_local']);
        $this->assertSame(1, $localizedReferences[0]['sys_language_uid']);
        $this->assertSame(10, $localizedReferences[0]['l10n_parent']);
        $this->assertSame(1, $this->fetchImageCounter(1));
        $this->assertSame(1, $this->fetchImageCounter(2));
        $this->assertSame(0, $this->fetchImageCounter(3));
        $this->assertSame('custom', $this->fetchImageState(3));
    }

    #[Test]
    public function propagateToTranslationsRejectsATranslation(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1757000002);

        $this->getWriter()->propagateToTranslations(2);
    }

    /**
     * Both tables are workspace aware, and a version row keeps the `uid_foreign` of
     * its live record. The lookup must not see it: it would sort by the same
     * `sorting_foreign, uid` and could be picked instead of the rendered live row.
     */
    #[Test]
    public function findImageReferenceIgnoresWorkspaceVersionsOfTheReference(): void
    {
        $this->insertReference(uid: 10, profileUid: 1, fileUid: 1);
        // Both sort BEFORE the live row, so an unrestricted lookup picks one of them.
        $this->insertReference(uid: 11, profileUid: 1, fileUid: 2, sorting: 0, workspaceId: 42, versionOf: 10);
        $this->insertReference(uid: 12, profileUid: 1, fileUid: 3, sorting: 0, workspaceId: 42);

        $this->assertSame(
            ['uid' => 10, 'uid_local' => 1, 'languageUid' => 0, 'translationParentUid' => 0],
            $this->getWriter()->findImageReference(1),
        );
    }

    /**
     * The consequence for the write path: a live profile with one live reference plus
     * a draft version of it stays on the in-place branch, so the live row is re-pointed
     * and never deleted and re-created.
     */
    #[Test]
    public function replaceIgnoresWorkspaceVersionsAndKeepsTheLiveReference(): void
    {
        $this->insertReference(uid: 10, profileUid: 1, fileUid: 1);
        $this->insertReference(uid: 11, profileUid: 1, fileUid: 2, sorting: 0, workspaceId: 42, versionOf: 10);
        $this->setImageCounter(1, 1);

        $replacedFileUids = $this->getWriter()->replace(1, $this->getFile(3));

        $this->assertSame([1], $replacedFileUids);
        $liveReferences = $this->fetchReferences(1);
        // fetchReferences() orders by uid and sees every row, versions included.
        $this->assertCount(2, $liveReferences);
        $this->assertSame(10, $liveReferences[0]['uid']);
        $this->assertSame(3, $liveReferences[0]['uid_local'], 'The live reference was not re-pointed in place.');
        $this->assertSame(11, $liveReferences[1]['uid']);
        $this->assertSame(2, $liveReferences[1]['uid_local'], 'The workspace version was rewritten.');
        $this->assertSame(0, $this->fetchDeletedFlag(10));
        $this->assertSame(0, $this->fetchDeletedFlag(11), 'The workspace version was deleted.');
    }

    /**
     * A uid addressing a workspace version is refused rather than written against:
     * the DataHandler addresses versioned records through their live uid.
     */
    #[Test]
    public function replaceRefusesAWorkspaceVersionOfTheProfile(): void
    {
        $this->insertRecord(self::TABLE_PROFILE, [
            'uid' => 4,
            'pid' => 100,
            'first_name' => 'Erika',
            'last_name' => 'Musterfrau',
            't3ver_oid' => 1,
            't3ver_wsid' => 42,
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1757000005);

        $this->getWriter()->replace(4, $this->getFile(1));
    }

    #[Test]
    public function updateReferenceMetadataWritesTitleAndAlternative(): void
    {
        $this->insertReference(uid: 10, profileUid: 1, fileUid: 1);

        $this->getWriter()->updateReferenceMetadata(10, ['title' => 'Title', 'alternative' => 'Alternative']);

        $row = $this->getConnectionPool()
            ->getConnectionForTable(self::TABLE_REFERENCE)
            ->select(['title', 'alternative'], self::TABLE_REFERENCE, ['uid' => 10])
            ->fetchAssociative();
        $this->assertSame(['title' => 'Title', 'alternative' => 'Alternative'], $row);
    }

    /**
     * A listener of `ModifyProfileImageMetadataEvent` may set a field the installation
     * does not have. It is dropped here rather than submitted to the DataHandler.
     */
    #[Test]
    public function updateReferenceMetadataDropsFieldsTheTableDoesNotDeclare(): void
    {
        $this->insertReference(uid: 10, profileUid: 1, fileUid: 1);

        $this->getWriter()->updateReferenceMetadata(10, [
            'description' => 'Kept',
            'copyright' => 'A column of sys_file_metadata, not of the reference',
        ]);

        $row = $this->getConnectionPool()
            ->getConnectionForTable(self::TABLE_REFERENCE)
            ->select(['description'], self::TABLE_REFERENCE, ['uid' => 10])
            ->fetchAssociative();
        $this->assertSame(['description' => 'Kept'], $row);
    }

    private function getWriter(): ProfileImageRelationWriter
    {
        return $this->get(ProfileImageRelationWriter::class);
    }

    private function getFile(int $uid): File
    {
        return $this->get(ResourceFactory::class)->getFileObject($uid);
    }

    private function createPhysicalFiles(): void
    {
        $folder = $this->instancePath . '/fileadmin/images';
        GeneralUtility::mkdir_deep($folder);
        foreach (['portrait.png', 'portrait-2.png', 'portrait-3.png'] as $fileName) {
            copy(__DIR__ . '/Fixtures/ProfileImageRelationWriter/portrait.png', $folder . '/' . $fileName);
        }
    }

    private function insertReference(
        int $uid,
        int $profileUid,
        int $fileUid,
        int $languageUid = 0,
        int $localizationParent = 0,
        int $sorting = 1,
        int $deleted = 0,
        int $workspaceId = 0,
        int $versionOf = 0,
    ): void {
        $this->insertRecord(self::TABLE_REFERENCE, [
            'uid' => $uid,
            'pid' => 100,
            'deleted' => $deleted,
            'sys_language_uid' => $languageUid,
            'l10n_parent' => $localizationParent,
            'uid_local' => $fileUid,
            'uid_foreign' => $profileUid,
            'tablenames' => self::TABLE_PROFILE,
            'fieldname' => 'image',
            'sorting_foreign' => $sorting,
            't3ver_wsid' => $workspaceId,
            't3ver_oid' => $versionOf,
        ]);
    }

    private function fetchDeletedFlag(int $referenceUid): int
    {
        return (int)$this->getConnectionPool()
            ->getConnectionForTable(self::TABLE_REFERENCE)
            ->select(['deleted'], self::TABLE_REFERENCE, ['uid' => $referenceUid])
            ->fetchOne();
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

    private function setImageCounter(int $profileUid, int $counter): void
    {
        $this->getConnectionPool()
            ->getConnectionForTable(self::TABLE_PROFILE)
            ->update(self::TABLE_PROFILE, ['image' => $counter], ['uid' => $profileUid]);
    }

    private function fetchImageCounter(int $profileUid): int
    {
        return (int)$this->getConnectionPool()
            ->getConnectionForTable(self::TABLE_PROFILE)
            ->select(['image'], self::TABLE_PROFILE, ['uid' => $profileUid])
            ->fetchOne();
    }

    private function fetchImageState(int $profileUid): ?string
    {
        $state = $this->getConnectionPool()
            ->getConnectionForTable(self::TABLE_PROFILE)
            ->select(['l10n_state'], self::TABLE_PROFILE, ['uid' => $profileUid])
            ->fetchOne();
        if (!is_string($state) || $state === '') {
            return null;
        }
        $states = json_decode($state, true, 512, JSON_THROW_ON_ERROR);
        return is_array($states) ? ($states['image'] ?? null) : null;
    }

    /**
     * Non-deleted image references, of one profile or of all of them.
     *
     * @return list<array{uid: int, uid_local: int, uid_foreign: int, sys_language_uid: int, l10n_parent: int, sorting_foreign: int}>
     */
    private function fetchReferences(?int $profileUid = null): array
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable(self::TABLE_REFERENCE);
        $queryBuilder
            ->select('uid', 'uid_local', 'uid_foreign', 'sys_language_uid', 'l10n_parent', 'sorting_foreign')
            ->from(self::TABLE_REFERENCE)
            ->where($queryBuilder->expr()->eq('tablenames', $queryBuilder->createNamedParameter(self::TABLE_PROFILE)))
            ->orderBy('uid');
        if ($profileUid !== null) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->eq('uid_foreign', $queryBuilder->createNamedParameter($profileUid, Connection::PARAM_INT)),
            );
        }
        $rows = $queryBuilder
            ->executeQuery()
            ->fetchAllAssociative();
        return array_map(
            static fn(array $row): array => [
                'uid' => (int)$row['uid'],
                'uid_local' => (int)$row['uid_local'],
                'uid_foreign' => (int)$row['uid_foreign'],
                'sys_language_uid' => (int)$row['sys_language_uid'],
                'l10n_parent' => (int)$row['l10n_parent'],
                'sorting_foreign' => (int)$row['sorting_foreign'],
            ],
            $rows,
        );
    }

    /**
     * @return list<int>
     */
    private function fetchFileUids(): array
    {
        return array_map(
            'intval',
            $this->getConnectionPool()
                ->getConnectionForTable('sys_file')
                ->executeQuery('SELECT uid FROM sys_file ORDER BY uid')
                ->fetchFirstColumn(),
        );
    }
}
