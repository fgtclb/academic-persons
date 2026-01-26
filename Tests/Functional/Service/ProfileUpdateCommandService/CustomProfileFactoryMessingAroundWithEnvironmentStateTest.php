<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Service\ProfileUpdateCommandService;

use FGTCLB\AcademicPersons\Domain\Model\Dto\ProfileUpdateCommandDto;
use FGTCLB\AcademicPersons\Service\Event\ModifyProfileCommandEnvironmentStateBuildContextForFrontendUserEvent;
use FGTCLB\AcademicPersons\Service\ProfileUpdateCommandService;
use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use Symfony\Component\DependencyInjection\Container;
use TESTS\TestMessyProfileFactory\Persons\MessyProfileFactory;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

final class CustomProfileFactoryMessingAroundWithEnvironmentStateTest extends AbstractAcademicPersonsTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
        'DE' => ['id' => 1, 'title' => 'Deutsch', 'locale' => 'de_DE.UTF8', 'iso' => 'de', 'hrefLang' => 'de-DE', 'direction' => ''],
    ];

    protected function setUp(): void
    {
        $this->addCoreExtension('typo3/cms-fluid-styled-content');
        $this->addTestExtension('tests/test-messy-profile-factory');
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
                'EXTENSION' => [
                    'academic_persons' => [
                        'demand' => [
                            'allowedGroupByValues' => 'firstNameAlpha=LLL:EXT:academic_persons/Resources/Private/Language/locallang_be.xlf:flexform.el.groupBy.items.first_name,lastNameAlpha=LLL:EXT:academic_persons/Resources/Private/Language/locallang_be.xlf:flexform.el.groupBy.items.last_name',
                            'allowedSortByValues' => 'firstName=LLL:EXT:academic_persons/Resources/Private/Language/locallang_be.xlf:flexform.el.sortBy.items.first_name,lastName=LLL:EXT:academic_persons/Resources/Private/Language/locallang_be.xlf:flexform.el.sortBy.items.last_name',
                        ],
                        'types' => [
                            'emailAddressTypes' => 'private=Private,business=Business',
                            'phoneNumberTypes' => 'private=Private,business=Business,mobile=Mobile',
                            'physicalAddressTypes' => 'private=Private,business=Business',
                            'profile.autoCreateProfiles' => 1,
                            'profile.createProfileForUserGroups' => '',
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
            'EXT:academic_persons/Configuration/TypoScript/constants.typoscript',
            'EXT:academic_persons/Tests/Functional/Service/ProfileCreateCommandService/Fixtures/TypoScript/Constants/constants.typoscript',
        ];
        $setup ??= [
            'EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript',
            'EXT:academic_persons/Configuration/TypoScript/setup.typoscript',
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
    public function getSuitableProfileFactoryReturnsMessyProfileFactory(): void
    {
        $profileCreateCommandService = GeneralUtility::makeInstance(ProfileUpdateCommandService::class);
        $frontendUserAuthentication = GeneralUtility::makeInstance(FrontendUserAuthentication::class);
        $frontendUserAuthentication->user = ['uid' => 123, 'pid' => 2];
        $profileFactory = (new \ReflectionMethod($profileCreateCommandService, 'getSuitableProfileFactory'))
            ->invoke($profileCreateCommandService, $frontendUserAuthentication);
        $this->assertInstanceOf(MessyProfileFactory::class, $profileFactory);
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

    public static function executeCreatesExpectedRecordsDataSets(): \Generator
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
    }

    /**
     * @param string[] $additionalImportDataSets
     * @param int[] $includePids
     * @param int[] $excludePids
     * @param string $assertCsvFileName
     * @throws \Doctrine\DBAL\Exception
     */
    #[DataProvider(methodName: 'executeCreatesExpectedRecordsDataSets')]
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
        $commandDto = new ProfileUpdateCommandDto(
            includePids: $includePids,
            excludePids: $excludePids,
        );
        $service = GeneralUtility::makeInstance(ProfileUpdateCommandService::class);
        $service->execute($commandDto);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/Asserts/' . $assertCsvFileName);
        $this->assertCount($dispatchedEventCount, $dispatchedModifyEvents);
    }
}
