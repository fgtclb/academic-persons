<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersons\Import;

use FGTCLB\AcademicPersons\DataHandling\ProfileWriteCorrelation;
use FGTCLB\AcademicPersons\Event\BeforeImportedRecordWriteEvent;
use FGTCLB\AcademicPersons\Profile\ManagedFieldResolver;
use FGTCLB\AcademicPersons\Service\DataHandlerExecutionContext;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\DataHandling\ReferenceIndexUpdater;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * Writes the persons of an external source through the DataHandler, one person
 * per call, and retires the records the source no longer supplies.
 *
 * Import code reads its source, maps each person to an {@see ImportedProfile}
 * and hands it to {@see self::write()}:
 *
 * ```php
 * $result = $this->profileImportWriter->write(new ImportedProfile(
 *     identifier: 'hr:4711',
 *     pid: 42,
 *     fields: ['first_name' => 'Jane', 'last_name' => 'Doe'],
 *     contracts: [new ImportedContract(identifier: 'hr:4711-1', fields: ['position' => 'Professor'])],
 * ));
 * ```
 *
 * Every record is matched by its import identifier, see
 * {@see ImportedRecordFinder}. A new record gets every supplied field. An
 * existing record gets only the supplied fields that are managed on it, see
 * the `managedFields` map of `Configuration/AcademicPersons/Settings.yaml`, so
 * what an editor changed beside them survives. `hidden` is never written on an
 * existing record. A profile excluded from the synchronisation with `skip_sync`
 * is not written at all, nor is anything of it.
 *
 * The whole person is one DataHandler run, as a live backend user, marked
 * {@see ProfileWriteCorrelation::Import}. History, the reference index and the
 * hooks apply as for a backend save, and the profile is announced once with
 * the origin import. With `academic_persons_edit` installed, that
 * synchronises its translations and its slug.
 *
 * Stateless: everything is passed in or read from the database per call.
 *
 * @api
 */
