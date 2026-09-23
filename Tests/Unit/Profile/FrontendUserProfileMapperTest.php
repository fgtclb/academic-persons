<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Unit\Profile;

use FGTCLB\AcademicPersons\Domain\Model\Contract;
use FGTCLB\AcademicPersons\Domain\Model\Profile;
use FGTCLB\AcademicPersons\Domain\Repository\AddressRepository;
use FGTCLB\AcademicPersons\Domain\Repository\EmailRepository;
use FGTCLB\AcademicPersons\Domain\Repository\PhoneNumberRepository;
use FGTCLB\AcademicPersons\Profile\FrontendUserPhoneNumberTypeResolver;
use FGTCLB\AcademicPersons\Profile\FrontendUserProfileMapper;
use FGTCLB\AcademicPersons\Settings\AcademicPersonsSettings;
use FGTCLB\AcademicPersons\Settings\FrontendUserSyncEntry;
use FGTCLB\AcademicPersons\Settings\FrontendUserSyncSettings;
use FGTCLB\AcademicPersons\Types\PhoneNumberTypes;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * What the mapper writes onto records that are not persisted yet - so no
 * repository is asked for existing ones. Matching, updating and removing
 * persisted records is covered by the functional tests of both commands.
 */
final class FrontendUserProfileMapperTest extends UnitTestCase
{
    /**
     * Every property the map accepts reaches its setter, each from its own
     * column - a property accepted by the normaliser but missing in the mapper
     * would fail the first synchronisation with a \LogicException.
     */
    #[Test]
    public function everySupportedProfilePropertyIsWrittenFromItsColumn(): void
    {
        $map = [];
        $data = ['uid' => 7];
        foreach (FrontendUserSyncSettings::PROFILE_PROPERTIES as $property) {
            $map[$property] = 'column_' . $property;
            $data['column_' . $property] = 'value of ' . $property;
        }
        $profile = new Profile();

        $this->mapper(new FrontendUserSyncSettings(profile: $map))->applyProfile($data, $profile);

        $this->assertSame(
            [
                'title' => 'value of title',
                'firstName' => 'value of firstName',
                'middleName' => 'value of middleName',
                'lastName' => 'value of lastName',
                'website' => 'value of website',
                'websiteTitle' => 'value of websiteTitle',
                'publicationsLink' => 'value of publicationsLink',
                'publicationsLinkTitle' => 'value of publicationsLinkTitle',
                'coreCompetences' => 'value of coreCompetences',
                'miscellaneous' => 'value of miscellaneous',
                'supervisedThesis' => 'value of supervisedThesis',
                'supervisedDoctoralThesis' => 'value of supervisedDoctoralThesis',
                'teachingArea' => 'value of teachingArea',
            ],
            [
                'title' => $profile->getTitle(),
                'firstName' => $profile->getFirstName(),
                'middleName' => $profile->getMiddleName(),
                'lastName' => $profile->getLastName(),
                'website' => $profile->getWebsite(),
                'websiteTitle' => $profile->getWebsiteTitle(),
                'publicationsLink' => $profile->getPublicationsLink(),
                'publicationsLinkTitle' => $profile->getPublicationsLinkTitle(),
                'coreCompetences' => $profile->getCoreCompetences(),
                'miscellaneous' => $profile->getMiscellaneous(),
                'supervisedThesis' => $profile->getSupervisedThesis(),
                'supervisedDoctoralThesis' => $profile->getSupervisedDoctoralThesis(),
                'teachingArea' => $profile->getTeachingArea(),
            ],
        );
    }

