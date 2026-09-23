<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersons\Profile;

use FGTCLB\AcademicPersons\Domain\Model\Address;
use FGTCLB\AcademicPersons\Domain\Model\Contract;
use FGTCLB\AcademicPersons\Domain\Model\Email;
use FGTCLB\AcademicPersons\Domain\Model\PhoneNumber;
use FGTCLB\AcademicPersons\Domain\Model\Profile;
use FGTCLB\AcademicPersons\Domain\Repository\AddressRepository;
use FGTCLB\AcademicPersons\Domain\Repository\EmailRepository;
use FGTCLB\AcademicPersons\Domain\Repository\PhoneNumberRepository;
use FGTCLB\AcademicPersons\Settings\AcademicPersonsSettings;
use FGTCLB\AcademicPersons\Settings\FrontendUserSyncEntry;
use FGTCLB\AcademicPersons\Settings\FrontendUserSyncSettings;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;

/**
 * Writes a frontend user's data onto a profile and its imported contract, as
 * the `frontendUserSync` map of `Configuration/AcademicPersons/Settings.yaml`
 * says. The default {@see ProfileFactory} delegates to it, and a custom
 * profile factory can do the same with data from its own source: the data is
 * a frontend user record, or any array keyed like one, and must carry `uid`.
 *
 * The profile and the contract, and whether to create or remove the contract,
 * stay with the factory. What this class decides are the property values and
 * the contact records of the contract: it matches an imported record by its
 * import identifier, including hidden records, updates it, creates the missing
 * one and removes one whose source columns are all empty. Records without that
 * identifier - the ones an editor added - are never touched, and neither is
 * the visibility of any record.
 *
 * Each method throws an \UnexpectedValueException (1790142324) when the map
 * has a problem, before it writes anything. The map is not checked against
 * the data: a column the data lacks reads as empty, unless the caller asks
 * {@see self::assertColumnsExist()} first, as the default factory does.
 */
