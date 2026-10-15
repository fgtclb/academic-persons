<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Plugins;

use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;

/**
 * Which number of results per page and of page links a paginated profile list uses (ACE-913).
 *
 * An element saved with an empty "Results per Page" used to replace the site setting with
 * the empty value, and the list handed 0 to the paginator, which throws. An empty or `0`
 * field takes the site value now, a value below 1 from any source falls back to 10 results
 * and 5 links, and the shipped default is 10 results per page.
 *
 * The fixture holds twelve profiles. The list is neither grouped nor sorted, so it renders
 * them in uid order, and the pagination partial of `EXT:test_plugin_templates` prints the
 * page count and one `PAGELINK` line per link of `georgringer/numbered-pagination`.
 */
final class AcademicPersonsListPaginationSettingsTest extends AbstractAcademicPersonsTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    private const ALL_PROFILES = '12,11,10,9,8,7,6,5,4,3,2,1';

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration();
        $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
        $this->addTestExtensionsToLoad('georgringer/numbered-pagination', 'tests/plugin-templates');
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsListPaginationSettings/profiles.csv');
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    /**
     * @return \Generator<string, array{
     *     cType: string,
     *     elementSettings: array<string, string>,
     *     siteConstants: array<string, string>,
     *     expectedUids: list<int>,
     *     expectedPages: int,
     * }>
     */
    public static function resultsPerPage(): \Generator
    {
        yield 'empty field in a list takes the site value' => [
            'cType' => 'academicpersons_list',
            'elementSettings' => ['settings.pagination.resultsPerPage' => ''],
            'siteConstants' => ['resultsPerPage' => '2'],
            'expectedUids' => [1, 2],
            'expectedPages' => 6,
        ];
        yield 'empty field in a list and details element takes the site value' => [
            'cType' => 'academicpersons_listanddetail',
            'elementSettings' => ['settings.pagination.resultsPerPage' => ''],
            'siteConstants' => ['resultsPerPage' => '2'],
            'expectedUids' => [1, 2],
            'expectedPages' => 6,
        ];
        yield 'empty field in a manual selection takes the site value' => [
            'cType' => 'academicpersons_list',
            'elementSettings' => [
                'settings.pagination.resultsPerPage' => '',
                'settings.demand.profileList' => self::ALL_PROFILES,
            ],
            'siteConstants' => ['resultsPerPage' => '2'],
            'expectedUids' => [12, 11],
            'expectedPages' => 6,
        ];
        yield 'field saved as zero takes the site value' => [
            'cType' => 'academicpersons_list',
            'elementSettings' => ['settings.pagination.resultsPerPage' => '0'],
            'siteConstants' => ['resultsPerPage' => '2'],
            'expectedUids' => [1, 2],
            'expectedPages' => 6,
        ];
        yield 'the value of the element wins over the site value' => [
            'cType' => 'academicpersons_list',
            'elementSettings' => ['settings.pagination.resultsPerPage' => '3'],
            'siteConstants' => ['resultsPerPage' => '2'],
            'expectedUids' => [1, 2, 3],
            'expectedPages' => 4,
        ];
        yield 'site value zero falls back to ten' => [
            'cType' => 'academicpersons_list',
            'elementSettings' => ['settings.pagination.resultsPerPage' => ''],
            'siteConstants' => ['resultsPerPage' => '0'],
            'expectedUids' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
            'expectedPages' => 2,
        ];
        yield 'empty site value falls back to ten' => [
            'cType' => 'academicpersons_list',
            'elementSettings' => ['settings.pagination.resultsPerPage' => ''],
            'siteConstants' => ['resultsPerPage' => ''],
            'expectedUids' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
            'expectedPages' => 2,
        ];
        yield 'negative value of the element falls back to ten' => [
            'cType' => 'academicpersons_list',
            'elementSettings' => ['settings.pagination.resultsPerPage' => '-3'],
            'siteConstants' => ['resultsPerPage' => '2'],
            'expectedUids' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
            'expectedPages' => 2,
        ];
        yield 'manual selection with a value below one falls back to ten' => [
            'cType' => 'academicpersons_list',
            'elementSettings' => [
                'settings.pagination.resultsPerPage' => '',
                'settings.demand.profileList' => self::ALL_PROFILES,
            ],
            'siteConstants' => ['resultsPerPage' => '0'],
            'expectedUids' => [12, 11, 10, 9, 8, 7, 6, 5, 4, 3],
            'expectedPages' => 2,
        ];
        yield 'empty field on a site with the shipped settings shows ten' => [
            'cType' => 'academicpersons_list',
            'elementSettings' => ['settings.pagination.resultsPerPage' => ''],
            'siteConstants' => [],
            'expectedUids' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
            'expectedPages' => 2,
        ];
        yield 'element without the field on a site with the shipped settings shows ten' => [
            'cType' => 'academicpersons_listanddetail',
            'elementSettings' => [],
            'siteConstants' => [],
            'expectedUids' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
            'expectedPages' => 2,
        ];
    }

    /**
     * @param array<string, string> $elementSettings
     * @param array<string, string> $siteConstants
     * @param list<int> $expectedUids
     */
    #[DataProvider('resultsPerPage')]
    #[Test]
    public function theListShowsTheNumberOfProfilesThatApplies(
        string $cType,
        array $elementSettings,
        array $siteConstants,
        array $expectedUids,
        int $expectedPages,
    ): void {
        $this->setUpSite($siteConstants);
        $this->addPaginatedElement($cType, $elementSettings);

        $content = $this->renderFrontendPage('https://www.acme.com/home');

        $this->assertSame($expectedUids, $this->listedUids($content));
        $this->assertStringContainsString(sprintf('PAGINATION: page 1 of %d', $expectedPages), $content);
    }

    /**
     * @return \Generator<string, array{
     *     elementSettings: array<string, string>,
     *     siteConstants: array<string, string>,
     *     expectedLinks: list<int>,
     * }>
     */
    public static function numberOfLinks(): \Generator
    {
        yield 'empty field takes the site value' => [
            'elementSettings' => [
                'settings.pagination.resultsPerPage' => '1',
                'settings.pagination.numberOfLinks' => '',
            ],
            'siteConstants' => ['numberOfLinks' => '3'],
            'expectedLinks' => [1, 2, 3],
        ];
        yield 'field saved as zero takes the site value' => [
            'elementSettings' => [
                'settings.pagination.resultsPerPage' => '1',
                'settings.pagination.numberOfLinks' => '0',
            ],
            'siteConstants' => ['numberOfLinks' => '3'],
            'expectedLinks' => [1, 2, 3],
        ];
        yield 'site value zero falls back to five' => [
            'elementSettings' => [
                'settings.pagination.resultsPerPage' => '1',
                'settings.pagination.numberOfLinks' => '',
            ],
            'siteConstants' => ['numberOfLinks' => '0'],
            'expectedLinks' => [1, 2, 3, 4, 5],
        ];
        yield 'negative value of the element falls back to five' => [
            'elementSettings' => [
                'settings.pagination.resultsPerPage' => '1',
                'settings.pagination.numberOfLinks' => '-2',
            ],
            'siteConstants' => ['numberOfLinks' => '3'],
            'expectedLinks' => [1, 2, 3, 4, 5],
        ];
    }

    /**
     * @param array<string, string> $elementSettings
     * @param array<string, string> $siteConstants
     * @param list<int> $expectedLinks
     */
    #[DataProvider('numberOfLinks')]
    #[Test]
    public function thePageNavigationLinksTheNumberOfPagesThatApplies(
        array $elementSettings,
        array $siteConstants,
        array $expectedLinks,
    ): void {
        $this->setUpSite($siteConstants);
        $this->addPaginatedElement('academicpersons_list', $elementSettings);

        $content = $this->renderFrontendPage('https://www.acme.com/home');

        $this->assertStringContainsString('PAGINATION: page 1 of 12', $content);
        $this->assertSame($expectedLinks, $this->linkedPages($content));
    }

    /**
     * The root page with the shipped TypoScript, the constants the plugin tests pin, and the
     * given pagination constants after them, as a site would set them.
     *
     * @param array<string, string> $paginationConstants
     */
    private function setUpSite(array $paginationConstants): void
    {
        $this->setUpFrontendRootPage(
            pageId: 1,
            typoScriptFiles: [
                'constants' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript',
                    'EXT:academic_persons/Configuration/TypoScript/Default/constants.typoscript',
                    'EXT:test_plugin_templates/Configuration/TypoScript/constants.typoscript',
                    'EXT:academic_persons/Tests/Functional/Plugins/Fixtures/TypoScript/Constants/PluginConfiguration.typoscript',
                ],
                'setup' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript',
                    'EXT:academic_persons/Configuration/TypoScript/Default/setup.typoscript',
                    'EXT:test_plugin_templates/Configuration/TypoScript/setup.typoscript',
                    'EXT:academic_persons/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/Rendering.typoscript',
                ],
            ],
        );
        if ($paginationConstants !== []) {
            $connection = $this->getConnectionPool()->getConnectionForTable('sys_template');
            $constants = (string)$connection->select(['constants'], 'sys_template', ['pid' => 1])->fetchOne();
            foreach ($paginationConstants as $name => $value) {
                $constants .= sprintf('plugin.tx_academicpersons.pagination.%s = %s', $name, $value) . LF;
            }
            $connection->update('sys_template', ['constants' => $constants], ['pid' => 1]);
        }
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
        ]);
    }

    /**
     * A list element on the home page with pagination switched on, as the backend form
     * stores it: a field the editor left empty is stored as an empty value.
     *
     * @param array<string, string> $settings
     */
    private function addPaginatedElement(string $cType, array $settings): void
    {
        $fields = '';
        foreach (['settings.paginationEnabled' => '1', 'settings.fallbackForNonTranslated' => '0', ...$settings] as $name => $value) {
            $fields .= sprintf('<field index="%s"><value index="vDEF">%s</value></field>', $name, $value);
        }
        $this->getConnectionPool()->getConnectionForTable('tt_content')->insert('tt_content', [
            'uid' => 1,
            'pid' => 2,
            'CType' => $cType,
            'header' => 'Profiles',
            'pi_flexform' => '<?xml version="1.0" encoding="utf-8" standalone="yes" ?><T3FlexForms><data>'
                . '<sheet index="sDEF"><language index="lDEF">' . $fields . '</language></sheet>'
                . '</data></T3FlexForms>',
        ]);
    }

    /**
     * @return list<int>
     */
    private function listedUids(string $content): array
    {
        preg_match_all('/#\d+\((\d+)\): /', $content, $matches);
        return array_map(intval(...), $matches[1]);
    }

    /**
     * @return list<int>
     */
    private function linkedPages(string $content): array
    {
        preg_match_all('/PAGELINK (\d+): /', $content, $matches);
        return array_map(intval(...), $matches[1]);
    }
}
