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
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * The synchronisation with a `frontendUserSync` map of a site package:
 * `test_frontend_user_sync` leaves the website to the editors, maps the
 * contract position, and adds a second address, a second e-mail address and a
 * mobile number from `fe_users` columns of its own. The shipped map is covered by the
 * `UsingDefaultProfileFactoryOnlyTest` classes of both commands, which run
 * without this fixture.
 */
final class FrontendUserSyncMappingTest extends AbstractAcademicPersonsTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    protected function setUp(): void
    {
        $this->addCoreExtension('typo3/cms-fluid-styled-content');
        $this->addTestExtension('tests/test-frontend-user-sync');
        ArrayUtility::mergeRecursiveWithOverrule(
            $this->configurationToUseInTestInstance,
            [
                'EXTENSIONS' => [
                    'academic_persons' => [
                        'profile' => [
                            'autoCreateProfiles' => 1,
                            'createProfileForUserGroups' => '',
                            'feuser' => [
                                'faxNumberType' => 'business',
                                'telephoneNumberType' => 'business',
                            ],
                        ],
                        'types' => [
                            'phoneNumberTypes' => 'private=Private,business=Business,mobile=Mobile',
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
    }

    #[Test]
    public function createProfilesImportsTheMappedPositionAndEveryListedContactRecord(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/FrontendUserSyncMapping/frontend-user-without-profile.csv');

        GeneralUtility::makeInstance(ProfileCreateCommandService::class)
            ->execute(new ProfileCreateCommandDto(includePids: [200], excludePids: []));

        $profile = $this->getConnectionPool()
            ->getConnectionForTable('tx_academicpersons_domain_model_profile')
            ->select(
                ['uid', 'first_name', 'last_name', 'website'],
                'tx_academicpersons_domain_model_profile',
                ['import_identifier' => 'fe_users:50'],
            )
            ->fetchAssociative();
        $this->assertIsArray($profile);
        // The website is mapped to '' by the fixture: not synchronised, so a new profile has none.
        $this->assertSame(
            ['first_name' => 'Nina', 'last_name' => 'New', 'website' => ''],
            array_diff_key($profile, ['uid' => true]),
        );
        $contract = $this->getConnectionPool()
            ->getConnectionForTable('tx_academicpersons_domain_model_contract')
            ->select(
                ['uid', 'position', 'import_identifier'],
                'tx_academicpersons_domain_model_contract',
                ['profile' => (int)$profile['uid']],
            )
            ->fetchAllAssociative();
        $this->assertCount(1, $contract);
        $this->assertSame('Professor', $contract[0]['position']);
        $this->assertSame('fe_users:50', $contract[0]['import_identifier']);
        $contractUid = (int)$contract[0]['uid'];

        $this->assertSame(
            [
                ['email' => 'nina@example.org', 'import_identifier' => 'fe_users:50'],
                ['email' => 'nina@private.example', 'import_identifier' => 'tx_test_email:fe_users:50'],
            ],
            $this->recordsOfContract('tx_academicpersons_domain_model_email', ['email', 'import_identifier'], $contractUid),
        );
        $this->assertSame(
            [
                ['type' => 'business', 'phone_number' => '+49 30 112', 'import_identifier' => 'fax:fe_users:50'],
                ['type' => 'business', 'phone_number' => '+49 30 111', 'import_identifier' => 'telephone:fe_users:50'],
                ['type' => 'mobile', 'phone_number' => '+49 170 113', 'import_identifier' => 'tx_test_mobile:fe_users:50'],
            ],
            $this->recordsOfContract(
                'tx_academicpersons_domain_model_phone_number',
                ['type', 'phone_number', 'import_identifier'],
                $contractUid,
            ),
        );
        $this->assertSame(
            [
                ['street' => 'Street 5', 'city' => 'Town', 'import_identifier' => 'fe_users:50'],
                ['street' => 'Campus 1', 'city' => 'Campus Town', 'import_identifier' => 'tx_test_office_street:fe_users:50'],
            ],
            $this->recordsOfContract(
                'tx_academicpersons_domain_model_address',
                ['street', 'city', 'import_identifier'],
                $contractUid,
            ),
        );
    }

    #[Test]
    public function updateProfilesLeavesAPropertyMappedToNothingAsTheEditorSetIt(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/FrontendUserSyncMapping/frontend-users-with-profile.csv');

        GeneralUtility::makeInstance(ProfileUpdateCommandService::class)
            ->execute(new ProfileUpdateCommandDto(includePids: [200], excludePids: []));

        $profile = $this->getConnectionPool()
            ->getConnectionForTable('tx_academicpersons_domain_model_profile')
            ->select(['website'], 'tx_academicpersons_domain_model_profile', ['uid' => 60])
            ->fetchAssociative();
        $this->assertSame(['website' => 'https://editor.example/'], $profile);
        $contract = $this->getConnectionPool()
            ->getConnectionForTable('tx_academicpersons_domain_model_contract')
            ->select(['position'], 'tx_academicpersons_domain_model_contract', ['uid' => 60])
            ->fetchAssociative();
        $this->assertSame(['position' => 'Dean'], $contract);
    }

    /**
     * Frontend user 60 has no office address, second e-mail address or mobile
     * number any more. The imported records go - soft deleted, so the history
     * can restore them - while the address and the mobile number an editor added
     * without an import identifier, and the records whose source is still set,
     * stay.
     */
    #[Test]
    public function updateProfilesRemovesTheImportedRecordsOfAnEmptiedSourceAndKeepsTheEditorsOnes(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/FrontendUserSyncMapping/frontend-users-with-profile.csv');

        GeneralUtility::makeInstance(ProfileUpdateCommandService::class)
            ->execute(new ProfileUpdateCommandDto(includePids: [200], excludePids: []));

        $this->assertSame(
            [
                ['uid' => 60, 'street' => 'Campus 60', 'deleted' => 1],
                ['uid' => 61, 'street' => 'Home 1', 'deleted' => 0],
            ],
            $this->recordsOfContract('tx_academicpersons_domain_model_address', ['uid', 'street', 'deleted'], 60, 'uid'),
        );

        $this->assertSame(
            [
                ['uid' => 60, 'email' => 'emma@example.org', 'deleted' => 0],
                ['uid' => 61, 'email' => 'emma@private.example', 'deleted' => 1],
            ],
            $this->recordsOfContract('tx_academicpersons_domain_model_email', ['uid', 'email', 'deleted'], 60, 'uid'),
        );
        $this->assertSame(
            [
                ['uid' => 60, 'phone_number' => '+49 30 601', 'deleted' => 0],
                ['uid' => 61, 'phone_number' => '+49 170 603', 'deleted' => 1],
                ['uid' => 62, 'phone_number' => '+49 170 604', 'deleted' => 0],
            ],
            $this->recordsOfContract(
                'tx_academicpersons_domain_model_phone_number',
                ['uid', 'phone_number', 'deleted'],
                60,
                'uid',
            ),
        );
    }

    /**
     * Frontend user 61 carries every mapped column and its contract has no record
     * yet. The first run imports them, the second one finds each by its import
     * identifier - asserted over every row of the contract, deleted ones
     * included, because a duplicate is invisible to an assertion per uid.
     */
    #[Test]
    public function updateProfilesTwiceImportsEveryRecordOnce(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/FrontendUserSyncMapping/frontend-users-with-profile.csv');
        $service = GeneralUtility::makeInstance(ProfileUpdateCommandService::class);

        $service->execute(new ProfileUpdateCommandDto(includePids: [200], excludePids: []));
        $service->execute(new ProfileUpdateCommandDto(includePids: [200], excludePids: []));

        $this->assertSame(
            [
                ['import_identifier' => 'fe_users:61', 'deleted' => 0],
                ['import_identifier' => 'tx_test_email:fe_users:61', 'deleted' => 0],
            ],
            $this->recordsOfContract('tx_academicpersons_domain_model_email', ['import_identifier', 'deleted'], 61),
        );
        $this->assertSame(
            [
                ['import_identifier' => 'fax:fe_users:61', 'deleted' => 0],
                ['import_identifier' => 'telephone:fe_users:61', 'deleted' => 0],
                ['import_identifier' => 'tx_test_mobile:fe_users:61', 'deleted' => 0],
            ],
            $this->recordsOfContract(
                'tx_academicpersons_domain_model_phone_number',
                ['import_identifier', 'deleted'],
                61,
            ),
        );
        $this->assertSame(
            [
                ['import_identifier' => 'fe_users:61', 'deleted' => 0],
                ['import_identifier' => 'tx_test_office_street:fe_users:61', 'deleted' => 0],
            ],
            $this->recordsOfContract('tx_academicpersons_domain_model_address', ['import_identifier', 'deleted'], 61),
        );
    }

    /**
     * Every row of the contract, deleted and hidden ones included.
     *
     * @param non-empty-string $table
     * @param non-empty-list<non-empty-string> $fields
     * @return list<array<string, mixed>>
     */
    private function recordsOfContract(
        string $table,
        array $fields,
        int $contractUid,
        string $orderBy = 'import_identifier',
    ): array {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll();
        return $queryBuilder
            ->select(...$fields)
            ->from($table)
            ->where($queryBuilder->expr()->eq('contract', $queryBuilder->createNamedParameter($contractUid, Connection::PARAM_INT)))
            ->orderBy($orderBy)
            ->addOrderBy('uid')
            ->executeQuery()
            ->fetchAllAssociative();
    }
}
