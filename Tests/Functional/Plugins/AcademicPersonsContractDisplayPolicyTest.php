<?php

declare(strict_types=1);

/*
 * This file is part of the fgtclb/academic extension collection.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace FGTCLB\AcademicPersons\Tests\Functional\Plugins;

use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend;

/**
 * Which contracts the shipped templates render per profile, as the content element
 * configures it: all, the first, those of the plugin's units and function types, those
 * valid today - and how long a page that depends on the date is cached.
 *
 * The fixture, by position in the editor's order:
 *
 * | Profile      | Contract   | Unit             | Function type | Valid                  |
 * |--------------|------------|------------------|---------------|------------------------|
 * | Ada Lovelace | Emeritus   | Mathematics      | Professorship | until 2020-12-31       |
 * |              | Professor  | Computer Science | Professorship | from 2021-01-01        |
 * |              | Dean       | Mathematics      | Management    | always                 |
 * | Grace Hopper | Lecturer   | Mathematics      | Professorship | always                 |
 * |              | Researcher | Computer Science | Professorship | from in 30 days        |
 * |              | Advisor    | Computer Science | Management    | always                 |
 */
final class AcademicPersonsContractDisplayPolicyTest extends AbstractAcademicPersonsTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    private const ADA = 'Ada Lovelace';
    private const GRACE = 'Grace Hopper';

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration([
            'SYS' => [
                'caching' => [
                    'cacheConfigurations' => [
                        // The testing framework replaces the page cache by a NullBackend. The
                        // database backend is restored, so the lifetime a rendering gives its
                        // page cache entry can be read back, and a second request can be served
                        // from that entry.
                        'pages' => [
                            'backend' => Typo3DatabaseBackend::class,
                        ],
                    ],
                ],
            ],
        ]);
        $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsContractDisplayPolicy/records.csv');
        // Relative to the run, so the "valid today" expectations do not flip on a fixed date.
        $this->getConnectionPool()->getConnectionForTable('tx_academicpersons_domain_model_contract')
            ->update('tx_academicpersons_domain_model_contract', ['valid_from' => $this->todayMidnight()->modify('+30 days')->getTimestamp()], ['uid' => 5]);
        $this->setUpFrontendRootPage(
            pageId: 1,
            typoScriptFiles: [
                'constants' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript',
                    'EXT:academic_persons/Configuration/TypoScript/Default/constants.typoscript',
                    'EXT:academic_persons/Tests/Functional/Plugins/Fixtures/TypoScript/Constants/DetailPage.typoscript',
                ],
                'setup' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript',
                    'EXT:academic_persons/Configuration/TypoScript/Default/setup.typoscript',
                    'EXT:academic_persons/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/Rendering.typoscript',
                    'EXT:academic_persons/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/CachePeriodTwoDays.typoscript',
                ],
            ],
        );
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration('EN', '/'),
        ]);
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    /**
     * @param array<string, string> $settings FlexForm fields below `settings.`
     */
    private function addContentElement(string $cType, array $settings): void
    {
        $fields = '';
        foreach (array_merge(['fallbackForNonTranslated' => '0'], $settings) as $name => $value) {
            $fields .= sprintf(
                '<field index="settings.%s"><value index="vDEF">%s</value></field>',
                $name,
                htmlspecialchars($value),
            );
        }
        $this->getConnectionPool()->getConnectionForTable('tt_content')->insert('tt_content', [
            'uid' => 1,
            'pid' => 2,
            'CType' => $cType,
            'header' => '',
            'pi_flexform' => '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>'
                . '<T3FlexForms><data><sheet index="sDEF"><language index="lDEF">'
                . $fields
                . '</language></sheet></data></T3FlexForms>',
        ]);
    }

    private function renderHomePage(): string
    {
        return $this->renderFrontendPage('https://www.acme.com/home');
    }

    /**
     * The contract positions every rendered profile item shows, by profile name.
     *
     * @return array<string, list<string>>
     */
    private function contractsPerProfile(string $content): array
    {
        $document = new \DOMDocument();
        $document->loadHTML('<?xml encoding="utf-8" ?>' . $content, LIBXML_NOERROR);
        $xpath = new \DOMXPath($document);
        $hasClass = static fn(string $class): string => sprintf("contains(concat(' ', normalize-space(@class), ' '), ' %s ')", $class);
        $text = static fn(\DOMNode $node): string => trim((string)preg_replace('#\s+#u', ' ', $node->textContent));

        $profiles = [];
        foreach ($xpath->query(sprintf('//div[%s]', $hasClass('academic-persons-item'))) ?: [] as $item) {
            $names = $xpath->query(sprintf('.//*[%s]', $hasClass('card-title')), $item);
            $name = $names === false ? null : $names->item(0);
            $this->assertInstanceOf(\DOMNode::class, $name, 'A profile item without a name was rendered.');
            $positions = [];
            // One list per contract; its first row is the position, the rows after it the
            // other fields of the contract.
            foreach ($xpath->query(sprintf('.//ul[%s]/li[1]', $hasClass('list-group')), $item) ?: [] as $position) {
                $positions[] = (string)preg_replace('#^Position: #', '', $text($position));
            }
            $profiles[$text($name)] = $positions;
        }
        ksort($profiles);

        return $profiles;
    }

    /**
     * @return \Generator<string, array{0: array<string, string>, 1: array<string, list<string>>}>
     */
    public static function listSettingsDataProvider(): \Generator
    {
        yield 'no contract options' => [
            [],
            [self::ADA => ['Emeritus', 'Professor', 'Dean'], self::GRACE => ['Lecturer', 'Researcher', 'Advisor']],
        ];
        yield 'all, stated' => [
            ['contracts.display' => 'all', 'contracts.matchFilter' => '0', 'contracts.onlyValid' => '0'],
            [self::ADA => ['Emeritus', 'Professor', 'Dean'], self::GRACE => ['Lecturer', 'Researcher', 'Advisor']],
        ];
        yield 'first' => [
            ['contracts.display' => 'first'],
            [self::ADA => ['Emeritus'], self::GRACE => ['Lecturer']],
        ];
        yield 'matching the unit of the plugin' => [
            ['organisationalUnits' => '2', 'contracts.matchFilter' => '1'],
            [self::ADA => ['Professor'], self::GRACE => ['Researcher', 'Advisor']],
        ];
        yield 'the unit of the plugin, not matched' => [
            ['organisationalUnits' => '2', 'contracts.matchFilter' => '0'],
            [self::ADA => ['Emeritus', 'Professor', 'Dean'], self::GRACE => ['Lecturer', 'Researcher', 'Advisor']],
        ];
        yield 'first matching the unit of the plugin' => [
            ['organisationalUnits' => '2', 'contracts.matchFilter' => '1', 'contracts.display' => 'first'],
            [self::ADA => ['Professor'], self::GRACE => ['Researcher']],
        ];
        yield 'matching the function type of the plugin' => [
            ['functionTypes' => '2', 'contracts.matchFilter' => '1'],
            [self::ADA => ['Dean'], self::GRACE => ['Advisor']],
        ];
        yield 'matching without a restriction of the plugin' => [
            ['contracts.matchFilter' => '1'],
            [self::ADA => ['Emeritus', 'Professor', 'Dean'], self::GRACE => ['Lecturer', 'Researcher', 'Advisor']],
        ];
        yield 'valid today' => [
            ['contracts.onlyValid' => '1'],
            [self::ADA => ['Professor', 'Dean'], self::GRACE => ['Lecturer', 'Advisor']],
        ];
        yield 'first valid today' => [
            ['contracts.display' => 'first', 'contracts.onlyValid' => '1'],
            [self::ADA => ['Professor'], self::GRACE => ['Lecturer']],
        ];
        yield 'first valid today matching the unit of the plugin' => [
            ['organisationalUnits' => '2', 'contracts.matchFilter' => '1', 'contracts.display' => 'first', 'contracts.onlyValid' => '1'],
            [self::ADA => ['Professor'], self::GRACE => ['Advisor']],
        ];
    }

    /**
     * @param array<string, string> $settings
     * @param array<string, list<string>> $expected
     */
    #[Test]
    #[DataProvider('listSettingsDataProvider')]
    public function listShowsTheConfiguredContracts(array $settings, array $expected): void
    {
        $this->addContentElement('academicpersons_list', $settings);

        $this->assertSame($expected, $this->contractsPerProfile($this->renderHomePage()));
    }

    #[Test]
    public function listAndDetailShowsTheConfiguredContracts(): void
    {
        $this->addContentElement('academicpersons_listanddetail', ['contracts.display' => 'first', 'contracts.onlyValid' => '1']);

        $this->assertSame(
            [self::ADA => ['Professor'], self::GRACE => ['Lecturer']],
            $this->contractsPerProfile($this->renderHomePage()),
        );
    }

    #[Test]
    public function cardShowsTheConfiguredContracts(): void
    {
        $this->addContentElement('academicpersons_card', [
            'demand.profileList' => '1,2',
            'contracts.display' => 'first',
            'contracts.onlyValid' => '1',
        ]);

        $this->assertSame(
            [self::ADA => ['Professor'], self::GRACE => ['Lecturer']],
            $this->contractsPerProfile($this->renderHomePage()),
        );
    }

    #[Test]
    public function selectedProfilesShowTheConfiguredContracts(): void
    {
        $this->addContentElement('academicpersons_selectedprofiles', [
            'selectedProfiles' => '1,2',
            'contracts.display' => 'first',
            'contracts.onlyValid' => '1',
        ]);

        $this->assertSame(
            [self::ADA => ['Professor'], self::GRACE => ['Lecturer']],
            $this->contractsPerProfile($this->renderHomePage()),
        );
    }

    /**
     * The selected-contracts element offers none of the options. Settings that reach it
     * anyway - through TypoScript, or a FlexForm copied from another element - do not
     * change which contract an item shows: the one the editor chose, expired or not.
     */
    #[Test]
    public function selectedContractsShowTheChosenContractWhateverTheOptions(): void
    {
        $this->addContentElement('academicpersons_selectedcontracts', [
            'selectedContracts' => '1,6',
            'organisationalUnits' => '2',
            'contracts.display' => 'first',
            'contracts.matchFilter' => '1',
            'contracts.onlyValid' => '1',
        ]);

        $this->assertSame(
            [self::ADA => ['Emeritus'], self::GRACE => ['Advisor']],
            $this->contractsPerProfile($this->renderHomePage()),
        );
    }

    /**
     * The detail view keeps every contract in both blocks with the shipped `Settings.yaml`,
     * whatever the list does.
     */
    #[Test]
    public function detailViewShowsEveryContractWithTheShippedSettings(): void
    {
        $this->addContentElement('academicpersons_detail', []);

        $content = $this->renderFrontendPage(
            'https://www.acme.com/home?' . http_build_query([
                'tx_academicpersons_detail' => [
                    'controller' => 'Profile',
                    'action' => 'detail',
                    'profile' => 1,
                ],
                'cHash' => '13c8ec3ab2a317651a40bd164df8a366',
            ])
        );

        $this->assertStringContainsString('academic-persons-detail__position">Emeritus</p>', $content);
        $this->assertStringContainsString('academic-persons-detail__position">Professor</p>', $content);
        $this->assertStringContainsString('academic-persons-detail__position">Dean</p>', $content);
        $this->assertStringContainsString('mailto:emeritus@example.com', $content);
        $this->assertStringContainsString('mailto:professor@example.com', $content);
        $this->assertStringContainsString('mailto:dean@example.com', $content);
    }

    /**
     * @return array{expires: int}
     */
    private function pageCacheEntry(): array
    {
        $rows = $this->getConnectionPool()
            ->getConnectionForTable('cache_pages')
            ->select(['expires'], 'cache_pages')
            ->fetchAllAssociative();
        $this->assertCount(1, $rows, 'The rendered page did not reach the page cache.');

        return ['expires' => (int)$rows[0]['expires']];
    }

    private function todayMidnight(): \DateTimeImmutable
    {
        return (new \DateTimeImmutable())->setTimestamp((int)$GLOBALS['EXEC_TIME'])->setTime(0, 0);
    }

    /**
     * A shown contract that ends today is gone tomorrow, so the page expires at midnight
     * instead of after the configured two days.
     */
    #[Test]
    public function aPageShowingAContractThatEndsTodayExpiresAtMidnight(): void
    {
        $tomorrow = $this->todayMidnight()->modify('+1 day');
        $this->getConnectionPool()->getConnectionForTable('tx_academicpersons_domain_model_contract')
            ->update('tx_academicpersons_domain_model_contract', ['valid_to' => $this->todayMidnight()->getTimestamp()], ['uid' => 2]);
        $this->addContentElement('academicpersons_list', ['contracts.onlyValid' => '1']);

        $content = $this->renderHomePage();

        $this->assertSame([self::ADA => ['Professor', 'Dean'], self::GRACE => ['Lecturer', 'Advisor']], $this->contractsPerProfile($content));
        $this->assertSame($tomorrow->getTimestamp(), $this->pageCacheEntry()['expires']);
    }

    /**
     * A contract that starts tomorrow is shown tomorrow: the page rendered today is not
     * served any more once tomorrow has begun.
     */
    #[Test]
    public function aContractThatStartsTomorrowIsShownTomorrow(): void
    {
        $tomorrow = $this->todayMidnight()->modify('+1 day');
        $this->getConnectionPool()->getConnectionForTable('tx_academicpersons_domain_model_contract')
            ->update('tx_academicpersons_domain_model_contract', ['valid_from' => $tomorrow->getTimestamp()], ['uid' => 5]);
        $this->addContentElement('academicpersons_list', ['contracts.onlyValid' => '1']);

        $this->assertSame(
            [self::ADA => ['Professor', 'Dean'], self::GRACE => ['Lecturer', 'Advisor']],
            $this->contractsPerProfile($this->renderHomePage()),
        );
        $this->assertSame($tomorrow->getTimestamp(), $this->pageCacheEntry()['expires']);

        // One second into tomorrow. Without the restriction the entry written today would
        // still be valid for most of the day and be served unchanged.
        $GLOBALS['EXEC_TIME'] = $tomorrow->getTimestamp() + 1;
        $GLOBALS['SIM_EXEC_TIME'] = $GLOBALS['EXEC_TIME'];
        $GLOBALS['ACCESS_TIME'] = $GLOBALS['EXEC_TIME'] - ($GLOBALS['EXEC_TIME'] % 60);
        $GLOBALS['SIM_ACCESS_TIME'] = $GLOBALS['ACCESS_TIME'];

        $this->assertSame(
            [self::ADA => ['Professor', 'Dean'], self::GRACE => ['Lecturer', 'Researcher', 'Advisor']],
            $this->contractsPerProfile($this->renderHomePage()),
        );
    }

    /**
     * Without "only valid" nothing rendered depends on the date, and the page keeps the
     * lifetime it has without the option. On TYPO3 v14 that is `config.cache_period`, two
     * days here.
     */
    #[Test]
    #[Group('not-core-13')]
    public function withoutOnlyValidThePageKeepsTheConfiguredLifetime(): void
    {
        $this->renderListWithAContractEndingTodayWithoutOnlyValid();

        $this->assertSame((int)$GLOBALS['EXEC_TIME'] + 172800, $this->pageCacheEntry()['expires']);
    }

    /**
     * The same on TYPO3 v13, where that lifetime is 24 hours whatever `config.cache_period`
     * says: Extbase adds a cache tag for every record it loads, with the lifetime of
     * `CacheLifetimeCalculator::defaultCacheTimeout`, which is 86400 seconds there and a year
     * on v14.
     */
    #[Test]
    #[Group('not-core-14')]
    public function withoutOnlyValidThePageKeepsTheLifetimeOfItsExtbaseRecords(): void
    {
        $this->renderListWithAContractEndingTodayWithoutOnlyValid();

        $this->assertSame((int)$GLOBALS['EXEC_TIME'] + 86400, $this->pageCacheEntry()['expires']);
    }

    /**
     * A contract that ends today is listed, which would limit the lifetime to midnight if
     * "only valid" applied.
     */
    private function renderListWithAContractEndingTodayWithoutOnlyValid(): void
    {
        $this->getConnectionPool()->getConnectionForTable('tx_academicpersons_domain_model_contract')
            ->update('tx_academicpersons_domain_model_contract', ['valid_to' => $this->todayMidnight()->getTimestamp()], ['uid' => 2]);
        $this->addContentElement('academicpersons_list', ['contracts.display' => 'first']);

        $this->renderHomePage();
    }
}
