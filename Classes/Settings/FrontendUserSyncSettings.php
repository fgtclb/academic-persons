<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Settings;

use Symfony\Component\DependencyInjection\Attribute\Exclude;

/**
 * The `frontendUserSync` map: which `fe_users` column feeds which profile and
 * contract property, and which columns become the physical addresses, e-mail
 * addresses and phone numbers of the imported contract. Only mapped
 * properties are kept; a property mapped to `''` is not synchronised.
 *
 * A mistake in the map - an unknown property, a value that is not a string, a
 * list that is no list - is recorded in `problems` rather than thrown: the
 * graph is built while the TCA is loaded, and a typo in the synchronisation
 * must not take the installation down. {@see self::assertValid()} throws it
 * where the map is used.
 *
 * @internal not part of public API.
 */
#[Exclude]
final class FrontendUserSyncSettings
{
    /**
     * The profile properties a column can feed: the free text and link fields.
     * The select `gender` and the derived `*Alpha` letters are left out.
     */
    public const PROFILE_PROPERTIES = [
        'title',
        'firstName',
        'middleName',
        'lastName',
        'website',
        'websiteTitle',
        'publicationsLink',
        'publicationsLinkTitle',
        'coreCompetences',
        'miscellaneous',
        'supervisedThesis',
        'supervisedDoctoralThesis',
        'teachingArea',
    ];

    public const CONTRACT_PROPERTIES = [
        'position',
        'room',
    ];

    public const PHYSICAL_ADDRESS_PROPERTIES = [
        'street',
        'streetNumber',
        'additional',
        'zip',
        'city',
        'state',
        'country',
    ];

    /**
     * @param array<string, non-empty-string> $profile profile property => `fe_users` column
     * @param array<string, non-empty-string> $contract contract property => `fe_users` column
     * @param list<FrontendUserSyncEntry> $physicalAddresses
     * @param list<FrontendUserSyncEntry> $emailAddresses columns keyed `email`
     * @param list<FrontendUserSyncEntry> $phoneNumbers columns keyed `phoneNumber`
     * @param list<string> $problems what the map got wrong, one sentence each
     */
    public function __construct(
        public readonly array $profile = [],
        public readonly array $contract = [],
        public readonly array $physicalAddresses = [],
        public readonly array $emailAddresses = [],
        public readonly array $phoneNumbers = [],
        public readonly array $problems = [],
    ) {}

    /**
     * @param array{
     *     profile?: array<string, non-empty-string>,
     *     contract?: array<string, non-empty-string>,
     *     physicalAddresses?: list<FrontendUserSyncEntry>,
     *     emailAddresses?: list<FrontendUserSyncEntry>,
     *     phoneNumbers?: list<FrontendUserSyncEntry>,
     *     problems?: list<string>,
     * } $array
     */
    public static function __set_state(array $array): self
    {
        return new self(
            profile: $array['profile'] ?? [],
            contract: $array['contract'] ?? [],
            physicalAddresses: $array['physicalAddresses'] ?? [],
            emailAddresses: $array['emailAddresses'] ?? [],
            phoneNumbers: $array['phoneNumbers'] ?? [],
            problems: $array['problems'] ?? [],
        );
    }

    /**
     * @throws \UnexpectedValueException when the map has a problem
     */
    public function assertValid(): void
    {
        if ($this->problems === []) {
            return;
        }
        throw new \UnexpectedValueException(
            'The frontendUserSync map of Configuration/AcademicPersons/Settings.yaml is invalid: '
            . implode(' ', $this->problems),
            1790142324,
        );
    }

    /**
     * Whether the map names any source of the imported contract - a contract
     * property or a contact entry. Without one, the contract is not
     * synchronised: it is neither created nor removed nor written.
     */
    public function mapsContract(): bool
    {
        return $this->contract !== []
            || $this->physicalAddresses !== []
            || $this->emailAddresses !== []
            || $this->phoneNumbers !== [];
    }

    /**
     * Every `fe_users` column the map reads.
     *
     * @return list<non-empty-string>
     */
    public function getColumns(): array
    {
        $columns = [...array_values($this->profile), ...array_values($this->contract)];
        foreach ([...$this->physicalAddresses, ...$this->emailAddresses, ...$this->phoneNumbers] as $entry) {
            $columns = [...$columns, ...array_values($entry->columns)];
        }
        return array_values(array_unique($columns));
    }

    /**
     * Whether the frontend user carries anything the imported contract is made
     * of: a mapped contract property or a column of any contact entry. Without
     * it, an update removes the imported contract, or does not create one -
     * provided the map names a source at all, see {@see self::mapsContract()}.
     *
     * @param array<string, mixed> $frontendUserData
     */
    public function hasContractData(array $frontendUserData): bool
    {
        foreach ($this->contract as $column) {
            if (!empty($frontendUserData[$column])) {
                return true;
            }
        }
        foreach ([...$this->physicalAddresses, ...$this->emailAddresses, ...$this->phoneNumbers] as $entry) {
            if (!$entry->isEmptyIn($frontendUserData)) {
                return true;
            }
        }
        return false;
    }
}
