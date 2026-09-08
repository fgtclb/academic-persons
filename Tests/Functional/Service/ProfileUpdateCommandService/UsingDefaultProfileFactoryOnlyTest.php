<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Service\ProfileUpdateCommandService;

use FGTCLB\AcademicPersons\Domain\Model\Dto\ProfileUpdateCommandDto;
use FGTCLB\AcademicPersons\Event\AfterProfileUpdateEvent;
use FGTCLB\AcademicPersons\Profile\ProfileFactory;
use FGTCLB\AcademicPersons\Service\Event\ModifyProfileCommandEnvironmentStateBuildContextForFrontendUserEvent;
use FGTCLB\AcademicPersons\Service\ProfileCreateCommandService;
use FGTCLB\AcademicPersons\Service\ProfileUpdateCommandService;
use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use FGTCLB\AcademicPersons\Types\PhoneNumberTypes;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use Symfony\Component\DependencyInjection\Container;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class UsingDefaultProfileFactoryOnlyTest extends AbstractAcademicPersonsTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
        'DE' => ['id' => 1, 'title' => 'Deutsch', 'locale' => 'de_DE.UTF8', 'iso' => 'de', 'hrefLang' => 'de-DE', 'direction' => ''],
    ];

    protected function setUp(): void
    {
        $this->addCoreExtension('typo3/cms-fluid-styled-content');
        ArrayUtility::mergeRecursiveWithOverrule(
            $this->configurationToUseInTestInstance,
            [
                'SYS' => [
                    'caching' => [
                        'cacheConfigurations' => [
                            // Set pages cache database backend, testing-framework sets this to NullBackend by default.
                            'pages' => [
                                'backend' => 'TYPO3\\CMS\\Core\\Cache\\Backend\\Typo3DatabaseBackend',
                            ],
                        ],
                    ],
                ],
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
                        'demand' => [
                            'allowedGroupByValues' => 'firstNameAlpha=LLL:EXT:academic_persons/Resources/Private/Language/locallang_be.xlf:flexform.el.groupBy.items.first_name,lastNameAlpha=LLL:EXT:academic_persons/Resources/Private/Language/locallang_be.xlf:flexform.el.groupBy.items.last_name',
                            'allowedSortByValues' => 'firstName=LLL:EXT:academic_persons/Resources/Private/Language/locallang_be.xlf:flexform.el.sortBy.items.first_name,lastName=LLL:EXT:academic_persons/Resources/Private/Language/locallang_be.xlf:flexform.el.sortBy.items.last_name',
                        ],
                        'types' => [
                            'emailAddressTypes' => 'private=Private,business=Business',
                            'phoneNumberTypes' => 'private=Private,business=Business,mobile=Mobile',
                            'physicalAddressTypes' => 'private=Private,business=Business',
                        ],
                    ],
                ],
            ]
        );
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSets/site-structure.csv');
        $this->setUpFrontendRootPageForTestCase(
            pageId: 1,
            identifier: 'site-one',
        );
        $this->setUpFrontendRootPageForTestCase(
            pageId: 1001,
            identifier: 'site-two',
        );
    }

    /**
     * @param int $pageId
     * @param non-empty-string $identifier
     * @param string[]|null $constants
     * @param string[]|null $setup
     */
    private function setUpFrontendRootPageForTestCase(
        int $pageId,
        string $identifier,
        ?array $constants = null,
        ?array $setup = null,
    ): void {
        $constants ??= [
            'EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript',
            'EXT:academic_persons/Configuration/TypoScript/Default/constants.typoscript',
            'EXT:academic_persons/Tests/Functional/Service/ProfileCreateCommandService/Fixtures/TypoScript/Constants/constants.typoscript',
        ];
        $setup ??= [
            'EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript',
            'EXT:academic_persons/Configuration/TypoScript/Default/setup.typoscript',
            'EXT:academic_persons/Tests/Functional/Service/ProfileCreateCommandService/Fixtures/TypoScript/Setup/setup.typoscript',
        ];
        $this->setUpFrontendRootPage(
            pageId: $pageId,
            typoScriptFiles: [
                'constants' => $constants,
                'setup' => $setup,
            ],
        );
        $this->writeSiteConfiguration(
            identifier: $identifier,
            site: $this->buildSiteConfiguration(
                rootPageId: $pageId,
                base: sprintf('https://%s.acme.com/', $identifier),
            ),
            languages: [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
            ],
        );
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @param string[] $fields
     * @return array<int, array<string, mixed>>
     */
    private function prepareRows(array $rows, array $fields): array
    {
        $return = [];
        foreach ($rows as $row) {
            $item = [];
            foreach ($fields as $field) {
                $item[$field] = $row[$field] ?? null;
            }
            $return[] = $item;
        }
        return $return;
    }

    #[Test]
    public function sharedInstanceRetrievesPersonsProfileFactoryAsDefaultProfileFactory(): void
    {
        $profileCreateCommandService = GeneralUtility::makeInstance(ProfileCreateCommandService::class);
        $defaultFactory = (new \ReflectionProperty($profileCreateCommandService, 'defaultFactory'))->getValue($profileCreateCommandService);
        $this->assertInstanceOf(ProfileFactory::class, $defaultFactory);
    }

    public static function getUsersWithProfileResultDataSets(): \Generator
    {
        yield '#1 return all frontenduser typed records from all pids' => [
            'includePids' => [],
            'excludePids' => [],
            'fields' => [
                'uid',
                'pid',
                'username',
                'first_name',
                'middle_name',
                'last_name',
                'www',
                'address',
                'zip',
                'city',
                'country',
                'email',
                'telephone',
                'fax',
            ],
            'expectedRows' => [
                0 => [
                    'uid' => 10,
                    'pid' => 100,
                    'username' => 'usera1',
                    'first_name' => 'Max1',
                    'middle_name' => 'Marvin1',
                    'last_name' => 'Müllermann1',
                    'www' => 'https://www.example1.com/',
                    'address' => 'Street 11',
                    'zip' => '73250',
                    'city' => 'Wernau1',
                    'country' => 'Germany1',
                    'email' => 'info1@email.org',
                    'telephone' => '+49175211223341',
                    'fax' => '+49175211223391',
                ],
                1 => [
                    'uid' => 12,
                    'pid' => 110,
                    'username' => 'userc1',
                    'first_name' => 'Max',
                    'middle_name' => 'Marvin',
                    'last_name' => 'Müllermann',
                    'www' => 'https://www.example.com/',
                    'address' => 'Street 1',
                    'zip' => '73249',
                    'city' => 'Wernau',
                    'country' => 'Germany',
                    'email' => 'info@email.org',
                    'telephone' => '+4917521122334',
                    'fax' => '+4917521122339',
                ],
                2 => [
                    'uid' => 14,
                    'pid' => 100,
                    'username' => 'admina1',
                    'first_name' => 'Max',
                    'middle_name' => 'Marvin',
                    'last_name' => 'Müllermann',
                    'www' => 'https://www.example.com/',
                    'address' => 'Street 1',
                    'zip' => '73249',
                    'city' => 'Wernau',
                    'country' => 'Germany',
                    'email' => 'info@email.org',
                    'telephone' => '+4917521122334',
                    'fax' => '+4917521122339',
                ],
                3 => [
                    'uid' => 16,
                    'pid' => 110,
                    'username' => 'adminc1',
                    'first_name' => 'Max',
                    'middle_name' => 'Marvin',
                    'last_name' => 'Müllermann',
                    'www' => 'https://www.example.com/',
                    'address' => 'Street 1',
                    'zip' => '73249',
                    'city' => 'Wernau',
                    'country' => 'Germany',
                    'email' => 'info@email.org',
                    'telephone' => '+4917521122334',
                    'fax' => '+4917521122339',
                ],
                4 => [
                    'uid' => 20,
                    'pid' => 1100,
                    'username' => 'usera2',
                    'first_name' => 'Max1',
                    'middle_name' => 'Marvin1',
                    'last_name' => 'Müllermann1',
                    'www' => 'https://www.example1.com/',
                    'address' => 'Street 11',
                    'zip' => '73250',
                    'city' => 'Wernau1',
                    'country' => 'Germany1',
                    'email' => 'info1@email.org',
                    'telephone' => '+49175211223341',
                    'fax' => '+49175211223391',
                ],
                5 => [
                    'uid' => 22,
                    'pid' => 1110,
                    'username' => 'userc2',
                    'first_name' => 'Max',
                    'middle_name' => 'Marvin',
                    'last_name' => 'Müllermann',
                    'www' => 'https://www.example.com/',
                    'address' => 'Street 1',
                    'zip' => '73249',
                    'city' => 'Wernau',
                    'country' => 'Germany',
                    'email' => 'info@email.org',
                    'telephone' => '+4917521122334',
                    'fax' => '+4917521122339',
                ],
                6 => [
                    'uid' => 24,
                    'pid' => 1100,
                    'username' => 'admina2',
                    'first_name' => 'Max',
                    'middle_name' => 'Marvin',
                    'last_name' => 'Müllermann',
                    'www' => 'https://www.example.com/',
                    'address' => 'Street 1',
                    'zip' => '73249',
                    'city' => 'Wernau',
                    'country' => 'Germany',
                    'email' => 'info@email.org',
                    'telephone' => '+4917521122334',
                    'fax' => '+4917521122339',
                ],
                7 => [
                    'uid' => 26,
                    'pid' => 1110,
                    'username' => 'adminc2',
                    'first_name' => 'Max',
                    'middle_name' => 'Marvin',
                    'last_name' => 'Müllermann',
                    'www' => 'https://www.example.com/',
                    'address' => 'Street 1',
                    'zip' => '73249',
                    'city' => 'Wernau',
                    'country' => 'Germany',
                    'email' => 'info@email.org',
                    'telephone' => '+4917521122334',
                    'fax' => '+4917521122339',
                ],
            ],
        ];
        yield '#2 return all frontenduser typed records only from includePids' => [
            'includePids' => [
                100,
                1100,
            ],
            'excludePids' => [],
            'fields' => [
                'uid',
                'pid',
                'username',
                'first_name',
                'middle_name',
                'last_name',
                'www',
                'address',
                'zip',
                'city',
                'country',
                'email',
                'telephone',
                'fax',
            ],
            'expectedRows' => [
                0 => [
                    'uid' => 10,
                    'pid' => 100,
                    'username' => 'usera1',
                    'first_name' => 'Max1',
                    'middle_name' => 'Marvin1',
                    'last_name' => 'Müllermann1',
                    'www' => 'https://www.example1.com/',
                    'address' => 'Street 11',
                    'zip' => '73250',
                    'city' => 'Wernau1',
                    'country' => 'Germany1',
                    'email' => 'info1@email.org',
                    'telephone' => '+49175211223341',
                    'fax' => '+49175211223391',
                ],
                1 => [
                    'uid' => 14,
                    'pid' => 100,
                    'username' => 'admina1',
                    'first_name' => 'Max',
                    'middle_name' => 'Marvin',
                    'last_name' => 'Müllermann',
                    'www' => 'https://www.example.com/',
                    'address' => 'Street 1',
                    'zip' => '73249',
                    'city' => 'Wernau',
                    'country' => 'Germany',
                    'email' => 'info@email.org',
                    'telephone' => '+4917521122334',
                    'fax' => '+4917521122339',
                ],
                2 => [
                    'uid' => 20,
                    'pid' => 1100,
                    'username' => 'usera2',
                    'first_name' => 'Max1',
                    'middle_name' => 'Marvin1',
                    'last_name' => 'Müllermann1',
                    'www' => 'https://www.example1.com/',
                    'address' => 'Street 11',
                    'zip' => '73250',
                    'city' => 'Wernau1',
                    'country' => 'Germany1',
                    'email' => 'info1@email.org',
                    'telephone' => '+49175211223341',
                    'fax' => '+49175211223391',
                ],
                4 => [
                    'uid' => 24,
                    'pid' => 1100,
                    'username' => 'admina2',
                    'first_name' => 'Max',
                    'middle_name' => 'Marvin',
                    'last_name' => 'Müllermann',
                    'www' => 'https://www.example.com/',
                    'address' => 'Street 1',
                    'zip' => '73249',
                    'city' => 'Wernau',
                    'country' => 'Germany',
                    'email' => 'info@email.org',
                    'telephone' => '+4917521122334',
                    'fax' => '+4917521122339',
                ],
            ],
        ];
        yield '#3 return all frontenduser typed records except from excludePids' => [
            'includePids' => [],
            'excludePids' => [
                110,
                1110,
            ],
            'fields' => [
                'uid',
                'pid',
                'username',
                'first_name',
                'middle_name',
                'last_name',
                'www',
                'address',
                'zip',
                'city',
                'country',
                'email',
                'telephone',
                'fax',
            ],
            'expectedRows' => [
                0 => [
                    'uid' => 10,
                    'pid' => 100,
                    'username' => 'usera1',
                    'first_name' => 'Max1',
                    'middle_name' => 'Marvin1',
                    'last_name' => 'Müllermann1',
                    'www' => 'https://www.example1.com/',
                    'address' => 'Street 11',
                    'zip' => '73250',
                    'city' => 'Wernau1',
                    'country' => 'Germany1',
                    'email' => 'info1@email.org',
                    'telephone' => '+49175211223341',
                    'fax' => '+49175211223391',
                ],
                1 => [
                    'uid' => 14,
                    'pid' => 100,
                    'username' => 'admina1',
                    'first_name' => 'Max',
                    'middle_name' => 'Marvin',
                    'last_name' => 'Müllermann',
                    'www' => 'https://www.example.com/',
                    'address' => 'Street 1',
                    'zip' => '73249',
                    'city' => 'Wernau',
                    'country' => 'Germany',
                    'email' => 'info@email.org',
                    'telephone' => '+4917521122334',
                    'fax' => '+4917521122339',
                ],
                2 => [
                    'uid' => 20,
                    'pid' => 1100,
                    'username' => 'usera2',
                    'first_name' => 'Max1',
                    'middle_name' => 'Marvin1',
                    'last_name' => 'Müllermann1',
                    'www' => 'https://www.example1.com/',
                    'address' => 'Street 11',
                    'zip' => '73250',
                    'city' => 'Wernau1',
                    'country' => 'Germany1',
                    'email' => 'info1@email.org',
                    'telephone' => '+49175211223341',
                    'fax' => '+49175211223391',
                ],
                3 => [
                    'uid' => 24,
                    'pid' => 1100,
                    'username' => 'admina2',
                    'first_name' => 'Max',
                    'middle_name' => 'Marvin',
                    'last_name' => 'Müllermann',
                    'www' => 'https://www.example.com/',
                    'address' => 'Street 1',
                    'zip' => '73249',
                    'city' => 'Wernau',
                    'country' => 'Germany',
                    'email' => 'info@email.org',
                    'telephone' => '+4917521122334',
                    'fax' => '+4917521122339',
                ],
            ],
        ];
        yield '#4 return all frontenduser typed records except from excludePids discarding exluded pid stated as includePid' => [
            'includePids' => [
                110,
            ],
            'excludePids' => [
                110,
                1110,
            ],
            'fields' => [
                'uid',
                'pid',
                'username',
                'first_name',
                'middle_name',
                'last_name',
                'www',
                'address',
                'zip',
                'city',
                'country',
                'email',
                'telephone',
                'fax',
            ],
            'expectedRows' => [
                0 => [
                    'uid' => 10,
                    'pid' => 100,
                    'username' => 'usera1',
                    'first_name' => 'Max1',
                    'middle_name' => 'Marvin1',
                    'last_name' => 'Müllermann1',
                    'www' => 'https://www.example1.com/',
                    'address' => 'Street 11',
                    'zip' => '73250',
                    'city' => 'Wernau1',
                    'country' => 'Germany1',
                    'email' => 'info1@email.org',
                    'telephone' => '+49175211223341',
                    'fax' => '+49175211223391',
                ],
                1 => [
                    'uid' => 14,
                    'pid' => 100,
                    'username' => 'admina1',
                    'first_name' => 'Max',
                    'middle_name' => 'Marvin',
                    'last_name' => 'Müllermann',
                    'www' => 'https://www.example.com/',
                    'address' => 'Street 1',
                    'zip' => '73249',
                    'city' => 'Wernau',
                    'country' => 'Germany',
                    'email' => 'info@email.org',
                    'telephone' => '+4917521122334',
                    'fax' => '+4917521122339',
                ],
                2 => [
                    'uid' => 20,
                    'pid' => 1100,
                    'username' => 'usera2',
                    'first_name' => 'Max1',
                    'middle_name' => 'Marvin1',
                    'last_name' => 'Müllermann1',
                    'www' => 'https://www.example1.com/',
                    'address' => 'Street 11',
                    'zip' => '73250',
                    'city' => 'Wernau1',
                    'country' => 'Germany1',
                    'email' => 'info1@email.org',
                    'telephone' => '+49175211223341',
                    'fax' => '+49175211223391',
                ],
                3 => [
                    'uid' => 24,
                    'pid' => 1100,
                    'username' => 'admina2',
                    'first_name' => 'Max',
                    'middle_name' => 'Marvin',
                    'last_name' => 'Müllermann',
                    'www' => 'https://www.example.com/',
                    'address' => 'Street 1',
                    'zip' => '73249',
                    'city' => 'Wernau',
                    'country' => 'Germany',
                    'email' => 'info@email.org',
                    'telephone' => '+4917521122334',
                    'fax' => '+4917521122339',
                ],
            ],
        ];
    }

    /**
     * @param int[] $includePids
     * @param int[] $excludePids
     * @param string[] $fields
     * @param array<int, array<string, mixed>> $expectedRows
     * @throws \ReflectionException
     */
    #[DataProvider(methodName: 'getUsersWithProfileResultDataSets')]
    #[Test]
    public function getUsersWithProfileResultReturnsExpectedRows(
        array $includePids,
        array $excludePids,
        array $fields,
        array $expectedRows,
    ): void {
        $expectedRows = $this->prepareRows($expectedRows, $fields);
        $profileCreateCommandService = GeneralUtility::makeInstance(ProfileUpdateCommandService::class);
        $rows = (new \ReflectionMethod($profileCreateCommandService, 'getUsersWithProfileResult'))
            ->invoke($profileCreateCommandService, $includePids, $excludePids)->fetchAllAssociative();
        $rows = $this->prepareRows($rows, $fields);
        $this->assertSame($expectedRows, $rows);
    }

    /**
     * All four visibility combinations of a frontend user and its profile must be selected for the
     * synchronization query. Neither a disabled frontend user (`fe_users.disable`) nor a hidden
     * profile (`tx_academicpersons_domain_model_profile.hidden`) may exclude the pair.
     */
    public static function getUsersWithProfileResultReturnsUsersRegardlessOfVisibilityDataSets(): \Generator
    {
        yield 'frontend user visible, profile visible' => [
            'expectedFrontendUserUid' => 30,
        ];
        yield 'frontend user disabled, profile visible' => [
            'expectedFrontendUserUid' => 32,
        ];
        yield 'frontend user visible, profile hidden' => [
            'expectedFrontendUserUid' => 34,
        ];
        yield 'frontend user disabled, profile hidden' => [
            'expectedFrontendUserUid' => 36,
        ];
    }

    /**
     * @throws \ReflectionException
     */
    #[DataProvider(methodName: 'getUsersWithProfileResultReturnsUsersRegardlessOfVisibilityDataSets')]
    #[Test]
    public function getUsersWithProfileResultReturnsUsersRegardlessOfVisibility(int $expectedFrontendUserUid): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSets/visibility-combinations.csv');
        $profileUpdateCommandService = GeneralUtility::makeInstance(ProfileUpdateCommandService::class);
        $rows = (new \ReflectionMethod($profileUpdateCommandService, 'getUsersWithProfileResult'))
            ->invoke($profileUpdateCommandService, [100], [])->fetchAllAssociative();
        $returnedUids = array_map(static fn(array $row): int => (int)$row['uid'], $rows);
        $this->assertContains($expectedFrontendUserUid, $returnedUids);
    }

    public static function executeUpdatesExpectedRecordsDataSets(): \Generator
    {
        yield '#1 without include and exclude pids' => [
            'additionalImportDataSets' => [],
            'includePids' => [],
            'excludePids' => [],
            'assertCsvFileName' => 'updated-for-all-frontendusers.csv',
            'dispatchedEventCount' => 8,
        ];
        yield '#2 only include pids' => [
            'additionalImportDataSets' => [],
            'includePids' => [
                100,
                110,
            ],
            'excludePids' => [],
            'assertCsvFileName' => 'updated-for-only-includepids.csv',
            'dispatchedEventCount' => 4,
        ];
        yield '#3 only exclude pids' => [
            'additionalImportDataSets' => [],
            'includePids' => [],
            'excludePids' => [
                1100,
                1110,
            ],
            'assertCsvFileName' => 'updated-for-only-excludepids.csv',
            'dispatchedEventCount' => 4,
        ];
        yield '#4 only excludePids discarding same pageId as includePid' => [
            'additionalImportDataSets' => [],
            'includePids' => [
                1100,
                1110,
            ],
            'excludePids' => [
                1100,
                1110,
            ],
            'assertCsvFileName' => 'updated-for-only-excludepids.csv',
            'dispatchedEventCount' => 4,
        ];
        // special cases
        yield '#5 exclude pids - updates correct secondary relation records' => [
            'additionalImportDataSets' => [
                __DIR__ . '/Fixtures/DataSets/secondary-relations.csv',
            ],
            'includePids' => [],
            'excludePids' => [
                1100,
                1110,
            ],
            'assertCsvFileName' => 'updated-secondary-relations.csv',
            'dispatchedEventCount' => 5,
        ];
        yield '#6 exclude pids - removes correct relation items if empty' => [
            'additionalImportDataSets' => [
                __DIR__ . '/Fixtures/DataSets/secondary-relations-empty.csv',
            ],
            'includePids' => [],
            'excludePids' => [
                1100,
                1110,
            ],
            'assertCsvFileName' => 'updated-secondary-relations-empty.csv',
            'dispatchedEventCount' => 5,
        ];
        yield '#7 exclude pids - skip sync prevents updates for profile records' => [
            'additionalImportDataSets' => [
                __DIR__ . '/Fixtures/DataSets/skip-sync.csv',
            ],
            'includePids' => [],
            'excludePids' => [
                1100,
                1110,
            ],
            'assertCsvFileName' => 'updated-skip-sync.csv',
            'dispatchedEventCount' => 4,
        ];
        yield '#8 exclude pids - keeps hidden relation records hidden and does not duplicate them' => [
            'additionalImportDataSets' => [
                __DIR__ . '/Fixtures/DataSets/secondary-relations-hidden.csv',
            ],
            'includePids' => [],
            'excludePids' => [
                1100,
                1110,
            ],
            'assertCsvFileName' => 'updated-secondary-relations-hidden.csv',
            'dispatchedEventCount' => 5,
        ];
        yield '#9 exclude pids - keeps hidden profile hidden but still synchronizes its data' => [
            'additionalImportDataSets' => [
                __DIR__ . '/Fixtures/DataSets/secondary-relations-profile-hidden.csv',
            ],
            'includePids' => [],
            'excludePids' => [
                1100,
                1110,
            ],
            'assertCsvFileName' => 'updated-secondary-relations-profile-hidden.csv',
            'dispatchedEventCount' => 5,
        ];
        // Covers all four frontend-user/profile visibility combinations in one run: both visible,
        // frontend user disabled only, profile hidden only, and both disabled/hidden. All of them
        // must be synchronized while their `disable`/`hidden` states are kept untouched.
        yield '#10 exclude pids - synchronizes profiles regardless of frontend user and profile visibility' => [
            'additionalImportDataSets' => [
                __DIR__ . '/Fixtures/DataSets/visibility-combinations.csv',
            ],
            'includePids' => [],
            'excludePids' => [
                1100,
                1110,
            ],
            'assertCsvFileName' => 'updated-visibility-combinations.csv',
            'dispatchedEventCount' => 8,
        ];
    }

    /**
     * @param string[] $additionalImportDataSets
     * @param int[] $includePids
     * @param int[] $excludePids
     * @param string $assertCsvFileName
     * @throws \Doctrine\DBAL\Exception
     */
    #[DataProvider(methodName: 'executeUpdatesExpectedRecordsDataSets')]
    #[Test]
    public function executeUpdatesExpectedRecordsInDatabase(
        array $additionalImportDataSets,
        array $includePids,
        array $excludePids,
        string $assertCsvFileName,
        int $dispatchedEventCount,
    ): void {
        if ($additionalImportDataSets !== []) {
            foreach ($additionalImportDataSets as $importDataSet) {
                $this->assertFileExists($importDataSet);
                $this->importCSVDataSet($importDataSet);
            }
        }
        $dispatchedModifyEvents = [];
        /** @var Container $container */
        $container = $this->get('service_container');
        $container->set(
            'modify-profile-create-environment-state-build-context-for-frontend-user-listener',
            static function (
                ModifyProfileCommandEnvironmentStateBuildContextForFrontendUserEvent $event
            ) use (&$dispatchedModifyEvents): void {
                $dispatchedModifyEvents[] = $dispatchedModifyEvents;
            }
        );
        $listenerProvider = $container->get(ListenerProvider::class);
        $listenerProvider->addListener(
            ModifyProfileCommandEnvironmentStateBuildContextForFrontendUserEvent::class,
            'modify-profile-create-environment-state-build-context-for-frontend-user-listener',
        );
        $this->assertFileExists(__DIR__ . '/Fixtures/Asserts/' . $assertCsvFileName);
        $profileCreateCommandService = GeneralUtility::makeInstance(ProfileUpdateCommandService::class);
        $profileCreateCommandService->execute(new ProfileUpdateCommandDto(
            includePids: $includePids,
            excludePids: $excludePids,
        ));
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/Asserts/' . $assertCsvFileName);
        $this->assertCount($dispatchedEventCount, $dispatchedModifyEvents);
    }

    /**
     * ACE-490: every profile the update runs through is announced through
     * {@see AfterProfileUpdateEvent}, after `persistAll()`, carrying the persisted
     * default language profile - the contract the frontend editing flow and
     * `createProfileForUser()` already honour, both of which also announce a profile
     * whose values already matched. Listeners regenerate the slug and synchronise the
     * translations, so before this the command changed profiles without either
     * happening.
     */
    #[Test]
    public function executeDispatchesAfterProfileUpdateEventPerSynchronisedProfile(): void
    {
        $dispatchedProfileUids = $this->captureAfterProfileUpdateEvents($capturedEvents);

        $profileUpdateCommandService = GeneralUtility::makeInstance(ProfileUpdateCommandService::class);
        $profileUpdateCommandService->execute(new ProfileUpdateCommandDto(
            includePids: [],
            excludePids: [1100, 1110],
        ));

        $this->assertSame([1, 2, 3, 4], $dispatchedProfileUids());
        foreach ($capturedEvents as $event) {
            $this->assertNotNull($event->getProfile()->getUid());
            $this->assertFalse($event->getProfile()->getIsTranslation());
        }
    }

    /**
     * ACE-490: `skip_sync` gates the update per PROFILE now. The provider query only
     * filters on the user level, so a frontend user carrying a synchronisable profile
     * next to a `skip_sync` one is still selected - previously the loop then updated
     * the `skip_sync` profile through that side door. The mixed fixture pins both
     * halves: profile 20 is updated and announced, profile 21 keeps every stale value,
     * gets no contract created from the user's address data, and is not announced.
     */
    #[Test]
    public function executeSkipsSkipSyncProfilesOfMixedUsers(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSets/skip-sync-mixed.csv');
        $dispatchedProfileUids = $this->captureAfterProfileUpdateEvents($capturedEvents);

        $profileUpdateCommandService = GeneralUtility::makeInstance(ProfileUpdateCommandService::class);
        $profileUpdateCommandService->execute(new ProfileUpdateCommandDto(
            includePids: [],
            excludePids: [1100, 1110],
        ));

        $this->assertSame([1, 2, 3, 4, 20], $dispatchedProfileUids());
        $connection = $this->getConnectionPool()->getConnectionForTable('tx_academicpersons_domain_model_profile');
        $syncedProfile = $connection->select(['*'], 'tx_academicpersons_domain_model_profile', ['uid' => 20])->fetchAssociative();
        $this->assertIsArray($syncedProfile);
        $this->assertSame('Mia', $syncedProfile['first_name']);
        $this->assertSame('fe_users:19', $syncedProfile['import_identifier']);
        $skippedProfile = $connection->select(['*'], 'tx_academicpersons_domain_model_profile', ['uid' => 21])->fetchAssociative();
        $this->assertIsArray($skippedProfile);
        $this->assertSame('Stale-Skip', $skippedProfile['first_name']);
        $this->assertSame('https://stale-skip.example.com/', $skippedProfile['website']);
        $this->assertSame('', $skippedProfile['import_identifier']);
        $skippedProfileContracts = $this->getConnectionPool()->getConnectionForTable('tx_academicpersons_domain_model_contract')
            ->select(['uid'], 'tx_academicpersons_domain_model_contract', ['profile' => 21])->fetchAllAssociative();
        $this->assertSame([], $skippedProfileContracts, 'The skip_sync profile received a contract from the user data.');
    }

    /**
     * ACE-365: both contract-data guards of `updateProfileFromFrontendUser()` tested
     * `$frontendUserData['phone']`, and `fe_users` has no such column - it is `telephone`,
     * which is why the method one level down already read that key. `empty()` on an
     * undefined key is always true, so a user whose only contact datum was a telephone
     * number had its existing contract deleted on every run and never got one created.
     * Both directions are asserted here against the record set rather than a count.
     */
    #[Test]
    public function executeKeepsAndCreatesContractsWhenTelephoneIsTheirOnlyData(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSets/telephone-only-contracts.csv');

        $profileUpdateCommandService = GeneralUtility::makeInstance(ProfileUpdateCommandService::class);
        $profileUpdateCommandService->execute(new ProfileUpdateCommandDto(includePids: [100], excludePids: []));

        $contractQueryBuilder = $this->getConnectionPool()
            ->getQueryBuilderForTable('tx_academicpersons_domain_model_contract');
        $contractQueryBuilder->getRestrictions()->removeAll();
        $contracts = $contractQueryBuilder
            ->select('profile', 'import_identifier', 'deleted')
            ->from('tx_academicpersons_domain_model_contract')
            ->where(
                $contractQueryBuilder->expr()->in(
                    'profile',
                    $contractQueryBuilder->quoteArrayBasedValueListToIntegerList([30, 31]),
                ),
            )
            ->orderBy('profile')
            ->executeQuery()
            ->fetchAllAssociative();
        $this->assertSame(
            [
                ['profile' => 30, 'import_identifier' => 'fe_users:30', 'deleted' => 0],
                ['profile' => 31, 'import_identifier' => 'fe_users:31', 'deleted' => 0],
            ],
            $contracts,
        );

        $phoneNumberQueryBuilder = $this->getConnectionPool()
            ->getQueryBuilderForTable('tx_academicpersons_domain_model_phone_number');
        $phoneNumberQueryBuilder->getRestrictions()->removeAll();
        $phoneNumbers = $phoneNumberQueryBuilder
            ->select('phone_number')
            ->from('tx_academicpersons_domain_model_phone_number')
            ->where(
                $phoneNumberQueryBuilder->expr()->in(
                    'phone_number',
                    $phoneNumberQueryBuilder->quoteArrayBasedValueListToStringList([
                        '+49 711 123456',
                        '+49 711 654321',
                    ]),
                ),
            )
            ->orderBy('phone_number')
            ->executeQuery()
            ->fetchAllAssociative();
        $this->assertSame(
            [
                ['phone_number' => '+49 711 123456'],
                ['phone_number' => '+49 711 654321'],
            ],
            $phoneNumbers,
        );
    }

    /**
     * The identifier carries the source field and never the configured type, so changing
     * the configuration updates a record instead of writing a second one. This is the
     * trap ACE-365 reproduced before the change: with the type inside the identifier, a
     * reconfiguration made both halves of the match fail and duplicated every imported
     * number. Contract 40 pins the collision policy (canonical wins, legacy untouched),
     * 41 the reuse of a legacy identifier, 42 the plain new import - asserted
     * exhaustively, because a duplicate is invisible to a per-uid CSV assertion.
     */
    #[Test]
    public function executeSynchronisesConfiguredAndLegacyPhoneNumberImportsWithoutDuplicates(): void
    {
        $this->reconfigureAcademicPersons([
            'profile' => [
                'feuser' => [
                    'faxNumberType' => 'private',
                    'telephoneNumberType' => 'mobile',
                ],
            ],
        ]);
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSets/phone-number-import-behaviour.csv');

        $profileUpdateCommandService = GeneralUtility::makeInstance(ProfileUpdateCommandService::class);
        $profileUpdateCommandService->execute(new ProfileUpdateCommandDto(includePids: [100], excludePids: []));

        $queryBuilder = $this->getConnectionPool()
            ->getQueryBuilderForTable('tx_academicpersons_domain_model_phone_number');
        $queryBuilder->getRestrictions()->removeAll();
        $rows = $queryBuilder
            ->select('contract', 'type', 'phone_number', 'import_identifier')
            ->from('tx_academicpersons_domain_model_phone_number')
            ->where(
                $queryBuilder->expr()->in(
                    'contract',
                    $queryBuilder->quoteArrayBasedValueListToIntegerList([40, 41, 42]),
                ),
            )
            ->orderBy('contract')
            ->addOrderBy('import_identifier')
            ->executeQuery()
            ->fetchAllAssociative();

        $this->assertSame(
            [
                [
                    'contract' => 40,
                    'type' => 'business',
                    'phone_number' => 'Fax valid updated',
                    'import_identifier' => 'fax:fe_users:40',
                ],
                [
                    'contract' => 40,
                    'type' => 'phone',
                    'phone_number' => 'Legacy collision',
                    'import_identifier' => 'phone:fe_users:40',
                ],
                [
                    'contract' => 40,
                    'type' => 'private',
                    'phone_number' => 'Canonical updated',
                    'import_identifier' => 'telephone:fe_users:40',
                ],
                [
                    'contract' => 41,
                    'type' => 'private',
                    'phone_number' => 'Fax migrated',
                    'import_identifier' => 'fax:fe_users:41',
                ],
                [
                    'contract' => 41,
                    'type' => 'mobile',
                    'phone_number' => 'Legacy updated',
                    'import_identifier' => 'telephone:fe_users:41',
                ],
                [
                    'contract' => 42,
                    'type' => 'private',
                    'phone_number' => 'New fax',
                    'import_identifier' => 'fax:fe_users:42',
                ],
                [
                    'contract' => 42,
                    'type' => 'mobile',
                    'phone_number' => 'New telephone',
                    'import_identifier' => 'telephone:fe_users:42',
                ],
            ],
            $rows,
        );
    }

    /**
     * The invariant of the feature: this synchronization never writes a type the backend
     * cannot resolve. A configured value that is not in `types.phoneNumberTypes` has to
     * reach the record as the empty `undefined` type the TCA ships as its first item -
     * proven here against the database, because the resolver's own coverage runs against
     * a mocked configuration and cannot show what is stored. The fax half configures a
     * valid, non-default type in the same run, so the fallback is not mistaken for
     * "nothing was configured at all".
     */
    #[Test]
    public function executeStoresTheUndefinedTypeWhenTheConfiguredOneIsNotSelectable(): void
    {
        $this->reconfigureAcademicPersons([
            'profile' => [
                'feuser' => [
                    'faxNumberType' => 'mobile',
                    'telephoneNumberType' => 'no-such-type',
                ],
            ],
        ]);
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSets/phone-number-import-behaviour.csv');

        $profileUpdateCommandService = GeneralUtility::makeInstance(ProfileUpdateCommandService::class);
        $profileUpdateCommandService->execute(new ProfileUpdateCommandDto(includePids: [100], excludePids: []));

        $queryBuilder = $this->getConnectionPool()
            ->getQueryBuilderForTable('tx_academicpersons_domain_model_phone_number');
        $queryBuilder->getRestrictions()->removeAll();
        $rows = $queryBuilder
            ->select('type', 'import_identifier')
            ->from('tx_academicpersons_domain_model_phone_number')
            ->where(
                $queryBuilder->expr()->eq(
                    'contract',
                    $queryBuilder->createNamedParameter(42, Connection::PARAM_INT),
                ),
            )
            ->orderBy('import_identifier')
            ->addOrderBy('uid')
            ->executeQuery()
            ->fetchAllAssociative();

        $this->assertSame(
            [
                ['type' => 'mobile', 'import_identifier' => 'fax:fe_users:42'],
                ['type' => '', 'import_identifier' => 'telephone:fe_users:42'],
            ],
            $rows,
        );
    }

    /**
     * `phone` and `fax` are only corrected because a default installation cannot select
     * them. An installation that offers them as real types has editors who may have chosen
     * them deliberately, so the correction has to stand down - the invariant is "never
     * write an unselectable type", not "never write phone".
     */
    #[Test]
    public function executePreservesLegacyTypeValuesWhenTheyAreSelectable(): void
    {
        $this->reconfigureAcademicPersons([
            'types' => ['phoneNumberTypes' => 'business=Business,phone=Phone,fax=Fax'],
        ]);
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSets/phone-number-import-behaviour.csv');

        $profileUpdateCommandService = GeneralUtility::makeInstance(ProfileUpdateCommandService::class);
        $profileUpdateCommandService->execute(new ProfileUpdateCommandDto(includePids: [100], excludePids: []));

        $connection = $this->getConnectionPool()
            ->getConnectionForTable('tx_academicpersons_domain_model_phone_number');
        $telephone = $connection->select(
            ['type', 'import_identifier'],
            'tx_academicpersons_domain_model_phone_number',
            ['uid' => 43],
        )->fetchAssociative();
        $fax = $connection->select(
            ['type', 'import_identifier'],
            'tx_academicpersons_domain_model_phone_number',
            ['uid' => 44],
        )->fetchAssociative();

        $this->assertSame(
            ['type' => 'phone', 'import_identifier' => 'telephone:fe_users:41'],
            $telephone,
        );
        $this->assertSame(
            ['type' => 'fax', 'import_identifier' => 'fax:fe_users:41'],
            $fax,
        );
    }

    /**
     * Registers a capturing listener for {@see AfterProfileUpdateEvent} and returns a
     * closure yielding the sorted profile uids of the captured events.
     *
     * @param array<int, AfterProfileUpdateEvent>|null $capturedEvents
     * @return \Closure(): list<int>
     */
    private function captureAfterProfileUpdateEvents(?array &$capturedEvents): \Closure
    {
        $capturedEvents = [];
        /** @var Container $container */
        $container = $this->get('service_container');
        $container->set(
            'after-profile-update-event-capture-listener',
            static function (AfterProfileUpdateEvent $event) use (&$capturedEvents): void {
                $capturedEvents[] = $event;
            }
        );
        $listenerProvider = $container->get(ListenerProvider::class);
        $listenerProvider->addListener(
            AfterProfileUpdateEvent::class,
            'after-profile-update-event-capture-listener',
        );
        return static function () use (&$capturedEvents): array {
            $uids = array_map(
                static fn(AfterProfileUpdateEvent $event): int => (int)$event->getProfile()->getUid(),
                $capturedEvents,
            );
            sort($uids);
            return $uids;
        };
    }

    /**
     * The selectable type list is a construction-time snapshot on a shared service, so a
     * configuration written inside a test method reaches it only once that snapshot is
     * rebuilt. Without this, whether an assertion sees the new list depends on whether
     * something instantiated the service earlier in the same method - and this suite runs
     * in random order.
     *
     * @param array<string, mixed> $overrides
     */
    private function reconfigureAcademicPersons(array $overrides): void
    {
        ArrayUtility::mergeRecursiveWithOverrule(
            $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['academic_persons'],
            $overrides,
        );
        $container = $this->get('service_container');
        $this->assertInstanceOf(Container::class, $container);
        $container->set(
            PhoneNumberTypes::class,
            new PhoneNumberTypes(GeneralUtility::makeInstance(ExtensionConfiguration::class)),
        );
    }

    // @toDo: Add tests for no record and no data returning early without creating a record
}
