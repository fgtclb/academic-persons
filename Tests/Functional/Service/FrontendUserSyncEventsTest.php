<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Service;

use FGTCLB\AcademicPersons\Domain\Model\Dto\ProfileCreateCommandDto;
use FGTCLB\AcademicPersons\Domain\Model\Dto\ProfileUpdateCommandDto;
use FGTCLB\AcademicPersons\Service\ProfileCreateCommandService;
use FGTCLB\AcademicPersons\Service\ProfileUpdateCommandService;
use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TESTS\TestFrontendUserSyncEvents\EventListener\RecordSynchronisation;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * The events of the synchronisation, with the listeners of
 * `test_frontend_user_sync_events`: a stand-in for a directory service adds
 * `ldap.room`, `ldap.gender` and `ldap.status` and skips the user `skipped`,
 * who has left. A listener of the mapped profile maps the gender and rewrites
 * the last name, and the user `declined` gets a factory that creates no
 * profile. The map of the fixture reads the contract room from `ldap.room`.
 */
final class FrontendUserSyncEventsTest extends AbstractAcademicPersonsTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    protected function setUp(): void
    {
        $this->addCoreExtension('typo3/cms-fluid-styled-content');
        $this->addTestExtension('tests/test-frontend-user-sync-events');
        ArrayUtility::mergeRecursiveWithOverrule(
            $this->configurationToUseInTestInstance,
            [
                'EXTENSIONS' => [
                    'academic_persons' => [
                        'profile' => [
                            'autoCreateProfiles' => 1,
                            'createProfileForUserGroups' => '',
                        ],
                    ],
                ],
            ]
        );
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/FrontendUserSyncMapping/site-structure.csv');
        $this->setUpFrontendRootPage(
            pageId: 1,
            typoScriptFiles: [
                'constants' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript',
                    'EXT:academic_persons/Configuration/TypoScript/Default/constants.typoscript',
                ],
                'setup' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript',
                    'EXT:academic_persons/Configuration/TypoScript/Default/setup.typoscript',
                    'EXT:academic_persons/Tests/Functional/Service/ProfileCreateCommandService/Fixtures/TypoScript/Setup/setup.typoscript',
                ],
            ],
        );
        $this->writeSiteConfiguration(
            identifier: 'people',
            site: $this->buildSiteConfiguration(rootPageId: 1, base: 'https://people.acme.com/'),
            languages: [$this->buildDefaultLanguageConfiguration('EN', '/')],
        );
        RecordSynchronisation::$events = [];
    }

    /**
     * The skipped and the declined user come first, so the profile of the
     * third one shows that the run went on. The skipped user stops at the data
     * event, the declined one at its factory: nothing is mapped, saved or
     * announced for either. The mapped profile already carries its frontend
     * user.
     */
    #[Test]
    public function createProfilesCreatesNothingForASkippedOrDeclinedUserAndGoesOn(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/FrontendUserSyncEvents/frontend-users-without-profile.csv');

        GeneralUtility::makeInstance(ProfileCreateCommandService::class)
            ->execute(new ProfileCreateCommandDto(includePids: [200], excludePids: []));

        $profiles = $this->rows('tx_academicpersons_domain_model_profile', ['uid', 'import_identifier', 'first_name']);
        $this->assertSame(
            [['import_identifier' => 'fe_users:72', 'first_name' => 'Lara']],
            array_map(static fn(array $profile): array => array_diff_key($profile, ['uid' => true]), $profiles),
        );
        $this->assertSame(
            [
                'data create skipped profile none skipped',
                'data create declined profile none',
                'data create listed profile none',
                'mapped create listed profile new users 1',
                sprintf('updated creation profile %d fe_users:72', $profiles[0]['uid']),
            ],
            RecordSynchronisation::$events,
        );
    }

    /**
     * A value a listener adds is read by the map like a column, and the
     * listener of the mapped profile sees it as well. That listener also
     * rewrites the mapped last name, which only sticks after the mapping.
     */
    #[Test]
    public function createProfilesWritesTheValuesTheListenersAdd(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/FrontendUserSyncEvents/frontend-users-without-profile.csv');

        GeneralUtility::makeInstance(ProfileCreateCommandService::class)
            ->execute(new ProfileCreateCommandDto(includePids: [200], excludePids: []));

        $this->assertSame(
            [['import_identifier' => 'fe_users:72', 'last_name' => 'Listed', 'gender' => 'ms']],
            $this->rows('tx_academicpersons_domain_model_profile', ['import_identifier', 'last_name', 'gender']),
        );
        $this->assertSame(
            [['import_identifier' => 'fe_users:72', 'room' => 'A 2.14']],
            $this->rows('tx_academicpersons_domain_model_contract', ['import_identifier', 'room']),
        );
    }

    /**
     * The skipped profile keeps its first name and its room although the
     * frontend user has other values, and it is not announced. The data event
     * is dispatched once per profile, so the user with two profiles is seen
     * twice, and the skip of profile 83 leaves profile 82 of the same user to
     * be updated and announced.
     */
    #[Test]
    public function updateProfilesLeavesASkippedUserAloneAndUpdatesTheOthers(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/FrontendUserSyncEvents/frontend-users-with-profile.csv');

        GeneralUtility::makeInstance(ProfileUpdateCommandService::class)
            ->execute(new ProfileUpdateCommandDto(includePids: [200], excludePids: []));

        $this->assertSame(
            [
                ['uid' => 80, 'first_name' => 'Sam', 'last_name' => 'Skipped', 'gender' => ''],
                ['uid' => 81, 'first_name' => 'Lara', 'last_name' => 'Listed', 'gender' => 'ms'],
                ['uid' => 82, 'first_name' => 'Tom', 'last_name' => 'Twice', 'gender' => ''],
                ['uid' => 83, 'first_name' => 'Thomas', 'last_name' => 'Twice', 'gender' => ''],
            ],
            $this->rows('tx_academicpersons_domain_model_profile', ['uid', 'first_name', 'last_name', 'gender']),
        );
        $this->assertSame(
            [
                ['uid' => 80, 'room' => 'C 3.01', 'deleted' => 0],
                ['uid' => 81, 'room' => 'A 2.14', 'deleted' => 0],
                // The e-mail address makes a contract for the synchronised profile of the user the directory does not know.
                ['uid' => 82, 'room' => '', 'deleted' => 0],
            ],
            $this->rows('tx_academicpersons_domain_model_contract', ['uid', 'room', 'deleted']),
        );
        $this->assertSame(
            [
                'data update skipped profile 80 skipped',
                'data update listed profile 81',
                'mapped update listed profile 81 users 1',
                'updated synchronization profile 81 fe_users:81',
                'data update twice profile 82',
                'mapped update twice profile 82 users 1',
                'data update twice profile 83 skipped',
                'updated synchronization profile 82 fe_users:82',
            ],
            RecordSynchronisation::$events,
        );
    }

    /**
     * Every row of the table, deleted and hidden ones included.
     *
     * @param non-empty-string $table
     * @param non-empty-list<non-empty-string> $fields
     * @return list<array<string, mixed>>
     */
    private function rows(string $table, array $fields): array
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll();
        return $queryBuilder
            ->select(...$fields)
            ->from($table)
            ->orderBy('uid')
            ->executeQuery()
            ->fetchAllAssociative();
    }
}
