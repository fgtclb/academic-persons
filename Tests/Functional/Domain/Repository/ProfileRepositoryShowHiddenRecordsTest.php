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
}
