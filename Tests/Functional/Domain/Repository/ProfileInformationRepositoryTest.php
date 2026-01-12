<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Domain\Repository;

use FGTCLB\AcademicPersons\Domain\Model\Profile;
use FGTCLB\AcademicPersons\Domain\Repository\ProfileInformationRepository;
use FGTCLB\AcademicPersons\Domain\Repository\ProfileRepository;
use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class ProfileInformationRepositoryTest extends AbstractAcademicPersonsTestCase
{
    #[Test]
    public function findByProfileAndTypeReturnsProfileInformationRespectingSortingFieldValues(): void
    {
        $assertFields = [
            'uid',
            'pid',
            'type',
            'sorting',
            'title',
            'year',
            'year_start',
            'year_end',
        ];
        $expected = [
            [
                'uid' => 1,
                'pid' => 20,
                'type' => 'type_1',
                'sorting' => 1,
                'title' => 'Type 1 - UID 1 Pos #1',
                'year' => 2020,
                'year_start' => null,
                'year_end' => null,
            ],
            [
                'uid' => 4,
                'pid' => 20,
                'type' => 'type_1',
                'sorting' => 2,
                'title' => 'Type 1 - UID 3 Pos #2',
                'year' => 2020,
                'year_start' => null,
                'year_end' => null,
            ],
            [
                'uid' => 3,
                'pid' => 20,
                'type' => 'type_1',
                'sorting' => 3,
                'title' => 'Type 1 - UID 2 Pos #3',
                'year' => 2020,
                'year_start' => null,
                'year_end' => null,
            ],
        ];
        $profileRepository = GeneralUtility::makeInstance(ProfileRepository::class);
        $profileInformationRepository = GeneralUtility::makeInstance(ProfileInformationRepository::class);
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ProfileInformationRepository/Import/profilesWithSortingFieldsNotMatchingUidOrder.csv');
        $profile = $profileRepository->findByUid(1);
        $this->assertInstanceOf(Profile::class, $profile);
        $this->assertSame($profile->getUid(), 1);
        $this->assertSame($profile->getPid(), 20);

        $profileInformationTypes = $profileInformationRepository->findByProfileAndType($profile, 'type_1');
        $this->assertCount(3, $profileInformationTypes);
        $records = [];
        $normalizedData = [];
        foreach ($profileInformationTypes as $profileInformation) {
            if ($profileInformation->getUid() === null) {
                continue;
            }
            $normalizedData[$profileInformation->getUid()] = [
                'uid' => $profileInformation->getUid(),
                'pid' => $profileInformation->getPid(),
                'sorting' => $profileInformation->getSorting(),
                'type' => $profileInformation->getType(),
                'title' => $profileInformation->getTitle(),
                'year' => $profileInformation->getYear(),
                'year_start' => $profileInformation->getYearStart(),
                'year_end' => $profileInformation->getYearEnd(),
            ];
        }
        $tableName = 'tx_academicpersons_domain_model_profile_information';
        $this->assertMatchingArray($tableName, $expected, $normalizedData, $assertFields);
    }
}
