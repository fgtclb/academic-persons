<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersons\Tests\Functional\Import;

use FGTCLB\AcademicPersons\Event\AfterProfileUpdateEvent;
use FGTCLB\AcademicPersons\Event\BeforeImportedRecordWriteEvent;
use FGTCLB\AcademicPersons\Event\ProfileUpdateOrigin;
use FGTCLB\AcademicPersons\Import\ImportedContact;
use FGTCLB\AcademicPersons\Import\ImportedContract;
use FGTCLB\AcademicPersons\Import\ImportedProfile;
use FGTCLB\AcademicPersons\Import\ImportedRecordOutcome;
use FGTCLB\AcademicPersons\Import\ProfileImportWriter;
use FGTCLB\AcademicPersons\Import\RetirePolicy;
use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;

/**
 * Writing and retiring the persons of an external source. The fixture
 * extension `test_managed_fields` manages the profile title and website, the
 * contract position, the e-mail address and the phone number type.
 *
 * The fixture holds four persons. `his:4711` has two imported contracts, one
 * contract an editor added, and an imported e-mail address the owner hid.
 * `his:0042` is excluded from the synchronisation. An editor created the third
 * profile, and `hr:7` belongs to another source.
 */
final class ProfileImportWriterTest extends AbstractAcademicPersonsTestCase
{
    private const PROFILE_TABLE = 'tx_academicpersons_domain_model_profile';
    private const CONTRACT_TABLE = 'tx_academicpersons_domain_model_contract';
    private const EMAIL_TABLE = 'tx_academicpersons_domain_model_email';

    /**
     * @var \ArrayObject<int, AfterProfileUpdateEvent>
     */
    private \ArrayObject $announcements;

    protected function setUp(): void
    {
        $this->testExtensionsToLoad[] = 'tests/test-managed-fields';
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ImportWriter.csv');
        $announcements = new \ArrayObject();
        $this->announcements = $announcements;
        $this->addListener(
            AfterProfileUpdateEvent::class,
            static function (AfterProfileUpdateEvent $event) use ($announcements): void {
                $announcements->append($event);
            },
        );
    }

    #[Test]
    public function writingTheSamePersonTwiceYieldsOneProfile(): void
    {
        $person = new ImportedProfile(
            identifier: 'his:9000',
            pid: 20,
            fields: ['first_name' => 'Ada', 'last_name' => 'Lovelace'],
            contracts: [
                new ImportedContract(
                    identifier: 'his:9000-1',
                    fields: ['position' => 'Professor'],
                    emailAddresses: [new ImportedContact('his:9000-1-mail', ['email' => 'ada@example.org'])],
                ),
            ],
        );
        $writer = $this->get(ProfileImportWriter::class);

        $first = $writer->write($person);
        $second = $writer->write($person);

        $this->assertSame([], $first->errors);
        $this->assertSame([], $second->errors);
        $this->assertSame(
            [ImportedRecordOutcome::Created, ImportedRecordOutcome::Created, ImportedRecordOutcome::Created],
            array_map(static fn($record) => $record->outcome, $first->records),
        );
        $profiles = $this->fetchRows(self::PROFILE_TABLE, 'his:9000');
        $this->assertCount(1, $profiles);
        $this->assertSame(20, (int)$profiles[0]['pid']);
        $this->assertSame('Lovelace', $profiles[0]['last_name']);
        $contracts = $this->fetchRows(self::CONTRACT_TABLE, 'his:9000-1');
        $this->assertCount(1, $contracts);
        $this->assertSame((int)$profiles[0]['uid'], (int)$contracts[0]['profile']);
        $emailAddresses = $this->fetchRows(self::EMAIL_TABLE, 'his:9000-1-mail');
        $this->assertCount(1, $emailAddresses);
        $this->assertSame((int)$contracts[0]['uid'], (int)$emailAddresses[0]['contract']);
        $this->assertSame((int)$profiles[0]['uid'], $second->getRecord(self::PROFILE_TABLE, 'his:9000')?->uid);
    }

