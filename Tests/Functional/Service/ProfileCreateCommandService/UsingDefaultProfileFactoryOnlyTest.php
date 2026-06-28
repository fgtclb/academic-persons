<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Service\ProfileCreateCommandService;

use FGTCLB\AcademicPersons\Domain\Model\Dto\ProfileCreateCommandDto;
use FGTCLB\AcademicPersons\Profile\ProfileFactory;
use FGTCLB\AcademicPersons\Service\Event\ModifyProfileCommandEnvironmentStateBuildContextForFrontendUserEvent;
use FGTCLB\AcademicPersons\Service\ProfileCreateCommandService;
use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use Symfony\Component\DependencyInjection\Container;
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
        $this->importCSVDataSet(__DIR__ . '/Fixtures/site-structure.csv');
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
    public function sharedInstanceRetrievesPersonsProfileFactoryAsDefaultProfileFactory(): void
    {
        $profileCreateCommandService = GeneralUtility::makeInstance(ProfileCreateCommandService::class);
        $defaultFactory = (new \ReflectionProperty($profileCreateCommandService, 'defaultFactory'))->getValue($profileCreateCommandService);
        $this->assertInstanceOf(ProfileFactory::class, $defaultFactory);
    }

    public static function getUsersWithoutProfileResultDataSets(): \Generator
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
    #[DataProvider(methodName: 'getUsersWithoutProfileResultDataSets')]
    #[Test]
    public function getUsersWithoutProfileResultReturnsExpectedRows(
        array $includePids,
        array $excludePids,
        array $fields,
        array $expectedRows,
    ): void {
        $expectedRows = $this->prepareRows($expectedRows, $fields);
        $profileCreateCommandService = GeneralUtility::makeInstance(ProfileCreateCommandService::class);
        $rows = (new \ReflectionMethod($profileCreateCommandService, 'getUsersWithoutProfileResult'))
            ->invoke($profileCreateCommandService, $includePids, $excludePids)->fetchAllAssociative();
        $rows = $this->prepareRows($rows, $fields);
        $this->assertSame($expectedRows, $rows);
    }

    /**
     * A frontend user without a profile must be selected for the profile creation regardless of its
     * own visibility, so a disabled frontend user gets a profile as well. A frontend user that
     * already has a profile - hidden or not - must never be selected, because the M:N relation, not
     * the profile visibility, decides whether a profile exists.
     */
    public static function getUsersWithoutProfileResultReturnsUsersRegardlessOfVisibilityDataSets(): \Generator
    {
        yield 'visible frontend user without profile is returned' => [
            'frontendUserUid' => 10,
            'shouldBeReturned' => true,
        ];
        yield 'disabled frontend user without profile is returned' => [
            'frontendUserUid' => 30,
            'shouldBeReturned' => true,
        ];
        yield 'visible frontend user with visible profile is not returned' => [
            'frontendUserUid' => 32,
            'shouldBeReturned' => false,
        ];
        yield 'visible frontend user with hidden profile is not returned' => [
            'frontendUserUid' => 34,
            'shouldBeReturned' => false,
        ];
        yield 'disabled frontend user with visible profile is not returned' => [
            'frontendUserUid' => 36,
            'shouldBeReturned' => false,
        ];
        yield 'disabled frontend user with hidden profile is not returned' => [
            'frontendUserUid' => 38,
            'shouldBeReturned' => false,
        ];
    }

    /**
     * @throws \ReflectionException
     */
    #[DataProvider(methodName: 'getUsersWithoutProfileResultReturnsUsersRegardlessOfVisibilityDataSets')]
    #[Test]
    public function getUsersWithoutProfileResultReturnsUsersRegardlessOfVisibility(
        int $frontendUserUid,
        bool $shouldBeReturned,
    ): void {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/visibility-combinations.csv');
        $profileCreateCommandService = GeneralUtility::makeInstance(ProfileCreateCommandService::class);
        $rows = (new \ReflectionMethod($profileCreateCommandService, 'getUsersWithoutProfileResult'))
            ->invoke($profileCreateCommandService, [100], [])->fetchAllAssociative();
        $returnedUids = array_map(static fn(array $row): int => (int)$row['uid'], $rows);
        if ($shouldBeReturned) {
            $this->assertContains($frontendUserUid, $returnedUids);
        } else {
            $this->assertNotContains($frontendUserUid, $returnedUids);
        }
    }

    public static function executeCreatesExpectedRecordsDataSets(): \Generator
    {
        yield '#1 without include and exclude pids' => [
            'includePids' => [],
            'excludePids' => [],
            'assertCsvFileName' => 'created-for-all-frontendusers.csv',
            'dispatchedEventCount' => 8,
        ];
        yield '#2 only include pids' => [
            'includePids' => [
                100,
                110,
            ],
            'excludePids' => [],
            'assertCsvFileName' => 'created-for-only-includepids.csv',
            'dispatchedEventCount' => 4,
        ];
        yield '#3 only exclude pids' => [
            'includePids' => [],
            'excludePids' => [
                1100,
                1110,
            ],
            'assertCsvFileName' => 'created-for-only-excludepids.csv',
            'dispatchedEventCount' => 4,
        ];
        yield '#4 only excludePids discarding same pageId as includePid' => [
            'includePids' => [
                1100,
                1110,
            ],
            'excludePids' => [
                1100,
                1110,
            ],
            'assertCsvFileName' => 'created-for-only-excludepids.csv',
            'dispatchedEventCount' => 4,
        ];
    }

    /**
     * @param int[] $includePids
     * @param int[] $excludePids
     * @param string $assertCsvFileName
     * @throws \Doctrine\DBAL\Exception
     */
    #[DataProvider(methodName: 'executeCreatesExpectedRecordsDataSets')]
    #[Test]
    public function executeCreatesExpectedRecordsInDatabase(
        array $includePids,
        array $excludePids,
        string $assertCsvFileName,
        int $dispatchedEventCount,
    ): void {
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
        $profileCreateCommandService = GeneralUtility::makeInstance(ProfileCreateCommandService::class);
        $profileCreateCommandService->execute(new ProfileCreateCommandDto(
            includePids: $includePids,
            excludePids: $excludePids,
        ));
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/Asserts/' . $assertCsvFileName);
        $this->assertCount($dispatchedEventCount, $dispatchedModifyEvents);
    }

    /**
     * A frontend user that already has a profile - even a hidden one - must not get a second
     * profile created. The hidden profile keeps its visibility and is left to the update command
     * for data synchronization.
     *
     * The first create run sets up the real profile (and the M:N relation) for the frontend users
     * of pid 100. One of them is hidden afterwards, then a second create run must neither duplicate
     * the hidden profile nor change its visibility.
     *
     * @throws \Doctrine\DBAL\Exception
     */
    #[Test]
    public function executeDoesNotCreateAdditionalProfileForUserWithExistingHiddenProfile(): void
    {
        $profileCreateCommandService = GeneralUtility::makeInstance(ProfileCreateCommandService::class);

        // First run: creates the profiles for the frontend users of pid 100 (uids 10 and 14).
        $profileCreateCommandService->execute(new ProfileCreateCommandDto(includePids: [100], excludePids: []));
        $this->assertSame(
            2,
            $this->countProfiles(),
            'The first create run must create the two profiles for pid 100.',
        );

        // Hide the profile of fe_user 10.
        $connection = $this->getConnectionPool()->getConnectionForTable('tx_academicpersons_domain_model_profile');
        $connection->update(
            'tx_academicpersons_domain_model_profile',
            ['hidden' => 1],
            ['import_identifier' => 'fe_users:10'],
        );

        // Second run: must not create a second profile for the now hidden one.
        $profileCreateCommandService->execute(new ProfileCreateCommandDto(includePids: [100], excludePids: []));

        $this->assertSame(
            1,
            $this->countProfiles(['import_identifier' => 'fe_users:10']),
            'The hidden profile of fe_user 10 must not be duplicated by a second create run.',
        );
        $this->assertSame(
            1,
            $this->countProfiles(['import_identifier' => 'fe_users:10', 'hidden' => 1]),
            'The hidden profile must keep its visibility.',
        );
        $this->assertSame(
            2,
            $this->countProfiles(),
            'No additional profile must be created on the second run.',
        );
    }

    /**
     * A disabled frontend user without a profile must still get a profile created for it, just like
     * a visible frontend user. The frontend user visibility must not exclude it from the creation.
     *
     * @throws \Doctrine\DBAL\Exception
     */
    #[Test]
    public function executeCreatesProfileForDisabledFrontendUserWithoutProfile(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/frontend-user-disabled-without-profile.csv');
        $profileCreateCommandService = GeneralUtility::makeInstance(ProfileCreateCommandService::class);

        // pid 100 has the visible frontend users 10 and 14 plus the disabled user 30, all without a profile.
        $profileCreateCommandService->execute(new ProfileCreateCommandDto(includePids: [100], excludePids: []));

        $this->assertSame(
            1,
            $this->countProfiles(['import_identifier' => 'fe_users:30']),
            'A profile must be created for the disabled frontend user without a profile.',
        );
        $this->assertSame(
            3,
            $this->countProfiles(),
            'Profiles must be created for the visible and the disabled frontend users of pid 100.',
        );
    }

    /**
     * Counts profile records ignoring all enable field restrictions, so hidden profiles are
     * counted as well.
     *
     * @param array<string, int|string> $identifiers
     * @throws \Doctrine\DBAL\Exception
     */
    private function countProfiles(array $identifiers = []): int
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('tx_academicpersons_domain_model_profile');
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder->count('uid')->from('tx_academicpersons_domain_model_profile');
        foreach ($identifiers as $field => $value) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->eq($field, $queryBuilder->createNamedParameter($value))
            );
        }
        return (int)$queryBuilder->executeQuery()->fetchOne();
    }
}
