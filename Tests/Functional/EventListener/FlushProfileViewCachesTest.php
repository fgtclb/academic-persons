<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\EventListener;

use FGTCLB\AcademicPersons\Domain\Model\Contract;
use FGTCLB\AcademicPersons\Domain\Model\Location;
use FGTCLB\AcademicPersons\Domain\Model\Profile;
use FGTCLB\AcademicPersons\Domain\Repository\AddressRepository;
use FGTCLB\AcademicPersons\Domain\Repository\ContractRepository;
use FGTCLB\AcademicPersons\Domain\Repository\EmailRepository;
use FGTCLB\AcademicPersons\Domain\Repository\LocationRepository;
use FGTCLB\AcademicPersons\Domain\Repository\PhoneNumberRepository;
use FGTCLB\AcademicPersons\Domain\Repository\ProfileInformationRepository;
use FGTCLB\AcademicPersons\Domain\Repository\ProfileRepository;
use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * A record of a profile written through Extbase leaves the cached list and the cached
 * detail view of that profile, the way a backend save does through the DataHandler
 * hooks. The profile editor of `academic_persons_edit` writes through Extbase, the
 * editor test there covers the plugin end to end. These tests drive the persistence
 * directly, one record type at a time.
 *
 * uid 1  profile with one contract, one address, email address and phone number on it,
 *        and one profile information
 * uid 2  profile without records, whose detail view must stay cached
 */
final class FlushProfileViewCachesTest extends AbstractAcademicPersonsTestCase
{
    protected function setUp(): void
    {
        ArrayUtility::mergeRecursiveWithOverrule(
            $this->configurationToUseInTestInstance,
            ['SYS' => ['caching' => ['cacheConfigurations' => ['pages' => ['backend' => Typo3DatabaseBackend::class]]]]],
        );
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/FlushProfileViewCaches.csv');
    }

    private function getPagesCache(): FrontendInterface
    {
        $cache = $this->get(CacheManager::class)->getCache('pages');
        $cache->set('list', 'list', ['profile_list_view']);
        $cache->set('detail-1', 'detail', ['profile_detail_view_1']);
        $cache->set('detail-2', 'detail', ['profile_detail_view_2']);
        return $cache;
    }

    private function persist(): void
    {
        $this->get(PersistenceManagerInterface::class)->persistAll();
    }

    private function assertProfileOneWasFlushed(FrontendInterface $cache): void
    {
        $this->assertFalse($cache->has('list'), 'The cached list was not flushed.');
        $this->assertFalse($cache->has('detail-1'), 'The cached detail view of the profile was not flushed.');
        $this->assertTrue($cache->has('detail-2'), 'The cached detail view of another profile was flushed.');
    }

    #[Test]
    public function aProfileUpdatedThroughExtbaseLeavesItsCachedViews(): void
    {
        $cache = $this->getPagesCache();
        $repository = $this->get(ProfileRepository::class);
        $profile = $repository->findByUid(1);
        $this->assertInstanceOf(Profile::class, $profile);

        $profile->setWebsite('https://example.org');
        $repository->update($profile);
        $this->persist();

        $this->assertProfileOneWasFlushed($cache);
    }

    #[Test]
    public function aContractAddedThroughExtbaseLeavesTheCachedViewsOfItsProfile(): void
    {
        $cache = $this->getPagesCache();
        $profile = $this->get(ProfileRepository::class)->findByUid(1);
        $this->assertInstanceOf(Profile::class, $profile);

        $contract = new Contract();
        $contract->setPid(100);
        $contract->setProfile($profile);
        $contract->setPosition('Dean');
        $this->get(ContractRepository::class)->add($contract);
        $this->persist();

        $this->assertProfileOneWasFlushed($cache);
    }

    /**
     * @return \Generator<string, array{class-string<Repository<covariant AbstractEntity>>, string, string}>
     */
    public static function recordsOfAProfile(): \Generator
    {
        yield 'contract' => [ContractRepository::class, 'setPosition', 'Dean'];
        yield 'address of a contract' => [AddressRepository::class, 'setStreet', 'Side Street'];
        yield 'email address of a contract' => [EmailRepository::class, 'setEmail', 'max@example.org'];
        yield 'phone number of a contract' => [PhoneNumberRepository::class, 'setPhoneNumber', '+49 30 5678'];
        yield 'profile information' => [ProfileInformationRepository::class, 'setTitle', 'Curriculum vitae'];
    }

    /**
     * @param class-string<Repository<covariant AbstractEntity>> $repositoryClass
     */
    #[DataProvider('recordsOfAProfile')]
    #[Test]
    public function aRecordOfAProfileUpdatedThroughExtbaseLeavesTheCachedViewsOfItsProfile(
        string $repositoryClass,
        string $setter,
        string $value,
    ): void {
        $cache = $this->getPagesCache();
        $repository = $this->get($repositoryClass);
        $record = $repository->findByUid(1);
        $this->assertInstanceOf(AbstractEntity::class, $record);

        $record->{$setter}($value);
        $repository->update($record);
        $this->persist();

        $this->assertProfileOneWasFlushed($cache);
    }

    /**
     * @param class-string<Repository<covariant AbstractEntity>> $repositoryClass
     */
    #[DataProvider('recordsOfAProfile')]
    #[Test]
    public function aRecordOfAProfileRemovedThroughExtbaseLeavesTheCachedViewsOfItsProfile(
        string $repositoryClass,
        string $setter,
        string $value,
    ): void {
        $cache = $this->getPagesCache();
        $repository = $this->get($repositoryClass);
        $record = $repository->findByUid(1);
        $this->assertInstanceOf(AbstractEntity::class, $record);

        $repository->remove($record);
        $this->persist();

        $this->assertProfileOneWasFlushed($cache);
    }

    /**
     * Only the records of a profile flush its views. Every other Extbase write, of this
     * extension or of any other one, leaves the page cache alone.
     */
    #[Test]
    public function aRecordThatIsNoPartOfAProfileLeavesTheCachedViewsAlone(): void
    {
        $cache = $this->getPagesCache();
        $repository = $this->get(LocationRepository::class);
        $location = $repository->findByUid(1);
        $this->assertInstanceOf(Location::class, $location);

        $location->setTitle('North Campus');
        $repository->update($location);
        $this->persist();

        $this->assertTrue($cache->has('list'));
        $this->assertTrue($cache->has('detail-1'));
        $this->assertTrue($cache->has('detail-2'));
    }
}
