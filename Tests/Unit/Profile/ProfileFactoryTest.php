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
use FGTCLB\AcademicPersons\Profile\ProfileFactory;
use FGTCLB\AcademicPersons\Settings\AcademicPersonsSettings;
use FGTCLB\AcademicPersons\Settings\FrontendUserSyncEntry;
use FGTCLB\AcademicPersons\Settings\FrontendUserSyncSettings;
use FGTCLB\AcademicPersons\Types\PhoneNumberTypes;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * What the default factory decides itself: whether the imported contract is
 * created, kept or removed, and that it refuses a map reading a column the
 * frontend user record does not have. What it writes is the mapper's, see
 * FrontendUserProfileMapperTest; the database side is covered by the
 * functional tests of both commands.
 */
final class ProfileFactoryTest extends UnitTestCase
{
    private const FRONTEND_USER = ['uid' => 7, 'pid' => 5, 'last_name' => 'Doe', 'email' => ''];

    /**
     * A map that names no source of the contract - `frontendUserSync: ~`, or a
     * site package keeping only the names - must not read as "every source is
     * empty": that removed every imported contract, the records editors added
     * to it included.
     */
    #[Test]
    public function aMapWithoutContractSourcesLeavesTheImportedContractAlone(): void
    {
        $persistenceManager = $this->createMock(PersistenceManagerInterface::class);
        $persistenceManager->expects($this->never())->method('remove');
        $subject = $this->factory(new FrontendUserSyncSettings(profile: ['lastName' => 'last_name']), $persistenceManager);
        $profile = $this->profileWithImportedContract();

        $this->update($subject, self::FRONTEND_USER, $profile);
        $created = $this->create($subject, self::FRONTEND_USER);

        $this->assertSame('Doe', $profile->getLastName());
        $this->assertCount(1, $profile->getContracts());
        $this->assertCount(0, $created->getContracts());
    }

    #[Test]
    public function aMappedContractSourceThatIsEmptyRemovesTheImportedContract(): void
    {
        $profile = $this->profileWithImportedContract();
        $contract = $profile->getContracts()->toArray()[0];
        $persistenceManager = $this->createMock(PersistenceManagerInterface::class);
        $persistenceManager->expects($this->once())->method('remove')->with($contract);
        $subject = $this->factory(
            new FrontendUserSyncSettings(emailAddresses: [new FrontendUserSyncEntry(['email' => 'email'])]),
            $persistenceManager,
        );

        $this->update($subject, self::FRONTEND_USER, $profile);
    }

    #[Test]
    public function aColumnTheFrontendUserLacksIsRefusedBeforeAnythingIsWritten(): void
    {
        $persistenceManager = $this->createMock(PersistenceManagerInterface::class);
        $persistenceManager->expects($this->never())->method('remove');
        $subject = $this->factory(
            new FrontendUserSyncSettings(
                profile: ['lastName' => 'last_name'],
                emailAddresses: [new FrontendUserSyncEntry(['email' => 'e_mail'])],
            ),
            $persistenceManager,
        );
        $profile = $this->profileWithImportedContract();
        $profile->setLastName('Editor');

        try {
            $this->update($subject, self::FRONTEND_USER, $profile);
            $this->fail('A map reading a missing column was applied.');
        } catch (\UnexpectedValueException $exception) {
            $this->assertSame(1790142326, $exception->getCode());
            $this->assertStringContainsString('e_mail', $exception->getMessage());
        }
        $this->assertSame('Editor', $profile->getLastName());
    }

    private function profileWithImportedContract(): Profile
    {
        $contract = new Contract();
        $contract->setImportIdentifier('fe_users:7');
        $profile = new Profile();
        $profile->getContracts()->attach($contract);
        return $profile;
    }

    /**
     * @param array<string, int|string|null> $frontendUserData
     */
    private function update(ProfileFactory $subject, array $frontendUserData, Profile $profile): void
    {
        (new \ReflectionMethod($subject, 'updateProfileFromFrontendUser'))->invoke($subject, $frontendUserData, $profile);
    }

    /**
     * @param array<string, int|string|null> $frontendUserData
     */
    private function create(ProfileFactory $subject, array $frontendUserData): Profile
    {
        $profile = (new \ReflectionMethod($subject, 'createProfileFromFrontendUser'))->invoke($subject, $frontendUserData);
        $this->assertInstanceOf(Profile::class, $profile);
        return $profile;
    }

    private function factory(FrontendUserSyncSettings $frontendUserSync, PersistenceManagerInterface $persistenceManager): ProfileFactory
    {
        $subject = new ProfileFactory(new FrontendUserProfileMapper(
            new AcademicPersonsSettings(frontendUserSync: $frontendUserSync),
            $this->createMock(AddressRepository::class),
            $this->createMock(EmailRepository::class),
            $this->createMock(PhoneNumberRepository::class),
            $persistenceManager,
            new FrontendUserPhoneNumberTypeResolver(
                $this->createMock(ExtensionConfiguration::class),
                $this->createMock(PhoneNumberTypes::class),
            ),
        ));
        $subject->injectPersistenceManagerInterface($persistenceManager);
        return $subject;
    }
}