#[Autoconfigure(public: true)]
final readonly class ProfileImportWriter
{
    private const PROFILE_TABLE = 'tx_academicpersons_domain_model_profile';
    private const CONTRACT_TABLE = 'tx_academicpersons_domain_model_contract';
    private const UNIT_TABLE = 'tx_academicpersons_domain_model_organisational_unit';
    private const FUNCTION_TYPE_TABLE = 'tx_academicpersons_domain_model_function_type';

    /**
     * The contact lists of a contract: the property of {@see ImportedContract},
     * the inline column of the contract and the table of the records.
     */
    private const CONTACT_LISTS = [
        'emailAddresses' => ['email_addresses', 'tx_academicpersons_domain_model_email'],
        'phoneNumbers' => ['phone_numbers', 'tx_academicpersons_domain_model_phone_number'],
        'physicalAddresses' => ['physical_addresses', 'tx_academicpersons_domain_model_address'],
    ];

    /**
     * The columns the writer owns on every record. The fields of an imported
     * record must not name them, and a listener cannot change them.
     */
    private const SYSTEM_COLUMNS = [
        'uid',
        'pid',
        'import_identifier',
        'deleted',
        'sys_language_uid',
        'l10n_parent',
        'l10n_source',
        'l10n_state',
        'l10n_diffsource',
        't3ver_oid',
        't3ver_wsid',
        't3ver_state',
        't3ver_stage',
    ];

    /**
     * The relation columns the writer owns per table.
     */
    private const RELATION_COLUMNS = [
        self::PROFILE_TABLE => ['contracts'],
        self::CONTRACT_TABLE => ['profile', 'organisational_unit', 'function_type', 'email_addresses', 'phone_numbers', 'physical_addresses'],
        'tx_academicpersons_domain_model_email' => ['contract'],
        'tx_academicpersons_domain_model_phone_number' => ['contract'],
        'tx_academicpersons_domain_model_address' => ['contract'],
    ];

    /**
     * The relation columns a listener may still set, because the writer
     * resolves them into the row it offers: a contract's unit and function type.
     */
    private const LISTENER_RELATION_COLUMNS = ['organisational_unit', 'function_type'];

    public function __construct(
        private ConnectionPool $connectionPool,
        private DataHandlerExecutionContext $executionContext,
        private EventDispatcherInterface $eventDispatcher,
        private ImportedRecordFinder $importedRecordFinder,
        private ManagedFieldResolver $managedFieldResolver,
    ) {}

    /**
     * Creates or updates one person.
     *
     * @throws \InvalidArgumentException when an identifier is empty or used twice in the person, a field names a
     *                                   column the writer owns, or a new profile has no page
     * @throws \UnexpectedValueException when the `managedFields` map has a problem
     */
    public function write(ImportedProfile $profile): ImportResult
    {
        $this->assertValid($profile);
        $profileUid = $this->importedRecordFinder->findUid(self::PROFILE_TABLE, $profile->identifier);
        $storedProfile = $profileUid === null ? null : $this->findRow(self::PROFILE_TABLE, $profileUid);
        if ($storedProfile === null) {
            $profileUid = null;
            if ($profile->pid <= 0) {
                throw new \InvalidArgumentException(
                    sprintf('The new profile "%s" needs a page.', $profile->identifier),
                    1790970747,
                );
            }
        } elseif ((int)$storedProfile['skip_sync'] === 1) {
            return new ImportResult($this->skipPerson($profile, ImportedRecordOutcome::Skipped, 'The profile is excluded from the synchronisation.'));
        }
        $pid = $storedProfile === null ? $profile->pid : (int)$storedProfile['pid'];

        $event = $this->offer(self::PROFILE_TABLE, $profile->identifier, $profileUid, $storedProfile, $profile->fields);
        if ($event->isVetoed()) {
            return new ImportResult($this->skipPerson($profile, ImportedRecordOutcome::Vetoed, (string)$event->getVetoReason()));
        }
        $profileRow = $this->restrictRow(self::PROFILE_TABLE, $storedProfile, $event->getRow());
        // Each entry is the result of a record, or the record the run is
        // about to write, which is resolved to its result once it ran.
        /** @var list<ImportedRecordResult|array{table: string, identifier: string, id: int|string, written: bool}> $entries */
        $entries = [];
        $messages = [];
        $datamap = [];
        $profileId = $profileUid ?? StringUtility::getUniqueId('NEW');
        $datamap[self::PROFILE_TABLE][$profileId] = $this->completeRow($profileRow, $storedProfile, $pid, $profile->identifier);
        if ($storedProfile !== null) {
            // The DataHandler drops an empty row, and only a profile in the
            // datamap is announced. The identifier it has keeps it in the run
            // without changing it.
            $datamap[self::PROFILE_TABLE][$profileId]['import_identifier'] = $profile->identifier;
        }
        $entries[] = ['table' => self::PROFILE_TABLE, 'identifier' => $profile->identifier, 'id' => $profileId, 'written' => $profileRow !== []];

        $newContractIds = [];
        foreach ($profile->contracts as $contract) {
            $contractUid = $this->importedRecordFinder->findUid(self::CONTRACT_TABLE, $contract->identifier);
            $storedContract = $contractUid === null ? null : $this->findRow(self::CONTRACT_TABLE, $contractUid);
            if ($storedContract !== null && (int)$storedContract['profile'] !== $profileUid) {
                array_push($entries, ...$this->skipContract($contract, ImportedRecordOutcome::Skipped, 'The contract belongs to another profile.', $contractUid));
                continue;
            }
            $contractUid = $storedContract === null ? null : $contractUid;
            $fields = $contract->fields;
            foreach ([
                'organisational_unit' => [self::UNIT_TABLE, $contract->organisationalUnitIdentifier, 'organisational unit'],
                'function_type' => [self::FUNCTION_TYPE_TABLE, $contract->functionTypeIdentifier, 'function type'],
            ] as $column => [$tableName, $identifier, $label]) {
                if ($identifier === null || $identifier === '') {
                    continue;
                }
                $uid = $this->importedRecordFinder->findUid($tableName, $identifier);
                if ($uid === null) {
                    $messages[] = sprintf('No %s carries the identifier "%s", it is not set on the contract "%s".', $label, $identifier, $contract->identifier);
                    continue;
                }
                $fields[$column] = $uid;
            }
            $event = $this->offer(self::CONTRACT_TABLE, $contract->identifier, $contractUid, $storedContract, $fields);
            if ($event->isVetoed()) {
                array_push($entries, ...$this->skipContract($contract, ImportedRecordOutcome::Vetoed, (string)$event->getVetoReason(), $contractUid));
                continue;
            }
            $contractRow = $this->restrictRow(self::CONTRACT_TABLE, $storedContract, $event->getRow());
            $contractId = $contractUid ?? StringUtility::getUniqueId('NEW');
            $datamap[self::CONTRACT_TABLE][$contractId] = $this->completeRow($contractRow, $storedContract, $pid, $contract->identifier);
            $entries[] = ['table' => self::CONTRACT_TABLE, 'identifier' => $contract->identifier, 'id' => $contractId, 'written' => $contractRow !== []];
            if ($contractUid === null) {
                $newContractIds[] = $contractId;
            }

            foreach (self::CONTACT_LISTS as $property => [$column, $tableName]) {
                $newContactIds = [];
                foreach ($contract->{$property} as $contact) {
                    $contactUid = $this->importedRecordFinder->findUid($tableName, $contact->identifier);
                    $storedContact = $contactUid === null ? null : $this->findRow($tableName, $contactUid);
                    if ($storedContact !== null && (int)$storedContact['contract'] !== $contractUid) {
                        $entries[] = new ImportedRecordResult($tableName, $contact->identifier, ImportedRecordOutcome::Skipped, $contactUid, 'The record belongs to another contract.');
                        continue;
                    }
                    $contactUid = $storedContact === null ? null : $contactUid;
                    $event = $this->offer($tableName, $contact->identifier, $contactUid, $storedContact, $contact->fields);
                    if ($event->isVetoed()) {
                        $entries[] = new ImportedRecordResult($tableName, $contact->identifier, ImportedRecordOutcome::Vetoed, $contactUid, (string)$event->getVetoReason());
                        continue;
                    }
                    $contactRow = $this->restrictRow($tableName, $storedContact, $event->getRow());
                    $contactId = $contactUid ?? StringUtility::getUniqueId('NEW');
                    $datamap[$tableName][$contactId] = $this->completeRow($contactRow, $storedContact, $pid, $contact->identifier);
                    $entries[] = ['table' => $tableName, 'identifier' => $contact->identifier, 'id' => $contactId, 'written' => $contactRow !== []];
                    if ($contactUid === null) {
                        $newContactIds[] = $contactId;
                    }
                }
                if ($newContactIds !== []) {
                    $datamap[self::CONTRACT_TABLE][$contractId][$column] = $this->buildChildList($tableName, 'contract', $contractUid, $newContactIds);
                }
            }
        }
        if ($newContractIds !== []) {
            $datamap[self::PROFILE_TABLE][$profileId]['contracts'] = $this->buildChildList(self::CONTRACT_TABLE, 'profile', $profileUid, $newContractIds);
        }

        [$substitutedIds, $errors] = $this->process($datamap, []);

        $records = [];
        foreach ($entries as $entry) {
            if ($entry instanceof ImportedRecordResult) {
                $records[] = $entry;
                continue;
            }
            $id = $entry['id'];
            if (is_int($id)) {
                $outcome = $entry['written'] ? ImportedRecordOutcome::Updated : ImportedRecordOutcome::Unchanged;
                $records[] = new ImportedRecordResult($entry['table'], $entry['identifier'], $outcome, $id);
                continue;
            }
            $uid = (int)($substitutedIds[$id] ?? 0);
            $records[] = $uid > 0
                ? new ImportedRecordResult($entry['table'], $entry['identifier'], ImportedRecordOutcome::Created, $uid)
                : new ImportedRecordResult($entry['table'], $entry['identifier'], ImportedRecordOutcome::Failed, null, 'The DataHandler did not create the record.');
        }
        return new ImportResult($records, $messages, $errors);
    }

    /**
     * Hides or deletes the records of a source whose identifiers are not kept.
     *
     * Every profile, contract and contact record whose identifier starts with
     * `<source>:` is retired unless its identifier is in the list. A record
     * without an identifier, a record of another source and a record of a
     * profile excluded from the synchronisation are never retired. The
     * identifiers are compared exactly.
     *
     * Hiding leaves a record that is hidden already alone. Deleting a profile
     * deletes its contracts and their contact records with it, and deleting a
     * contract its contact records, the ones an editor created included.
     *
     * The profiles whose contracts or contact records were retired are
     * announced with the origin import, so their translations follow where
     * `academic_persons_edit` synchronises them.
     *
     * @param list<string> $keepIdentifiers the identifiers the source still supplies, of every table
     * @throws \InvalidArgumentException for an empty source
     */
    public function retire(string $source, array $keepIdentifiers, RetirePolicy $policy): ImportResult
    {
        if (trim($source) === '') {
            throw new \InvalidArgumentException('Records can only be retired for a source.', 1790970748);
        }
        $prefix = $source . ':';
        $keep = array_fill_keys($keepIdentifiers, true);
        $hide = $policy === RetirePolicy::Hide;
        // The stored profiles, by uid, read once per profile and call.
        $profiles = [];
        $contractProfiles = [];

        $retired = [];
        foreach ($this->findRetirementCandidates(self::PROFILE_TABLE, $prefix, 'skip_sync') as $row) {
            if (!isset($keep[$row['import_identifier']]) && (int)$row['skip_sync'] === 0 && (!$hide || (int)$row['hidden'] === 0)) {
                $retired[self::PROFILE_TABLE][(int)$row['uid']] = (string)$row['import_identifier'];
            }
        }
        $affectedProfiles = [];
        foreach ($this->findRetirementCandidates(self::CONTRACT_TABLE, $prefix, 'profile') as $row) {
            $profileUid = (int)$row['profile'];
            $contractProfiles[(int)$row['uid']] = $profileUid;
            if (isset($keep[$row['import_identifier']])
                || ($hide && (int)$row['hidden'] === 1)
                || !$this->isProfileSynchronised($profileUid, $profiles)
                || (!$hide && isset($retired[self::PROFILE_TABLE][$profileUid]))
            ) {
                continue;
            }
            $retired[self::CONTRACT_TABLE][(int)$row['uid']] = (string)$row['import_identifier'];
            $affectedProfiles[$profileUid] = true;
        }
        foreach (self::CONTACT_LISTS as [, $tableName]) {
            foreach ($this->findRetirementCandidates($tableName, $prefix, 'contract') as $row) {
                $contractUid = (int)$row['contract'];
                $contractProfiles[$contractUid] ??= $this->findContractProfile($contractUid);
                $profileUid = $contractProfiles[$contractUid];
                if (isset($keep[$row['import_identifier']])
                    || ($hide && (int)$row['hidden'] === 1)
                    || !$this->isProfileSynchronised($profileUid, $profiles)
                    || (!$hide && (isset($retired[self::CONTRACT_TABLE][$contractUid]) || isset($retired[self::PROFILE_TABLE][$profileUid])))
                ) {
                    continue;
                }
                $retired[$tableName][(int)$row['uid']] = (string)$row['import_identifier'];
                $affectedProfiles[$profileUid] = true;
            }
        }
        if ($retired === []) {
            return new ImportResult();
        }

        $records = [];
        $retiredMap = [];
        foreach ($retired as $tableName => $identifiers) {
            foreach ($identifiers as $uid => $identifier) {
                $retiredMap[$tableName][$uid] = $hide ? ['hidden' => 1] : ['delete' => 1];
                $records[] = new ImportedRecordResult($tableName, $identifier, $hide ? ImportedRecordOutcome::Hidden : ImportedRecordOutcome::Deleted, $uid);
            }
        }
        // A profile that is in the datamap is announced. One whose contracts
        // or contact records were retired gets the identifier it has: the
        // DataHandler drops an empty row.
        $announcements = [];
        foreach (array_keys($affectedProfiles) as $profileUid) {
            if (!isset($retired[self::PROFILE_TABLE][$profileUid])) {
                $announcements[self::PROFILE_TABLE][$profileUid] = ['import_identifier' => (string)($profiles[$profileUid]['import_identifier'] ?? '')];
            }
        }
        if ($hide) {
            $datamap = $retiredMap;
            foreach ($announcements[self::PROFILE_TABLE] ?? [] as $profileUid => $row) {
                $datamap[self::PROFILE_TABLE][$profileUid] = $row;
            }
            [, $errors] = $this->process($datamap, []);
            return new ImportResult($records, [], $errors);
        }
        // The announcement follows the deletion, so the translations lose what
        // the default language lost. A run announces its datamap before it
        // processes its commands, so these are two runs.
        [, $errors] = $this->process([], $retiredMap);
        if ($announcements !== []) {
            [, $announcementErrors] = $this->process($announcements, []);
            $errors = [...$errors, ...$announcementErrors];
        }
        return new ImportResult($records, [], $errors);
    }

    /**
     * Offers a record to the listeners, with the row the writer would store.
     *
     * @param array<string, mixed>|null $storedRow
     * @param array<string, mixed> $fields
     */
    private function offer(string $tableName, string $identifier, ?int $uid, ?array $storedRow, array $fields): BeforeImportedRecordWriteEvent
    {
        $event = new BeforeImportedRecordWriteEvent($tableName, $identifier, $uid, $this->restrictRow($tableName, $storedRow, $fields), $fields);
        $this->eventDispatcher->dispatch($event);
        return $event;
    }

    /**
     * The columns of a row the writer stores: on a new record every column but
     * the ones the writer owns, on an existing record only the managed ones,
     * and never `hidden`.
     *
     * @param array<string, mixed>|null $storedRow
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function restrictRow(string $tableName, ?array $storedRow, array $row): array
    {
        $ownedColumns = array_diff(
            [...self::SYSTEM_COLUMNS, ...self::RELATION_COLUMNS[$tableName]],
            self::LISTENER_RELATION_COLUMNS,
        );
        $row = array_diff_key($row, array_flip($ownedColumns));
        if ($storedRow === null) {
            return $row;
        }
        // The shipped settings declare no field on `hidden`, so the map does
        // not name it. A field a site package declares on that column is
        // dropped here.
        unset($row['hidden']);
        return array_intersect_key($row, array_flip($this->managedFieldResolver->getManagedColumns($tableName, $storedRow)));
    }

    /**
     * @param array<string, mixed> $row
     * @param array<string, mixed>|null $storedRow
     * @return array<string, mixed>
     */
    private function completeRow(array $row, ?array $storedRow, int $pid, string $identifier): array
    {
        if ($storedRow !== null) {
            return $row;
        }
        return [...$row, 'pid' => $pid, 'import_identifier' => $identifier];
    }

    /**
     * @throws \InvalidArgumentException
     */
    private function assertValid(ImportedProfile $profile): void
    {
        $identifiers = [];
        $check = function (string $tableName, string $identifier, array $fields) use (&$identifiers): void {
            if (trim($identifier) === '') {
                throw new \InvalidArgumentException(
                    sprintf('A record of "%s" has no import identifier.', $tableName),
                    1790970749,
                );
            }
            if (isset($identifiers[$tableName][$identifier])) {
                throw new \InvalidArgumentException(
                    sprintf('The import identifier "%s" is used twice for "%s".', $identifier, $tableName),
                    1790970750,
                );
            }
            $identifiers[$tableName][$identifier] = true;
            $ownedColumns = array_intersect(
                array_keys($fields),
                [...self::SYSTEM_COLUMNS, ...self::RELATION_COLUMNS[$tableName]],
            );
            if ($ownedColumns !== []) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'The fields of "%s" in "%s" name %s, which the writer sets itself.',
                        $identifier,
                        $tableName,
                        implode(', ', $ownedColumns),
                    ),
                    1790970751,
                );
            }
        };
        $check(self::PROFILE_TABLE, $profile->identifier, $profile->fields);
        foreach ($profile->contracts as $contract) {
            $check(self::CONTRACT_TABLE, $contract->identifier, $contract->fields);
            foreach (self::CONTACT_LISTS as $property => [, $tableName]) {
                foreach ($contract->{$property} as $contact) {
                    $check($tableName, $contact->identifier, $contact->fields);
                }
            }
        }
    }

    /**
     * The results of a person that is not written: the profile with the
     * outcome and reason given, every record of it skipped.
     *
     * @return list<ImportedRecordResult>
     */
    private function skipPerson(ImportedProfile $profile, ImportedRecordOutcome $outcome, string $reason): array
    {
        $profileUid = $this->importedRecordFinder->findUid(self::PROFILE_TABLE, $profile->identifier);
        $records = [new ImportedRecordResult(self::PROFILE_TABLE, $profile->identifier, $outcome, $profileUid, $reason)];
        $childReason = $outcome === ImportedRecordOutcome::Skipped ? $reason : 'Its profile was not written.';
        foreach ($profile->contracts as $contract) {
            array_push($records, ...$this->skipContract($contract, ImportedRecordOutcome::Skipped, $childReason));
        }
        return $records;
    }

    /**
     * The results of a contract that is not written: the contract with the
     * outcome and reason given, every contact record of it skipped.
     *
     * @return list<ImportedRecordResult>
     */
    private function skipContract(ImportedContract $contract, ImportedRecordOutcome $outcome, string $reason, ?int $uid = null): array
    {
        $records = [new ImportedRecordResult(self::CONTRACT_TABLE, $contract->identifier, $outcome, $uid, $reason)];
        $childReason = $outcome === ImportedRecordOutcome::Skipped ? $reason : 'Its contract was not written.';
        foreach (self::CONTACT_LISTS as $property => [, $tableName]) {
            foreach ($contract->{$property} as $contact) {
                $records[] = new ImportedRecordResult($tableName, $contact->identifier, ImportedRecordOutcome::Skipped, null, $childReason);
            }
        }
        return $records;
    }

    /**
     * The value of an inline column that adds new children to a parent: the
     * live default-language children it has, in their order, then the new
     * ones. The DataHandler writes the parent and the sorting of every child
     * listed, and leaves a child that is not listed alone, so a child an
     * editor created keeps its place.
     *
     * @param list<int|string> $newIds
     */
    private function buildChildList(string $tableName, string $parentColumn, ?int $parentUid, array $newIds): string
    {
        $uids = [];
        if ($parentUid !== null) {
            $queryBuilder = $this->connectionPool->getQueryBuilderForTable($tableName);
            $queryBuilder->getRestrictions()
                ->removeAll()
                ->add(new DeletedRestriction());
            $uids = $queryBuilder
                ->select('uid')
                ->from($tableName)
                ->where(
                    $queryBuilder->expr()->eq($parentColumn, $queryBuilder->createNamedParameter($parentUid, Connection::PARAM_INT)),
                    $queryBuilder->expr()->in('sys_language_uid', $queryBuilder->quoteArrayBasedValueListToIntegerList([0, -1])),
                    $queryBuilder->expr()->eq('t3ver_wsid', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                )
                ->orderBy('sorting')
                ->addOrderBy('uid')
                ->executeQuery()
                ->fetchFirstColumn();
        }
        return implode(',', [...array_map(intval(...), $uids), ...$newIds]);
    }

    /**
     * Runs the DataHandler as a live backend user, marked as an import.
     *
     * The run passes a reference index updater of its own and flushes it: one
     * started from inside another DataHandler run is nested in it, and the
     * DataHandler flushes the index of the outermost run only. Such a nested
     * run is not announced either, see {@see ProfileWriteCorrelation}.
     *
     * @param array<string, array<int|string, array<string, mixed>>> $datamap
     * @param array<string, array<int, array<string, mixed>>> $cmdmap
     * @return array{0: array<string, int>, 1: list<string>} the uids of the new records by their NEW id, and the errors
     */
    private function process(array $datamap, array $cmdmap): array
    {
        $substitutedIds = [];
        $errors = [];
        $this->executionContext->runAsLiveBackendUser(
            function (BackendUserAuthentication $backendUser) use ($datamap, $cmdmap, &$substitutedIds, &$errors): void {
                $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
                $referenceIndexUpdater = GeneralUtility::makeInstance(ReferenceIndexUpdater::class);
                $dataHandler->start($datamap, $cmdmap, $backendUser, $referenceIndexUpdater);
                $dataHandler->setCorrelationId(ProfileWriteCorrelation::Import->create());
                if ($datamap !== []) {
                    $dataHandler->process_datamap();
                }
                if ($cmdmap !== []) {
                    $dataHandler->process_cmdmap();
                }
                $referenceIndexUpdater->update();
                $substitutedIds = array_map(intval(...), $dataHandler->substNEWwithIDs);
                $errors = array_values(array_map(strval(...), $dataHandler->errorLog));
            },
        );
        return [$substitutedIds, $errors];
    }

    /**
     * The live default-language records of a table whose identifier starts
     * with the prefix. `LIKE` narrows them on the indexed column, the exact
     * comparison happens in PHP: MySQL and MariaDB compare without case there.
     *
     * @return list<array<string, int|string>> the uid, the identifier, `hidden` and the column asked for
     */
    private function findRetirementCandidates(string $tableName, string $prefix, string $column): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($tableName);
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(new DeletedRestriction());
        $result = $queryBuilder
            ->select('uid', 'import_identifier', 'hidden', $column)
            ->from($tableName)
            ->where(
                $queryBuilder->expr()->like(
                    'import_identifier',
                    $queryBuilder->createNamedParameter($queryBuilder->escapeLikeWildcards($prefix) . '%'),
                ),
                $queryBuilder->expr()->in('sys_language_uid', $queryBuilder->quoteArrayBasedValueListToIntegerList([0, -1])),
                $queryBuilder->expr()->eq('t3ver_wsid', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
            )
            ->orderBy('uid')
            ->executeQuery();
        $rows = [];
        while ($row = $result->fetchAssociative()) {
            $identifier = (string)$row['import_identifier'];
            if (str_starts_with($identifier, $prefix)) {
                $rows[] = [
                    'uid' => (int)$row['uid'],
                    'import_identifier' => $identifier,
                    'hidden' => (int)$row['hidden'],
                    $column => (int)$row[$column],
                ];
            }
        }
        return $rows;
    }

    /**
     * @param array<int, array<string, mixed>|null> $profiles the stored profiles read so far, by uid
     */
    private function isProfileSynchronised(int $profileUid, array &$profiles): bool
    {
        if (!array_key_exists($profileUid, $profiles)) {
            $profiles[$profileUid] = $this->findRow(self::PROFILE_TABLE, $profileUid);
        }
        $profile = $profiles[$profileUid];
        return $profile !== null
            && (int)$profile['sys_language_uid'] <= 0
            && (int)$profile['skip_sync'] === 0;
    }

    private function findContractProfile(int $contractUid): int
    {
        return (int)($this->findRow(self::CONTRACT_TABLE, $contractUid)['profile'] ?? 0);
    }

    /**
     * The stored row of a record that is not deleted, whatever its visibility.
     *
     * @return array<string, mixed>|null
     */
    private function findRow(string $tableName, int $uid): ?array
    {
        if ($uid <= 0) {
            return null;
        }
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($tableName);
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(new DeletedRestriction());
        $row = $queryBuilder
            ->select('*')
            ->from($tableName)
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)))
            ->executeQuery()
            ->fetchAssociative();
        return $row === false ? null : $row;
    }
}