    /**
     * A mapped column the frontend user leaves empty clears the property, as the
     * synchronisation did before the map; an unmapped property is not touched.
     */
    #[Test]
    public function anEmptyColumnClearsItsPropertyAndAnUnmappedOneIsKept(): void
    {
        $profile = new Profile();
        $profile->setTitle('Dr.');
        $profile->setWebsite('https://editor.example/');

        $this->mapper(new FrontendUserSyncSettings(profile: ['title' => 'title']))
            ->applyProfile(['uid' => 7, 'title' => '', 'www' => 'https://feuser.example/'], $profile);

        $this->assertSame('', $profile->getTitle());
        $this->assertSame('https://editor.example/', $profile->getWebsite());
    }

    #[Test]
    public function everySupportedContractAndAddressPropertyIsWrittenFromItsColumn(): void
    {
        $addressColumns = [];
        $data = ['uid' => 7, 'tx_position' => 'Professor', 'tx_room' => 'A 1.01'];
        foreach (FrontendUserSyncSettings::PHYSICAL_ADDRESS_PROPERTIES as $property) {
            $addressColumns[$property] = 'column_' . $property;
            $data['column_' . $property] = 'value of ' . $property;
        }
        $contract = new Contract();

        $this->mapper(new FrontendUserSyncSettings(
            contract: ['position' => 'tx_position', 'room' => 'tx_room'],
            physicalAddresses: [new FrontendUserSyncEntry($addressColumns)],
        ))->applyContract($data, $contract, 5);

        $this->assertSame('Professor', $contract->getPosition());
        $this->assertSame('A 1.01', $contract->getRoom());
        $addresses = $contract->getPhysicalAddresses()->toArray();
        $this->assertCount(1, $addresses);
        $this->assertSame(
            [
                'street' => 'value of street',
                'streetNumber' => 'value of streetNumber',
                'additional' => 'value of additional',
                'zip' => 'value of zip',
                'city' => 'value of city',
                'state' => 'value of state',
                'country' => 'value of country',
                'pid' => 5,
                'importIdentifier' => 'fe_users:7',
            ],
            [
                'street' => $addresses[0]->getStreet(),
                'streetNumber' => $addresses[0]->getStreetNumber(),
                'additional' => $addresses[0]->getAdditional(),
                'zip' => $addresses[0]->getZip(),
                'city' => $addresses[0]->getCity(),
                'state' => $addresses[0]->getState(),
                'country' => $addresses[0]->getCountry(),
                'pid' => $addresses[0]->getPid(),
                'importIdentifier' => $addresses[0]->getImportIdentifier(),
            ],
        );
    }

    /**
     * An entry without a type takes the configured fax type for `fax` and the
     * configured telephone type for every other column; a named type is kept
     * when the installation offers it and becomes the undefined type when not.
     * An empty column imports nothing - and `'0'` is empty, as PHP's empty()
     * sees it, which is what the synchronisation did before the map.
     */
    #[Test]
    public function aPhoneNumberTakesTheTypeOfItsEntryOrOfTheExtensionConfiguration(): void
    {
        $contract = new Contract();

        $this->mapper(new FrontendUserSyncSettings(phoneNumbers: [
            new FrontendUserSyncEntry(['phoneNumber' => 'telephone']),
            new FrontendUserSyncEntry(['phoneNumber' => 'fax']),
            new FrontendUserSyncEntry(['phoneNumber' => 'tx_mobile'], 'mobile'),
            new FrontendUserSyncEntry(['phoneNumber' => 'tx_pager'], 'pager'),
            new FrontendUserSyncEntry(['phoneNumber' => 'tx_empty'], 'mobile'),
            new FrontendUserSyncEntry(['phoneNumber' => 'tx_zero'], 'mobile'),
            new FrontendUserSyncEntry(['phoneNumber' => 'tx_office']),
        ]))->applyContract(
            ['uid' => 7, 'telephone' => '1', 'fax' => '2', 'tx_mobile' => '3', 'tx_pager' => '4', 'tx_empty' => '', 'tx_zero' => '0', 'tx_office' => '5'],
            $contract,
            5,
        );

        $phoneNumbers = [];
        foreach ($contract->getPhoneNumbers() as $phoneNumber) {
            $phoneNumbers[] = [$phoneNumber->getImportIdentifier(), $phoneNumber->getType(), $phoneNumber->getPhoneNumber()];
        }
        $this->assertSame(
            [
                ['telephone:fe_users:7', 'business', '1'],
                ['fax:fe_users:7', 'private', '2'],
                ['tx_mobile:fe_users:7', 'mobile', '3'],
                ['tx_pager:fe_users:7', '', '4'],
                ['tx_office:fe_users:7', 'business', '5'],
            ],
            $phoneNumbers,
        );
    }