    #[Test]
    public function aNewContactIsAddedToAnExistingContractAfterTheOnesItHas(): void
    {
        $result = $this->get(ProfileImportWriter::class)->write(new ImportedProfile(
            identifier: 'his:4711',
            pid: 20,
            contracts: [
                new ImportedContract(
                    identifier: 'his:4711-1',
                    emailAddresses: [
                        new ImportedContact('his:4711-1-mail', ['email' => 'jane.doe@example.org']),
                        new ImportedContact('his:4711-1-mail-2', ['email' => 'j.doe@example.org']),
                    ],
                ),
            ],
        ));

        $this->assertSame([], $result->errors);
        $this->assertSame(ImportedRecordOutcome::Created, $result->getRecord(self::EMAIL_TABLE, 'his:4711-1-mail-2')?->outcome);
        $this->assertSame(
            [['his:4711-1-mail', 1], ['', 1], ['his:4711-1-mail-2', 1]],
            array_map(
                static fn(array $row): array => [$row['import_identifier'], (int)$row['contract']],
                $this->fetchContactsOfContract(self::EMAIL_TABLE, 1),
            ),
            'The new address is added after the imported and the manual one.',
        );
    }

    #[Test]
    public function aProfileExcludedFromTheSynchronisationIsNotWritten(): void
    {
        $result = $this->get(ProfileImportWriter::class)->write(new ImportedProfile(
            identifier: 'his:0042',
            pid: 20,
            fields: ['first_name' => 'Maximilian'],
            contracts: [
                new ImportedContract(
                    identifier: 'his:0042-1',
                    fields: ['position' => 'Professor'],
                    emailAddresses: [new ImportedContact('his:0042-1-mail-2', ['email' => 'max@example.org'])],
                ),
            ],
        ));

        $this->assertSame(
            [
                [self::PROFILE_TABLE, ImportedRecordOutcome::Skipped],
                [self::CONTRACT_TABLE, ImportedRecordOutcome::Skipped],
                [self::EMAIL_TABLE, ImportedRecordOutcome::Skipped],
            ],
            array_map(static fn($record): array => [$record->tableName, $record->outcome], $result->records),
        );
        $this->assertSame('Max', $this->fetchRows(self::PROFILE_TABLE, 'his:0042')[0]['first_name']);
        $this->assertSame('Lecturer', $this->fetchRows(self::CONTRACT_TABLE, 'his:0042-1')[0]['position']);
        $this->assertSame([], $this->fetchRows(self::EMAIL_TABLE, 'his:0042-1-mail-2'));
        $this->assertCount(0, $this->announcements);
    }

    /**
     * The source still delivers the room it had before the editor changed it.
     * Only the position is managed, so the editor's room survives.
     */
    #[Test]
    public function anExistingRecordReceivesOnlyItsManagedFields(): void
    {
        $result = $this->get(ProfileImportWriter::class)->write(new ImportedProfile(
            identifier: 'his:4711',
            pid: 20,
            fields: ['title' => 'Prof. Dr.', 'first_name' => 'Janet'],
            contracts: [
                new ImportedContract(identifier: 'his:4711-1', fields: ['position' => 'Professor', 'room' => 'A 0.01']),
            ],
        ));

        $this->assertSame([], $result->errors);
        $profile = $this->fetchRows(self::PROFILE_TABLE, 'his:4711')[0];
        $this->assertSame('Prof. Dr.', $profile['title']);
        $this->assertSame('Jane', $profile['first_name']);
        $contract = $this->fetchRows(self::CONTRACT_TABLE, 'his:4711-1')[0];
        $this->assertSame('Professor', $contract['position']);
        $this->assertSame('B 2.02', $contract['room']);
        $this->assertSame(ImportedRecordOutcome::Updated, $result->getRecord(self::CONTRACT_TABLE, 'his:4711-1')?->outcome);
    }

    #[Test]
    public function aHiddenImportedRecordStaysHidden(): void
    {
        $this->get(ProfileImportWriter::class)->write(new ImportedProfile(
            identifier: 'his:4711',
            pid: 20,
            contracts: [
                new ImportedContract(
                    identifier: 'his:4711-1',
                    emailAddresses: [new ImportedContact('his:4711-1-mail', ['email' => 'jane@example.org', 'hidden' => 0])],
                ),
            ],
        ));

        $emailAddress = $this->fetchRows(self::EMAIL_TABLE, 'his:4711-1-mail')[0];
        $this->assertSame('jane@example.org', $emailAddress['email'], 'The address is managed and written.');
        $this->assertSame(1, (int)$emailAddress['hidden']);
    }

