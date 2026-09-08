<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersons\Tests\Functional\Service;

use FGTCLB\AcademicPersons\Event\ModifyProfileImageMetadataEvent;
use FGTCLB\AcademicPersons\Service\ProfileImageMetadataService;
use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceFactory;
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
     * The upload half: the file the frontend editing just stored has an empty metadata
     * record, and the required fields of an installation that has any are filled from
     * the profile name - once, and without touching the reference row, which
     * {@see ProfileImageMetadataService::updateForProfileUid()} owns.
     */
    #[Test]
    public function initializeFileMetadataFillsTheRecordOfAnUploadedFile(): void
    {
        $metadata = $this->get(ProfileImageMetadataService::class)
            ->initializeFileMetadata($this->getFile(1), 1);

        $this->assertSame(['title' => 'Erika Musterfrau', 'alternative' => 'Erika Musterfrau'], $metadata);
        $this->assertSame(
            ['title' => 'Erika Musterfrau', 'alternative' => 'Erika Musterfrau'],
            $this->fetchFileMetadata(1),
        );
        $this->assertSame([['uid' => 1, 'title' => '', 'alternative' => '']], $this->fetchReferenceMetadata());
    }

    /**
     * A file that is indexed already carries what a backend editor maintained on it,
     * and an upload never overwrites that - it only fills what is empty.
     */
    #[Test]
    public function initializeFileMetadataKeepsTextTheRecordAlreadyCarries(): void
    {
        $this->insertRecord('sys_file_metadata', [
            'uid' => 1,
            'pid' => 0,
            'file' => 1,
            'title' => 'Editor title',
            'alternative' => '',
        ]);

        $metadata = $this->get(ProfileImageMetadataService::class)
            ->initializeFileMetadata($this->getFile(1), 1);

        $this->assertSame(['alternative' => 'Erika Musterfrau'], $metadata);
        $this->assertSame(
            ['title' => 'Editor title', 'alternative' => 'Erika Musterfrau'],
            $this->fetchFileMetadata(1),
        );
    }

    #[Test]
    public function initializeFileMetadataWritesNothingWithoutAProfileToNameTheFileAfter(): void
    {
        $this->assertNull(
            $this->get(ProfileImageMetadataService::class)->initializeFileMetadata($this->getFile(1), 4711),
        );
        $this->assertNull(
            $this->get(ProfileImageMetadataService::class)->initializeFileMetadata($this->getFile(1), 0),
        );
        $this->assertSame([], $this->fetchFileMetadata(1));
    }

    /**
     * Both writes announce themselves, and a listener sees which record is about to be
     * written and gets both records either way: the file, whose own metadata record
     * hangs off it, and the image relation of the profile.
     */
    #[Test]
    public function bothWritesAnnounceThemselvesWithTheRecordTheyWrite(): void
    {
        $seen = [];
        $this->addMetadataListener(static function (ModifyProfileImageMetadataEvent $event) use (&$seen): void {
            $seen[] = [
                'table' => $event->getTargetTable(),
                'file' => $event->getFile()->getUid(),
                'reference' => $event->getFileReference()?->getUid(),
                'profile' => $event->getProfileUid(),
                'metadata' => $event->getMetadata(),
            ];
        });

        $subject = $this->get(ProfileImageMetadataService::class);
        $subject->initializeFileMetadata($this->getFile(1), 1);
        $subject->updateForProfileUid(1);

        $this->assertSame(
            [
                [
                    'table' => 'sys_file_metadata',
                    'file' => 1,
                    'reference' => 1,
                    'profile' => 1,
                    'metadata' => ['title' => 'Erika Musterfrau', 'alternative' => 'Erika Musterfrau'],
                ],
                [
                    'table' => 'sys_file_reference',
                    'file' => 1,
                    'reference' => 1,
                    'profile' => 1,
                    'metadata' => ['title' => 'Erika Musterfrau', 'alternative' => 'Erika Musterfrau'],
                ],
            ],
            $seen,
        );
    }

    /**
     * The request the write happens in is handed over where the caller has one, and is
     * null where it has none - a command line run, for instance.
     */
    #[Test]
    public function theRequestOfTheWriteIsHandedOver(): void
    {
        $seen = [];
        $this->addMetadataListener(static function (ModifyProfileImageMetadataEvent $event) use (&$seen): void {
            $seen[] = $event->getRequest()?->getAttribute('probe');
        });
        $request = (new ServerRequest('https://example.test/profile'))->withAttribute('probe', 'the request');

        $subject = $this->get(ProfileImageMetadataService::class);
        $subject->initializeFileMetadata($this->getFile(1), 1, $request);
        $subject->updateForProfileUid(1, $request);
        $subject->updateForProfileUid(1);

        $this->assertSame(['the request', 'the request', null], $seen);
    }

    /**
     * A listener may add metadata columns, and nothing else: the identity of the
     * record, the relation it is part of and its localization stay the DataHandler's.
     */
    #[Test]
    public function systemFieldsAListenerSetsAreRefused(): void
    {
        $this->addMetadataListener(static function (ModifyProfileImageMetadataEvent $event): void {
            $metadata = $event->getMetadata();
            $metadata['description'] = 'A metadata column, written';
            $metadata['uid'] = '4711';
            $metadata['pid'] = '99';
            $metadata['file'] = '99';
            $metadata['uid_local'] = '99';
            $metadata['uid_foreign'] = '99';
            $metadata['tablenames'] = 'tt_content';
            $metadata['fieldname'] = 'assets';
            $metadata['sys_language_uid'] = '3';
            $metadata['l10n_parent'] = '99';
            $metadata['deleted'] = '1';
            $metadata['hidden'] = '1';
            $event->setMetadata($metadata);
        });

        $subject = $this->get(ProfileImageMetadataService::class);
        $subject->initializeFileMetadata($this->getFile(1), 1);
        $subject->updateForProfileUid(1);

        $this->assertSame(
            ['file' => '1', 'pid' => '0', 'sys_language_uid' => '0', 'description' => 'A metadata column, written'],
            $this->fetchRow(
                'sys_file_metadata',
                ['file', 'pid', 'sys_language_uid', 'description'],
                ['file' => 1],
            ),
        );
        $this->assertSame(
            [
                'uid' => '1',
                'pid' => '100',
                'uid_local' => '1',
                'uid_foreign' => '1',
                'tablenames' => 'tx_academicpersons_domain_model_profile',
                'fieldname' => 'image',
                'sys_language_uid' => '0',
                'hidden' => '0',
                'deleted' => '0',
                'description' => 'A metadata column, written',
            ],
            $this->fetchRow(
                self::TABLE_REFERENCE,
                ['uid', 'pid', 'uid_local', 'uid_foreign', 'tablenames', 'fieldname', 'sys_language_uid', 'hidden', 'deleted', 'description'],
                ['uid' => 1],
            ),
        );
    }

    /**
     * What a listener leaves in the event is what is written - including a field this
     * extension does not write itself.
     */
    #[Test]
    public function aListenerDecidesWhatIsWritten(): void
    {
        $this->addMetadataListener(static function (ModifyProfileImageMetadataEvent $event): void {
            $metadata = $event->getMetadata();
            $metadata['alternative'] = 'Portrait of ' . $metadata['alternative'];
            $metadata['description'] = 'Written by a listener';
            $event->setMetadata($metadata);
        });

        $subject = $this->get(ProfileImageMetadataService::class);
        $subject->initializeFileMetadata($this->getFile(1), 1);
        $subject->updateForProfileUid(1);

        $this->assertSame(
            [
                'title' => 'Erika Musterfrau',
                'alternative' => 'Portrait of Erika Musterfrau',
                'description' => 'Written by a listener',
            ],
            $this->fetchRow('sys_file_metadata', ['title', 'alternative', 'description'], ['file' => 1]),
        );
        $this->assertSame(
            [
                'title' => 'Erika Musterfrau',
                'alternative' => 'Portrait of Erika Musterfrau',
                'description' => 'Written by a listener',
            ],
            $this->fetchRow(self::TABLE_REFERENCE, ['title', 'alternative', 'description'], ['uid' => 1]),
        );
    }

    /**
     * A listener that empties the field map stops the write - the record keeps what it
     * has and the caller is told that nothing was written.
     */
    #[Test]
    public function aListenerCanStopTheWrite(): void
    {
        $this->addMetadataListener(static function (ModifyProfileImageMetadataEvent $event): void {
            $event->setMetadata([]);
        });

        $subject = $this->get(ProfileImageMetadataService::class);

        $this->assertNull($subject->initializeFileMetadata($this->getFile(1), 1));
        $this->assertNull($subject->updateForProfileUid(1));
        $this->assertSame([], $this->fetchFileMetadata(1));
        $this->assertSame([['uid' => 1, 'title' => '', 'alternative' => '']], $this->fetchReferenceMetadata());
    }

    /**
     * @param \Closure(ModifyProfileImageMetadataEvent): void $listener
     */
    private function addMetadataListener(\Closure $listener): void
    {
        $container = $this->get('service_container');
        $container->set('profile-image-metadata-listener', $listener);
        $container->get(ListenerProvider::class)
            ->addListener(ModifyProfileImageMetadataEvent::class, 'profile-image-metadata-listener');
    }

    /**
     * @param list<string> $fields
     * @param array<string, mixed> $identifiers
     * @return array<string, string>
     */
    private function fetchRow(string $tableName, array $fields, array $identifiers): array
    {
        $row = $this->getConnectionPool()
            ->getConnectionForTable($tableName)
            ->select($fields, $tableName, $identifiers)
            ->fetchAssociative();
        return $row === false ? [] : array_map(static fn(mixed $value): string => (string)$value, $row);
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

    private function getFile(int $uid): File
    {
        return $this->get(ResourceFactory::class)->getFileObject($uid);
    }

    /**
     * @return array{title: string, alternative: string}|array{}
     */
    private function fetchFileMetadata(int $fileUid): array
    {
        $row = $this->getConnectionPool()
            ->getConnectionForTable('sys_file_metadata')
            ->select(['title', 'alternative'], 'sys_file_metadata', ['file' => $fileUid])
            ->fetchAssociative();
        return $row === false ? [] : ['title' => (string)$row['title'], 'alternative' => (string)$row['alternative']];
    }
}