final readonly class FrontendUserProfileMapper
{
    public function __construct(
        private AcademicPersonsSettings $settings,
        private AddressRepository $addressRepository,
        private EmailRepository $emailRepository,
        private PhoneNumberRepository $phoneNumberRepository,
        private PersistenceManagerInterface $persistenceManager,
        private FrontendUserPhoneNumberTypeResolver $phoneNumberTypeResolver,
    ) {}

    /**
     * Sets every mapped profile property. An empty source column is written as
     * an empty value, an unmapped property keeps its value.
     *
     * @param array<string, mixed> $frontendUserData
     */
    public function applyProfile(array $frontendUserData, Profile $profile): void
    {
        foreach ($this->getSettings()->profile as $property => $column) {
            $value = (string)($frontendUserData[$column] ?? '');
            match ($property) {
                'title' => $profile->setTitle($value),
                'firstName' => $profile->setFirstName($value),
                'middleName' => $profile->setMiddleName($value),
                'lastName' => $profile->setLastName($value),
                'website' => $profile->setWebsite($value),
                'websiteTitle' => $profile->setWebsiteTitle($value),
                'publicationsLink' => $profile->setPublicationsLink($value),
                'publicationsLinkTitle' => $profile->setPublicationsLinkTitle($value),
                'coreCompetences' => $profile->setCoreCompetences($value),
                'miscellaneous' => $profile->setMiscellaneous($value),
                'supervisedThesis' => $profile->setSupervisedThesis($value),
                'supervisedDoctoralThesis' => $profile->setSupervisedDoctoralThesis($value),
                'teachingArea' => $profile->setTeachingArea($value),
                default => throw $this->unsupportedProperty('profile', $property),
            };
        }
    }

    /**
     * Refuses data that lacks its `uid` or a column the map reads - a
     * misspelled column would otherwise read as empty and remove the imported
     * records. A frontend user record carries every column of `fe_users`.
     *
     * @param array<string, mixed> $frontendUserData
     * @throws \UnexpectedValueException
     */
    public function assertColumnsExist(array $frontendUserData): void
    {
        $missingColumns = array_values(array_filter(
            ['uid', ...$this->getSettings()->getColumns()],
            static fn(string $column): bool => !array_key_exists($column, $frontendUserData),
        ));
        if ($missingColumns === []) {
            return;
        }
        throw new \UnexpectedValueException(
            sprintf(
                'The frontendUserSync map of Configuration/AcademicPersons/Settings.yaml reads columns the frontend user data does not have: %s.',
                implode(', ', $missingColumns),
            ),
            1790142326,
        );
    }

    /**
     * Whether the map names any source of the imported contract, see
     * {@see FrontendUserSyncSettings::mapsContract()}. Without one, the
     * contract is left as it is.
     */
    public function mapsContract(): bool
    {
        return $this->getSettings()->mapsContract();
    }

    /**
     * Whether the frontend user carries data for the imported contract, see
     * {@see FrontendUserSyncSettings::hasContractData()}.
     *
     * @param array<string, mixed> $frontendUserData
     */
    public function hasContractData(array $frontendUserData): bool
    {
        return $this->getSettings()->hasContractData($frontendUserData);
    }

    /**
     * Sets the mapped contract properties and synchronises the physical
     * addresses, e-mail addresses and phone numbers of the contract.
     *
     * @param array<string, mixed> $frontendUserData
     * @param int<0, max> $pid
     */
    public function applyContract(array $frontendUserData, Contract $contract, int $pid): void
    {
        $settings = $this->getSettings();
        $uid = (string)($frontendUserData['uid'] ?? '');
        if ($uid === '') {
            throw new \InvalidArgumentException(
                'The frontend user data needs its uid, which the import identifiers are made of.',
                1790142327,
            );
        }
        foreach ($settings->contract as $property => $column) {
            $value = (string)($frontendUserData[$column] ?? '');
            match ($property) {
                'position' => $contract->setPosition($value),
                'room' => $contract->setRoom($value),
                default => throw $this->unsupportedProperty('contract', $property),
            };
        }
        $this->applyPhysicalAddresses($settings->physicalAddresses, $frontendUserData, $uid, $contract, $pid);
        $this->applyEmailAddresses($settings->emailAddresses, $frontendUserData, $uid, $contract, $pid);
        foreach ($settings->phoneNumbers as $entry) {
            $this->applyPhoneNumber($entry, $frontendUserData, $uid, $contract, $pid);
        }
    }

    /**
     * The normaliser accepts no other property, so only a settings object built
     * by hand reaches this.
     */
    private function unsupportedProperty(string $record, string $property): \LogicException
    {
        return new \LogicException(
            sprintf('The %s property "%s" cannot be synchronised from a frontend user.', $record, $property),
            1790142325,
        );
    }

    private function getSettings(): FrontendUserSyncSettings
    {
        $settings = $this->settings->frontendUserSync;
        $settings->assertValid();
        return $settings;
    }

    /**
     * The first entry of an address or e-mail list keeps the identifier the
     * synchronisation used before the lists existed, so its records are matched
     * without a migration. Every further entry is named by its first column.
     */
    private function getListEntryImportIdentifier(int $index, FrontendUserSyncEntry $entry, string $uid): string
    {
        return $index === 0
            ? sprintf('fe_users:%s', $uid)
            : sprintf('%s:fe_users:%s', $entry->getIdentifyingColumn(), $uid);
    }

    /**
     * @param list<FrontendUserSyncEntry> $entries
     * @param array<string, mixed> $frontendUserData
     * @param int<0, max> $pid
     */
    private function applyPhysicalAddresses(
        array $entries,
        array $frontendUserData,
        string $uid,
        Contract $contract,
        int $pid,
    ): void {
        if ($entries === []) {
            return;
        }
        // Match including hidden records, otherwise a record hidden in the frontend would not be
        // found and a duplicate would be created on the next synchronization. The visibility status
        // is intentionally never changed here, so manually hidden records stay hidden.
        $existingAddresses = [];
        $contractUid = (int)$contract->getUid();
        if ($contractUid > 0) {
            foreach ($this->addressRepository->findByContractIncludingHidden($contractUid) as $checkAddress) {
                // First match wins, as the ordering of the query decides.
                $existingAddresses[$checkAddress->getImportIdentifier()] ??= $checkAddress;
            }
        }
        foreach ($entries as $index => $entry) {
            $importIdentifier = $this->getListEntryImportIdentifier($index, $entry, $uid);
            $address = $existingAddresses[$importIdentifier] ?? null;
            if ($entry->isEmptyIn($frontendUserData)) {
                if ($address !== null) {
                    // No address data, remove previous attached address.
                    // Note that $addresses->detach() would only remove the relation and making the record
                    // orphan (unconnected) and removing (deleting) it is used keep the database clean
                    // and allows to use the history to restore them.
                    $this->persistenceManager->remove($address);
                }
                continue;
            }
            $isNewAddress = $address === null;
            if ($isNewAddress) {
                $address = new Address();
                $address->setPid($pid);
                $address->setImportIdentifier($importIdentifier);
                $contract->getPhysicalAddresses()->attach($address);
            }
            foreach ($entry->columns as $property => $column) {
                $value = (string)($frontendUserData[$column] ?? '');
                match ($property) {
                    'street' => $address->setStreet($value),
                    'streetNumber' => $address->setStreetNumber($value),
                    'additional' => $address->setAdditional($value),
                    'zip' => $address->setZip($value),
                    'city' => $address->setCity($value),
                    'state' => $address->setState($value),
                    'country' => $address->setCountry($value),
                    default => throw $this->unsupportedProperty('physical address', $property),
                };
            }
            if (!$isNewAddress) {
                // Existing record was matched via the repository (outside the profile aggregate),
                // so its changes have to be persisted explicitly.
                $this->addressRepository->update($address);
            }
        }
    }

    /**
     * @param list<FrontendUserSyncEntry> $entries
     * @param array<string, mixed> $frontendUserData
     * @param int<0, max> $pid
     */
    private function applyEmailAddresses(
        array $entries,
        array $frontendUserData,
        string $uid,
        Contract $contract,
        int $pid,
    ): void {
        if ($entries === []) {
            return;
        }
        // Match including hidden records, see applyPhysicalAddresses().
        $existingEmails = [];
        $contractUid = (int)$contract->getUid();
        if ($contractUid > 0) {
            foreach ($this->emailRepository->findByContractIncludingHidden($contractUid) as $checkEmail) {
                $existingEmails[$checkEmail->getImportIdentifier()] ??= $checkEmail;
            }
        }
        foreach ($entries as $index => $entry) {
            $importIdentifier = $this->getListEntryImportIdentifier($index, $entry, $uid);
            $email = $existingEmails[$importIdentifier] ?? null;
            if ($entry->isEmptyIn($frontendUserData)) {
                if ($email !== null) {
                    // Email address no longer set, remove the imported record, see applyPhysicalAddresses().
                    $this->persistenceManager->remove($email);
                }
                continue;
            }
            $isNewEmail = $email === null;
            if ($isNewEmail) {
                $email = new Email();
                $email->setPid($pid);
                $email->setImportIdentifier($importIdentifier);
                $contract->getEmailAddresses()->attach($email);
            }
            $email->setEmail((string)($frontendUserData[$entry->columns['email']] ?? ''));
            if (!$isNewEmail) {
                // Existing record was matched via the repository (outside the profile aggregate),
                // so its changes have to be persisted explicitly.
                $this->emailRepository->update($email);
            }
        }
    }

    /**
     * A phone number is identified by its column, `<column>:fe_users:<uid>`,
     * never by its type: the type is presentation, and reconfiguring it must
     * not import a second record (ACE-365). The telephone column also adopts a
     * record written under the identifier used before that change.
     *
     * @param array<string, mixed> $frontendUserData
     * @param int<0, max> $pid
     */
    private function applyPhoneNumber(
        FrontendUserSyncEntry $entry,
        array $frontendUserData,
        string $uid,
        Contract $contract,
        int $pid,
    ): void {
        $sourceField = $entry->columns['phoneNumber'];
        $importIdentifier = sprintf('%s:%s:%s', $sourceField, 'fe_users', $uid);
        $legacyImportIdentifier = $sourceField === 'telephone'
            ? sprintf('%s:%s:%s', 'phone', 'fe_users', $uid)
            : null;
        // The type the synchronisation wrote before ACE-365, which is not a selectable type.
        $legacyInvalidType = match ($sourceField) {
            'telephone' => 'phone',
            'fax' => 'fax',
            default => null,
        };
        // Match including hidden records, otherwise a record hidden in the frontend would not be
        // found and a duplicate would be created on the next synchronization. The visibility status
        // is intentionally never changed here, so manually hidden records stay hidden.
        $phoneNumber = null;
        $legacyPhoneNumber = null;
        $contractUid = (int)$contract->getUid();
        if ($contractUid > 0) {
            foreach ($this->phoneNumberRepository->findByContractIncludingHidden($contractUid) as $checkPhoneNumber) {
                if ($checkPhoneNumber->getImportIdentifier() === $importIdentifier) {
                    $phoneNumber = $checkPhoneNumber;
                    break;
                }
                if ($legacyImportIdentifier !== null
                    && $legacyPhoneNumber === null
                    && $checkPhoneNumber->getImportIdentifier() === $legacyImportIdentifier
                ) {
                    // First match, like the canonical one above: an installation whose
                    // pre-ACE-365 synchronization wrote a second record under the same
                    // legacy identifier must adopt one of them deterministically, and the
                    // ordering of the query decides which - not the position in the loop.
                    $legacyPhoneNumber = $checkPhoneNumber;
                }
            }
        }
        $phoneNumber ??= $legacyPhoneNumber;
        if ($entry->isEmptyIn($frontendUserData)) {
            if ($phoneNumber !== null) {
                // PhoneNumber<type> no longer set, remove previous imported PhoneNumber<type> to clean it up.
                // Note that $phoneNumbers->detach() would only remove the relation and making the record
                // orphan (unconnected) and removing (deleting) it is used keep the database clean
                // and allows to use the history to restore them.
                $this->persistenceManager->remove($phoneNumber);
            }
            return;
        }
        $configuredType = $this->resolvePhoneNumberType($entry);
        $isNewPhoneNumber = $phoneNumber === null;
        if ($isNewPhoneNumber) {
            // No PhoneNumber<type> record yet but PhoneNumber<type> exists, create new PhoneNumber<type> record and attach it.
            $phoneNumber = new PhoneNumber();
            $phoneNumber->setPid($pid);
            $phoneNumber->setType($configuredType);
            $phoneNumber->setImportIdentifier($importIdentifier);
            $contract->getPhoneNumbers()->attach($phoneNumber);
        } else {
            if ($phoneNumber->getImportIdentifier() === $legacyImportIdentifier) {
                $phoneNumber->setImportIdentifier($importIdentifier);
            }
            if ($legacyInvalidType !== null
                && $phoneNumber->getType() === $legacyInvalidType
                && !$this->phoneNumberTypeResolver->isSelectable($legacyInvalidType)
            ) {
                $phoneNumber->setType($configuredType);
            }
        }
        $phoneNumber->setPhoneNumber((string)($frontendUserData[$sourceField] ?? ''));
        if (!$isNewPhoneNumber) {
            // Existing record was matched via the repository (outside the profile aggregate),
            // so its changes have to be persisted explicitly.
            $this->phoneNumberRepository->update($phoneNumber);
        }
    }

    /**
     * The type of the entry when it names one, the type the extension
     * configuration sets for fax or telephone numbers when it does not. A type
     * that is not selectable becomes the undefined type `''`.
     */
    private function resolvePhoneNumberType(FrontendUserSyncEntry $entry): string
    {
        if ($entry->type === '') {
            return $entry->columns['phoneNumber'] === 'fax'
                ? $this->phoneNumberTypeResolver->getFaxNumberType()
                : $this->phoneNumberTypeResolver->getTelephoneNumberType();
        }
        return $this->phoneNumberTypeResolver->isSelectable($entry->type) ? $entry->type : '';
    }
}
