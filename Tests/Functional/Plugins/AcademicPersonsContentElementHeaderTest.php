<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Plugins;

use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\ContentElementHeaderAssertionTrait;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;

/**
 * The header of a profile list, card, detail, selected profiles or selected contracts
 * content element renders once.
 *
 * By default the content element layout renders it and the plugins do not. A site whose
 * layout renders no header switches `renderContentElementHeader` on, and the templates then
 * render the `EXT:fluid_styled_content` `Header/All` partial themselves - also for a
 * selection that is empty, where the action returns before it looks for profiles.
 *
 * The switched on cases guard the `record` view variable as well: on TYPO3 v14 the header
 * partial renders the header through it, and fails without it.
 *
 * The shipped templates render here. The list, the card and the two selections sit on one
 * page, each content element counted inside its own frame; the detail renders the shipped
 * public profile of Max Müllermann, whose detail link carries a cHash.
 */
final class AcademicPersonsContentElementHeaderTest extends AbstractAcademicPersonsTestCase
{
    use ContentElementHeaderAssertionTrait;
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    private const HEADER = 'Our professors';
    private const SUBHEADER = 'Faculty of applied sciences';
    private const RENDER_HEADER_CONSTANTS = 'EXT:academic_persons/Tests/Functional/Plugins/Fixtures/TypoScript/Constants/RenderContentElementHeader.typoscript';
    private const HEADER_PARTIAL_OVERRIDE_SETUP = 'EXT:academic_persons/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/HeaderPartialOverride.typoscript';
    private const LAYOUT_WITHOUT_HEADER_SETUP = 'EXT:academic_persons/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/LayoutWithoutHeader.typoscript';

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration([
            'FE' => [
                // The configuration the cHash of the detail link below was calculated with.
                'cacheHash' => [
                    'requireCacheHashPresenceParameters' => ['value', 'testing[value]', 'tx_testing_link[value]'],
                    'excludedParameters' => ['L', 'tx_testing_link[excludedValue]'],
                    'enforceValidation' => true,
                ],
            ],
        ]);
        $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
        parent::setUp();
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    /**
     * @param list<string> $additionalConstantFiles
     * @param list<string> $additionalSetupFiles
     */
    private function setUpTestCase(string $dataSet, array $additionalConstantFiles = [], array $additionalSetupFiles = []): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/' . $dataSet . '.csv');
        $this->setUpFrontendRootPage(
            pageId: 1,
            typoScriptFiles: [
                'constants' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript',
                    'EXT:academic_persons/Configuration/TypoScript/Default/constants.typoscript',
                    'EXT:academic_persons/Tests/Functional/Plugins/Fixtures/TypoScript/Constants/DetailPage.typoscript',
                    ...$additionalConstantFiles,
                ],
                'setup' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript',
                    'EXT:academic_persons/Configuration/TypoScript/Default/setup.typoscript',
                    'EXT:academic_persons/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/Rendering.typoscript',
                    ...$additionalSetupFiles,
                ],
            ],
        );
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
        ]);
    }

    private function setContentElementHeader(int $uid, int $headerLayout): void
    {
        $this->getConnectionPool()
            ->getConnectionForTable('tt_content')
            ->update(
                'tt_content',
                ['header' => self::HEADER, 'subheader' => self::SUBHEADER, 'header_layout' => $headerLayout],
                ['uid' => $uid],
            );
    }

    /**
     * Every view, with the data set, the page, the content element and the class of the
     * element the template wraps its output in.
     *
     * @return array<string, array{string, string, int, string}>
     */
    private static function views(): array
    {
        return [
            'list' => ['AcademicPersonsContentElementHeader/allPlugins', 'https://www.acme.com/home', 1, 'academic-persons-list'],
            'card' => ['AcademicPersonsContentElementHeader/allPlugins', 'https://www.acme.com/home', 2, 'academic-persons-card'],
            'selected profiles' => ['AcademicPersonsContentElementHeader/allPlugins', 'https://www.acme.com/home', 3, 'academic-persons-profiles'],
            'selected contracts' => ['AcademicPersonsContentElementHeader/allPlugins', 'https://www.acme.com/home', 4, 'academic-persons-contracts'],
            'selected profiles, nothing selected' => ['AcademicPersonsContentElementHeader/allPlugins', 'https://www.acme.com/home', 5, 'academic-persons-profiles'],
            'selected contracts, nothing selected' => ['AcademicPersonsContentElementHeader/allPlugins', 'https://www.acme.com/home', 6, 'academic-persons-contracts'],
            'detail' => [
                'AcademicPersonsPublicProfilePlugin/shippedLayout',
                'https://www.acme.com/home?' . http_build_query([
                    'tx_academicpersons_detail' => [
                        'controller' => 'Profile',
                        'action' => 'detail',
                        'profile' => 1,
                    ],
                    'cHash' => '13c8ec3ab2a317651a40bd164df8a366',
                ]),
                1,
                'academic-persons-detail',
            ],
        ];
    }

    /**
     * Every view with the header layouts "Default", 2 and "Hidden", and the number of times
     * the header and the subheader have to render: "Default" is the layout the header
     * partial resolves through a setting, and the one a plugin rendering it without that
     * setting leaves an empty `<header>` for.
     *
     * @return \Generator<string, array{string, string, int, string, int, int}>
     */
    public static function viewsAndHeaderLayouts(): \Generator
    {
        $headerLayouts = [
            'header layout "Default"' => [0, 1],
            'header layout 2' => [2, 1],
            'header layout "Hidden"' => [100, 0],
        ];
        foreach (self::views() as $view => [$dataSet, $url, $contentElement, $wrapperClass]) {
            foreach ($headerLayouts as $name => [$headerLayout, $expectedHeadings]) {
                yield $view . ', ' . $name => [$dataSet, $url, $contentElement, $wrapperClass, $headerLayout, $expectedHeadings];
            }
        }
    }

    #[Test]
    #[DataProvider('viewsAndHeaderLayouts')]
    public function aPluginLeavesTheContentElementHeaderToTheLayout(
        string $dataSet,
        string $url,
        int $contentElement,
        string $wrapperClass,
        int $headerLayout,
        int $expectedHeadings,
    ): void {
        $this->setUpTestCase($dataSet);
        $this->setContentElementHeader($contentElement, $headerLayout);

        $content = $this->renderFrontendPage($url);
        $wrapper = sprintf(
            '//*[@id = "c%d"]//*[contains(concat(" ", normalize-space(@class), " "), " %s ")]',
            $contentElement,
            $wrapperClass,
        );
        $this->assertSame($expectedHeadings, $this->countHeadingsReading($content, self::HEADER));
        $this->assertSame($expectedHeadings, $this->countHeadingsReading($content, self::SUBHEADER));
        $this->assertSame(0, $this->countHeadingsReading($content, self::HEADER, $wrapper));
        $this->assertSame(0, $this->countHeadingsReading($content, self::SUBHEADER, $wrapper));
    }

    #[Test]
    #[DataProvider('viewsAndHeaderLayouts')]
    public function aPluginRendersTheContentElementHeaderWhenSwitchedOn(
        string $dataSet,
        string $url,
        int $contentElement,
        string $wrapperClass,
        int $headerLayout,
        int $expectedHeadings,
    ): void {
        $this->setUpTestCase($dataSet, [self::RENDER_HEADER_CONSTANTS], [self::LAYOUT_WITHOUT_HEADER_SETUP]);
        $this->setContentElementHeader($contentElement, $headerLayout);

        $content = $this->renderFrontendPage($url);
        $frame = sprintf('//*[@id = "c%d"]', $contentElement);
        // The fixture layout renders no header, so a heading inside the frame of the element
        // comes from the template. The first two assertions prove the fixture layout and the
        // template of the view rendered.
        $this->assertSame(1, $this->countContentElementHeaderNodes($content, $frame . '[contains(concat(" ", normalize-space(@class), " "), " frame-without-header ")]'));
        $this->assertSame(1, $this->countContentElementHeaderNodes(
            $content,
            sprintf('%s//*[contains(concat(" ", normalize-space(@class), " "), " %s ")]', $frame, $wrapperClass),
        ));
        $this->assertSame($expectedHeadings, $this->countHeadingsReading($content, self::HEADER));
        $this->assertSame($expectedHeadings, $this->countHeadingsReading($content, self::HEADER, $frame));
        $this->assertSame($expectedHeadings, $this->countHeadingsReading($content, self::SUBHEADER));
        $this->assertSame($expectedHeadings, $this->countHeadingsReading($content, self::SUBHEADER, $frame));
    }

    /**
     * A site package that renders the header its own way registers its own `Header/All`
     * above the paths of the extension, and that one renders instead of the partial of
     * EXT:fluid_styled_content, whose path sorts below every other one.
     */
    #[Test]
    public function aHeaderPartialOfTheSitePackageWinsOverTheShippedOne(): void
    {
        $this->setUpTestCase('AcademicPersonsContentElementHeader/allPlugins', [self::RENDER_HEADER_CONSTANTS], [self::LAYOUT_WITHOUT_HEADER_SETUP, self::HEADER_PARTIAL_OVERRIDE_SETUP]);
        $this->setContentElementHeader(1, 2);

        $content = $this->renderFrontendPage('https://www.acme.com/home');
        $this->assertSame(0, $this->countHeadingsReading($content, self::HEADER));
        $this->assertSame(1, $this->countContentElementHeaderNodes(
            $content,
            '//*[@id = "c1"]//p[@class = "site-package-header"][normalize-space() = "' . self::HEADER . '"]',
        ));
    }
}