    #[Test]
    public function aFurtherEmailAddressIsIdentifiedByItsColumn(): void
    {
        $contract = new Contract();

        $this->mapper(new FrontendUserSyncSettings(emailAddresses: [
            new FrontendUserSyncEntry(['email' => 'email']),
            new FrontendUserSyncEntry(['email' => 'tx_private_email']),
        ]))->applyContract(['uid' => 7, 'email' => 'a@example.org', 'tx_private_email' => 'b@example.org'], $contract, 5);

        $emails = [];
        foreach ($contract->getEmailAddresses() as $email) {
            $emails[] = [$email->getImportIdentifier(), $email->getEmail()];
        }
        $this->assertSame(
            [['fe_users:7', 'a@example.org'], ['tx_private_email:fe_users:7', 'b@example.org']],
            $emails,
        );
    }

    /**
     * The imported contract exists for as long as one mapped source of it is
     * set - a contract property as much as a contact column. An empty value is
     * what PHP's empty() calls empty, `'0'` included, as it was before the map.
     */
    #[Test]
    public function anyMappedContractSourceIsContractData(): void
    {
        $subject = $this->mapper(new FrontendUserSyncSettings(
            contract: ['position' => 'tx_position'],
            phoneNumbers: [new FrontendUserSyncEntry(['phoneNumber' => 'telephone'])],
        ));

        $this->assertTrue($subject->hasContractData(['uid' => 7, 'tx_position' => 'Professor', 'telephone' => '']));
        $this->assertTrue($subject->hasContractData(['uid' => 7, 'tx_position' => '', 'telephone' => '1']));
        $this->assertFalse($subject->hasContractData(['uid' => 7, 'tx_position' => '0', 'telephone' => '']));
        $this->assertFalse($subject->hasContractData(['uid' => 7, 'tx_position' => '', 'telephone' => '0']));
        $this->assertFalse($subject->hasContractData(['uid' => 7, 'email' => 'unmapped@example.org']));
    }

    /**
     * A map without any contract source leaves the contract alone - it must not
     * read as "every source is empty", which removes the imported contract.
     */
    #[Test]
    public function onlyAMapWithAContractSourceSynchronisesTheContract(): void
    {
        $this->assertFalse($this->mapper(new FrontendUserSyncSettings(profile: ['lastName' => 'last_name']))->mapsContract());
        $this->assertTrue($this->mapper(new FrontendUserSyncSettings(contract: ['room' => 'tx_room']))->mapsContract());
        $this->assertTrue($this->mapper(new FrontendUserSyncSettings(
            physicalAddresses: [new FrontendUserSyncEntry(['city' => 'city'])],
        ))->mapsContract());
        $this->assertTrue($this->mapper(new FrontendUserSyncSettings(
            phoneNumbers: [new FrontendUserSyncEntry(['phoneNumber' => 'telephone'])],
        ))->mapsContract());
        $this->assertTrue($this->mapper(new FrontendUserSyncSettings(
            emailAddresses: [new FrontendUserSyncEntry(['email' => 'email'])],
        ))->mapsContract());
    }