    #[Test]
    public function aListenerChangesARowAndVetoesAContract(): void
    {
        $this->addListener(
            BeforeImportedRecordWriteEvent::class,
            static function (BeforeImportedRecordWriteEvent $event): void {
                if ($event->getIdentifier() === 'his:9000-2') {
                    $event->veto('Student assistants are not listed.');
                }
                if ($event->getTableName() === self::PROFILE_TABLE) {
                    $event->setRow([...$event->getRow(), 'last_name' => 'King']);
                }
            },
        );

        $result = $this->get(ProfileImportWriter::class)->write(new ImportedProfile(
            identifier: 'his:9000',
            pid: 20,
            fields: ['first_name' => 'Ada', 'last_name' => 'Lovelace'],
            contracts: [
                new ImportedContract(identifier: 'his:9000-1', fields: ['position' => 'Professor']),
                new ImportedContract(
                    identifier: 'his:9000-2',
                    fields: ['position' => 'Student assistant'],
                    emailAddresses: [new ImportedContact('his:9000-2-mail', ['email' => 'ada@example.org'])],
                ),
            ],
        ));

        $this->assertSame('King', $this->fetchRows(self::PROFILE_TABLE, 'his:9000')[0]['last_name']);
        $this->assertCount(1, $this->fetchRows(self::CONTRACT_TABLE, 'his:9000-1'));
        $this->assertSame([], $this->fetchRows(self::CONTRACT_TABLE, 'his:9000-2'));
        $this->assertSame([], $this->fetchRows(self::EMAIL_TABLE, 'his:9000-2-mail'));
        $vetoed = $result->getRecord(self::CONTRACT_TABLE, 'his:9000-2');
        $this->assertSame(ImportedRecordOutcome::Vetoed, $vetoed?->outcome);
        $this->assertSame('Student assistants are not listed.', $vetoed->reason);
        $this->assertSame(ImportedRecordOutcome::Skipped, $result->getRecord(self::EMAIL_TABLE, 'his:9000-2-mail')?->outcome);
    }

    /**
     * The row a listener hands back is restricted like the supplied one. On the
     * existing profile only the managed title is written, on the contract only
     * the position, on the e-mail address only the address. The first name, the
     * room, the type, `hidden` and the page the listener set are dropped. The
     * listener still sees the room the source supplied.
     */
    #[Test]
    public function aListenerCannotWriteWhatTheWriterRestricts(): void
    {
        $suppliedFields = [];
        $this->addListener(
            BeforeImportedRecordWriteEvent::class,
            static function (BeforeImportedRecordWriteEvent $event) use (&$suppliedFields): void {
                $row = [...$event->getRow(), 'hidden' => 1, 'pid' => 1];
                $row = match ($event->getIdentifier()) {
                    'his:4711' => [...$row, 'title' => 'Prof.', 'first_name' => 'Janet'],
                    'his:4711-1' => [...$row, 'position' => 'Dean', 'room' => 'Z 9.99'],
                    'his:4711-1-mail' => [...$row, 'email' => 'j@example.org', 'type' => 'private'],
                    default => $row,
                };
                if ($event->getIdentifier() === 'his:4711-1') {
                    $suppliedFields = $event->getSuppliedFields();
                }
                $event->setRow($row);
            },
        );

        $this->get(ProfileImportWriter::class)->write(new ImportedProfile(
            identifier: 'his:4711',
            pid: 20,
            contracts: [
                new ImportedContract(
                    identifier: 'his:4711-1',
                    fields: ['position' => 'Professor', 'room' => 'A 0.01'],
                    emailAddresses: [new ImportedContact('his:4711-1-mail', ['email' => 'jane@example.org'])],
                ),
            ],
        ));

        $profile = $this->fetchRows(self::PROFILE_TABLE, 'his:4711')[0];
        $this->assertSame(['Prof.', 'Jane', 0, 20], [$profile['title'], $profile['first_name'], (int)$profile['hidden'], (int)$profile['pid']]);
        $contract = $this->fetchRows(self::CONTRACT_TABLE, 'his:4711-1')[0];
        $this->assertSame(['Dean', 'B 2.02', 0, 20], [$contract['position'], $contract['room'], (int)$contract['hidden'], (int)$contract['pid']]);
        $emailAddress = $this->fetchRows(self::EMAIL_TABLE, 'his:4711-1-mail')[0];
        $this->assertSame(['j@example.org', 'business', 1, 20], [$emailAddress['email'], $emailAddress['type'], (int)$emailAddress['hidden'], (int)$emailAddress['pid']]);
        $this->assertSame(['position' => 'Professor', 'room' => 'A 0.01'], $suppliedFields);
    }

