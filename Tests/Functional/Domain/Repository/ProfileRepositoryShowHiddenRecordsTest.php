<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Domain\Repository;

use FGTCLB\AcademicPersons\Domain\Model\Dto\ProfileDemand;
use FGTCLB\AcademicPersons\Domain\Model\Profile;
use FGTCLB\AcademicPersons\Domain\Repository\ProfileRepository;
use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;

final class ProfileRepositoryShowHiddenRecordsTest extends AbstractAcademicPersonsTestCase
{
    private function getProfileRepository(): ProfileRepository
    {
        return $this->get(ProfileRepository::class);
    }

    /**
     * @param QueryResultInterface<int, Profile> $result
     * @return int[]
     */
    private function resultUids(QueryResultInterface $result): array
    {
        $uids = [];
        foreach ($result as $profile) {
            $uids[] = (int)$profile->getUid();
        }
        sort($uids);
        return $uids;
    }

    #[Test]
    public function findByUidsExcludesHiddenRecordsByDefault(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ShowHiddenRecords/profiles.csv');
        $result = $this->getProfileRepository()->findByUids([1, 2, 3, 4]);
        $this->assertSame([1, 3], $this->resultUids($result));
    }

    #[Test]
    public function findByUidsIncludesHiddenRecordsWhenRequested(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ShowHiddenRecords/profiles.csv');
        $result = $this->getProfileRepository()->findByUids([1, 2, 3, 4], true);
        $this->assertSame([1, 2, 3, 4], $this->resultUids($result));
    }

    /**
     * The result orders by `uid` ascending since ACE-491, whatever order the uids were
     * requested in - `in()` does not preserve the argument order, and before the explicit
     * ordering the result order belonged to the DBMS. The order of the editor's selection
     * is deliberately not reproduced; that would be a behaviour change beyond making the
     * list reproducible.
     */
    #[Test]
    public function findByUidsReturnsUidOrderRegardlessOfTheRequestedOrder(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ShowHiddenRecords/profiles.csv');

        $uids = [];
        foreach ($this->getProfileRepository()->findByUids([3, 1]) as $profile) {
            $uids[] = (int)$profile->getUid();
        }

        $this->assertSame([1, 3], $uids);
    }

    #[Test]
    public function findByDemandExcludesHiddenRecordsByDefault(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ShowHiddenRecords/profiles.csv');
        $result = $this->getProfileRepository()->findByDemand(new ProfileDemand());
        $this->assertSame([1, 3], $this->resultUids($result));
    }

    #[Test]
    public function findByDemandIncludesHiddenRecordsWhenRequested(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ShowHiddenRecords/profiles.csv');
        $demand = (new ProfileDemand())->setShowHiddenRecords(true);
        $result = $this->getProfileRepository()->findByDemand($demand);
        $this->assertSame([1, 2, 3, 4], $this->resultUids($result));
    }

    /**
     * The second fixture adds three profiles sharing one last name, so the default
     * demanded ordering (`lastName` ascending) leaves their relative order undefined -
     * and the DBMS resolved it, arbitrarily on PostgreSQL (ACE-491). The `uid` tiebreaker
     * appended to the demanded ordering settles it, at the end of an otherwise
     * name-ordered list. The tied records' first names are deliberately reversed against
     * uid order, so a `first_name` accident cannot produce the expected list.
     */
    #[Test]
    public function profilesEqualInTheDemandedOrderingFallBackToUidOrder(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ShowHiddenRecords/profiles.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ShowHiddenRecords/profilesWithEqualLastNames.csv');

        $uids = [];
        foreach ($this->getProfileRepository()->findByDemand(new ProfileDemand()) as $profile) {
            $uids[] = (int)$profile->getUid();
        }

        $this->assertSame([1, 3, 10, 11, 12], $uids);
    }

    #[Test]
    public function findByUidIncludingHiddenReturnsHiddenProfile(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ShowHiddenRecords/profiles.csv');
        $profile = $this->getProfileRepository()->findByUidIncludingHidden(2);
        $this->assertInstanceOf(Profile::class, $profile);
        $this->assertSame(2, (int)$profile->getUid());
    }

    #[Test]
    public function findByUidIncludingHiddenReturnsVisibleProfile(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ShowHiddenRecords/profiles.csv');
        $profile = $this->getProfileRepository()->findByUidIncludingHidden(1);
        $this->assertInstanceOf(Profile::class, $profile);
        $this->assertSame(1, (int)$profile->getUid());
    }

    #[Test]
    public function findByUidIncludingHiddenDoesNotReturnDeletedProfile(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ShowHiddenRecords/profiles.csv');
        $profile = $this->getProfileRepository()->findByUidIncludingHidden(5);
        $this->assertNull($profile);
    }
}