    /**
     * A misspelled column reads as empty and would remove the imported records;
     * the check the default factory runs names it instead - and the uid, which
     * every import identifier is made of.
     */
    #[Test]
    public function aColumnTheDataLacksIsNamed(): void
    {
        $subject = $this->mapper(new FrontendUserSyncSettings(
            profile: ['lastName' => 'lastname'],
            contract: ['position' => 'tx_positon'],
            physicalAddresses: [new FrontendUserSyncEntry(['street' => 'adress', 'city' => 'city'])],
            emailAddresses: [new FrontendUserSyncEntry(['email' => 'e_mail'])],
            phoneNumbers: [new FrontendUserSyncEntry(['phoneNumber' => 'telphone'])],
        ));
        $complete = ['uid' => 7, 'lastname' => '', 'tx_positon' => '', 'adress' => '', 'city' => '', 'e_mail' => '', 'telphone' => ''];

        $subject->assertColumnsExist($complete);
        try {
            $subject->assertColumnsExist(['city' => 'Town', 'last_name' => 'Doe', 'telephone' => '1']);
            $this->fail('Missing columns were accepted.');
        } catch (\UnexpectedValueException $exception) {
            $this->assertSame(1790142326, $exception->getCode());
            $this->assertStringEndsWith(
                'does not have: uid, lastname, tx_positon, adress, e_mail, telphone.',
                $exception->getMessage(),
            );
        }
    }

    #[Test]
    public function aContractIsNotWrittenWithoutTheUidOfItsFrontendUser(): void
    {
        $contract = new Contract();
        $contract->setPosition('Editor');

        try {
            $this->mapper(new FrontendUserSyncSettings(
                contract: ['position' => 'tx_position'],
                emailAddresses: [new FrontendUserSyncEntry(['email' => 'email'])],
            ))->applyContract(['tx_position' => 'Professor', 'email' => 'a@example.org'], $contract, 5);
            $this->fail('A contract was written without a uid.');
        } catch (\InvalidArgumentException $exception) {
            $this->assertSame(1790142327, $exception->getCode());
        }
        $this->assertSame('Editor', $contract->getPosition());
    }

    #[Test]
    public function anInvalidMapIsRefusedBeforeAnythingIsWritten(): void
    {
        $profile = new Profile();
        $profile->setLastName('Editor');
        $subject = $this->mapper(new FrontendUserSyncSettings(
            profile: ['lastName' => 'last_name'],
            problems: ['`frontendUserSync.profile.nickname` is not supported.'],
        ));

        try {
            $subject->applyProfile(['uid' => 7, 'last_name' => 'Synchronised'], $profile);
            $this->fail('An invalid map was applied.');
        } catch (\UnexpectedValueException $exception) {
            $this->assertSame(1790142324, $exception->getCode());
            $this->assertStringContainsString('frontendUserSync.profile.nickname', $exception->getMessage());
        }
        $this->assertSame('Editor', $profile->getLastName());
    }

    private function mapper(FrontendUserSyncSettings $frontendUserSync): FrontendUserProfileMapper
    {
        $extensionConfiguration = $this->createMock(ExtensionConfiguration::class);
        $extensionConfiguration->method('get')->willReturnCallback(
            static fn(string $extension, string $path): string => match ($path) {
                'profile/feuser/telephoneNumberType' => 'business',
                'profile/feuser/faxNumberType' => 'private',
                default => throw new ExtensionConfigurationPathDoesNotExistException(),
            },
        );
        $phoneNumberTypes = $this->createMock(PhoneNumberTypes::class);
        $phoneNumberTypes->method('getAll')->willReturn(['private' => 'Private', 'business' => 'Business', 'mobile' => 'Mobile']);

        return new FrontendUserProfileMapper(
            new AcademicPersonsSettings(frontendUserSync: $frontendUserSync),
            $this->createMock(AddressRepository::class),
            $this->createMock(EmailRepository::class),
            $this->createMock(PhoneNumberRepository::class),
            $this->createMock(PersistenceManagerInterface::class),
            new FrontendUserPhoneNumberTypeResolver($extensionConfiguration, $phoneNumberTypes),
        );
    }
}