    #[Test]
    public function aVetoedProfileWritesNothingOfThePerson(): void
    {
        $this->addListener(
            BeforeImportedRecordWriteEvent::class,
            static function (BeforeImportedRecordWriteEvent $event): void {
                if ($event->getTableName() === self::PROFILE_TABLE) {
                    $event->veto('Not part of the directory.');
                }
            },
        );

        $result = $this->get(ProfileImportWriter::class)->write(new ImportedProfile(
            identifier: 'his:9000',
            pid: 20,
            fields: ['last_name' => 'Lovelace'],
            contracts: [new ImportedContract(identifier: 'his:9000-1', fields: ['position' => 'Professor'])],
        ));

        $this->assertSame([], $this->fetchRows(self::PROFILE_TABLE, 'his:9000'));
        $this->assertSame([], $this->fetchRows(self::CONTRACT_TABLE, 'his:9000-1'));
        $this->assertSame(
            [[ImportedRecordOutcome::Vetoed, 'Not part of the directory.'], [ImportedRecordOutcome::Skipped, 'Its profile was not written.']],
            array_map(static fn($record): array => [$record->outcome, $record->reason], $result->records),
        );
        $this->assertCount(0, $this->announcements);
    }

    /**
     * `hr:7-1` is a contract of another synchronised person, and `hr:7-1-mail`
     * an address of that contract. Their position and address are managed, so
     * only the check of the parent keeps the writer from changing them.
     */
    #[Test]
    public function aRecordBelowAnotherParentIsSkipped(): void
    {
        $result = $this->get(ProfileImportWriter::class)->write(new ImportedProfile(
            identifier: 'his:4711',
            pid: 20,
            contracts: [
                new ImportedContract(identifier: 'hr:7-1', fields: ['position' => 'Professor']),
                new ImportedContract(
                    identifier: 'his:4711-1',
                    emailAddresses: [new ImportedContact('hr:7-1-mail', ['email' => 'jane@example.org'])],
                ),
            ],
        ));

        $this->assertSame(ImportedRecordOutcome::Skipped, $result->getRecord(self::CONTRACT_TABLE, 'hr:7-1')?->outcome);
        $this->assertSame(ImportedRecordOutcome::Skipped, $result->getRecord(self::EMAIL_TABLE, 'hr:7-1-mail')?->outcome);
        $contract = $this->fetchRows(self::CONTRACT_TABLE, 'hr:7-1')[0];
        $this->assertSame([4, 'Lecturer'], [(int)$contract['profile'], $contract['position']]);
        $emailAddress = $this->fetchRows(self::EMAIL_TABLE, 'hr:7-1-mail')[0];
        $this->assertSame([5, 'erika@example.org'], [(int)$emailAddress['contract'], $emailAddress['email']]);
    }

    #[Test]
    public function aNewProfileWithoutAPageIsRefused(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1790970747);

        $this->get(ProfileImportWriter::class)->write(new ImportedProfile(identifier: 'his:9000', pid: 0));
    }

    #[Test]
    public function anEmptyIdentifierIsRefused(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1790970749);

        $this->get(ProfileImportWriter::class)->write(new ImportedProfile(
            identifier: 'his:9000',
            pid: 20,
            contracts: [new ImportedContract(identifier: ' ')],
        ));
    }

    #[Test]
    public function retiringWithoutASourceIsRefused(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1790970748);

        $this->get(ProfileImportWriter::class)->retire('', [], RetirePolicy::Hide);
    }

    #[Test]
    public function theUnitAndTheFunctionTypeAreFoundByTheirIdentifiers(): void
    {
        $result = $this->get(ProfileImportWriter::class)->write(new ImportedProfile(
            identifier: 'his:9000',
            pid: 20,
            fields: ['last_name' => 'Lovelace'],
            contracts: [
                new ImportedContract(
                    identifier: 'his:9000-1',
                    organisationalUnitIdentifier: 'his:unit-7',
                    functionTypeIdentifier: 'his:function-3',
                ),
                new ImportedContract(identifier: 'his:9000-2', organisationalUnitIdentifier: 'his:unit-8'),
            ],
        ));

        $contract = $this->fetchRows(self::CONTRACT_TABLE, 'his:9000-1')[0];
        $this->assertSame(1, (int)$contract['organisational_unit']);
        $this->assertSame(1, (int)$contract['function_type']);
        $this->assertSame(0, (int)$this->fetchRows(self::CONTRACT_TABLE, 'his:9000-2')[0]['organisational_unit']);
        $this->assertSame(
            ['No organisational unit carries the identifier "his:unit-8", it is not set on the contract "his:9000-2".'],
            $result->messages,
        );
    }

    #[Test]
    public function aWriteIsAnnouncedOnceAsAnImport(): void
    {
        $this->get(ProfileImportWriter::class)->write(new ImportedProfile(
            identifier: 'his:4711',
            pid: 20,
            contracts: [new ImportedContract(identifier: 'his:4711-1', fields: ['position' => 'Professor'])],
        ));

        $this->assertSame(
            [[1, ProfileUpdateOrigin::Import]],
            array_map(
                static fn(AfterProfileUpdateEvent $event): array => [$event->getProfile()->getUid(), $event->getOrigin()],
                $this->announcements->getArrayCopy(),
            ),
        );
    }

    #[Test]
    public function aColumnTheWriterSetsItselfIsRefused(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1790970751);

        $this->get(ProfileImportWriter::class)->write(new ImportedProfile(
            identifier: 'his:9000',
            pid: 20,
            contracts: [new ImportedContract(identifier: 'his:9000-1', fields: ['profile' => 3])],
        ));
    }

    #[Test]
    public function anIdentifierUsedTwiceIsRefused(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1790970750);

        $this->get(ProfileImportWriter::class)->write(new ImportedProfile(
            identifier: 'his:9000',
            pid: 20,
            contracts: [new ImportedContract(identifier: 'his:9000-1'), new ImportedContract(identifier: 'his:9000-1')],
        ));
    }

    /**
     * `his:4711-2` vanished from the source. The contract an editor added to
     * the same profile, the contract of the excluded profile and the person of
     * the other source are left alone.
     */
    #[Test]
    public function retiringHidesWhatTheSourceNoLongerSupplies(): void
    {
        $result = $this->get(ProfileImportWriter::class)->retire(
            'his',
            ['his:4711', 'his:4711-1', 'his:4711-1-mail'],
            RetirePolicy::Hide,
        );

        $this->assertSame([], $result->errors);
        $this->assertSame(
            [[self::CONTRACT_TABLE, 'his:4711-2', ImportedRecordOutcome::Hidden, 3]],
            array_map(static fn($record): array => [$record->tableName, $record->identifier, $record->outcome, $record->uid], $result->records),
        );
        $this->assertSame(
            [1 => 0, 2 => 0, 3 => 1, 4 => 0, 5 => 0],
            $this->fetchColumn(self::CONTRACT_TABLE, 'hidden'),
        );
        $this->assertSame([0, 0, 0, 0], array_values($this->fetchColumn(self::PROFILE_TABLE, 'hidden')));
        $this->assertSame(
            [[1, ProfileUpdateOrigin::Import]],
            array_map(
                static fn(AfterProfileUpdateEvent $event): array => [$event->getProfile()->getUid(), $event->getOrigin()],
                $this->announcements->getArrayCopy(),
            ),
            'The profile of the retired contract is announced.',
        );
    }

    #[Test]
    public function retiringWithTheDeletePolicyDeletes(): void
    {
        $result = $this->get(ProfileImportWriter::class)->retire(
            'his',
            ['his:4711', 'his:4711-1', 'his:4711-1-mail'],
            RetirePolicy::Delete,
        );

        $this->assertSame([], $result->errors);
        $this->assertSame(ImportedRecordOutcome::Deleted, $result->getRecord(self::CONTRACT_TABLE, 'his:4711-2')?->outcome);
        $this->assertSame([1 => 0, 2 => 0, 3 => 1, 4 => 0, 5 => 0], $this->fetchColumn(self::CONTRACT_TABLE, 'deleted'));
        $this->assertCount(1, $this->announcements);
    }

    /**
     * Retiring the whole source keeps nothing: the person `his:4711` is
     * hidden with its imported records, the excluded person `his:0042` is not.
     */
    #[Test]
    public function retiringEverythingOfASourceLeavesExcludedProfilesAlone(): void
    {
        $this->get(ProfileImportWriter::class)->retire('his', [], RetirePolicy::Hide);

        $this->assertSame([1 => 1, 2 => 0, 3 => 0, 4 => 0], $this->fetchColumn(self::PROFILE_TABLE, 'hidden'));
        $this->assertSame([1 => 1, 2 => 0, 3 => 1, 4 => 0, 5 => 0], $this->fetchColumn(self::CONTRACT_TABLE, 'hidden'));
        $this->assertSame([1 => 1, 2 => 0, 3 => 0, 4 => 0], $this->fetchColumn(self::EMAIL_TABLE, 'hidden'), 'Address 1 was hidden before.');
    }

    /**
     * Deleting the profile `his:4711` deletes the contract an editor added to
     * it as well. The excluded person and the other source stay.
     */
    #[Test]
    public function deletingAProfileDeletesEverythingBelowIt(): void
    {
        $result = $this->get(ProfileImportWriter::class)->retire('his', [], RetirePolicy::Delete);

        $this->assertSame([], $result->errors);
        $this->assertSame(
            [[self::PROFILE_TABLE, 'his:4711', ImportedRecordOutcome::Deleted]],
            array_map(static fn($record): array => [$record->tableName, $record->identifier, $record->outcome], $result->records),
            'The records below the profile are left to the cascade.',
        );
        $this->assertSame([1 => 1, 2 => 0, 3 => 0, 4 => 0], $this->fetchColumn(self::PROFILE_TABLE, 'deleted'));
        $this->assertSame([1 => 1, 2 => 1, 3 => 1, 4 => 0, 5 => 0], $this->fetchColumn(self::CONTRACT_TABLE, 'deleted'));
        $this->assertSame([1 => 1, 2 => 1, 3 => 0, 4 => 0], $this->fetchColumn(self::EMAIL_TABLE, 'deleted'));
    }

    private function addListener(string $eventClassName, \Closure $listener): void
    {
        $container = $this->get('service_container');
        $identifier = 'import-writer-test-listener-' . md5($eventClassName);
        $container->set($identifier, $listener);
        $container->get(ListenerProvider::class)->addListener($eventClassName, $identifier);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchRows(string $tableName, string $importIdentifier): array
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable($tableName);
        $queryBuilder->getRestrictions()->removeAll()->add(new DeletedRestriction());
        return $queryBuilder
            ->select('*')
            ->from($tableName)
            ->where($queryBuilder->expr()->eq('import_identifier', $queryBuilder->createNamedParameter($importIdentifier)))
            ->orderBy('uid')
            ->executeQuery()
            ->fetchAllAssociative();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchContactsOfContract(string $tableName, int $contractUid): array
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable($tableName);
        $queryBuilder->getRestrictions()->removeAll()->add(new DeletedRestriction());
        return $queryBuilder
            ->select('*')
            ->from($tableName)
            ->where($queryBuilder->expr()->eq('contract', $queryBuilder->createNamedParameter($contractUid, Connection::PARAM_INT)))
            ->orderBy('sorting')
            ->addOrderBy('uid')
            ->executeQuery()
            ->fetchAllAssociative();
    }

    /**
     * @return array<int, int> the column of every row, deleted ones included, by uid
     */
    private function fetchColumn(string $tableName, string $column): array
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable($tableName);
        $queryBuilder->getRestrictions()->removeAll();
        $values = [];
        foreach ($queryBuilder->select('uid', $column)->from($tableName)->orderBy('uid')->executeQuery()->fetchAllAssociative() as $row) {
            $values[(int)$row['uid']] = (int)$row[$column];
        }
        return $values;
    }
}
