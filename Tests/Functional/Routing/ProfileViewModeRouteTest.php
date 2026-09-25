<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Routing;

use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Site\SiteFinder;

/**
 * The `/view-mode/{viewMode}` routes of the list and list-and-detail enhancers, next to the
 * routes those enhancers had before.
 *
 * The site imports the shipped files the way the documentation tells an integrator to,
 * through `imports`, and confines each enhancer to the page of its plugin with
 * `limitToPages` - see {@see ProfileRouteEnhancerTest} for why that is required. Importing
 * rather than inlining is also what lets a test extend the shipped map of view modes from
 * the site configuration, which is the extension point for a project mode.
 *
 * The shipped templates render, so a resolved URL shows whether the mode arrived: a table,
 * the tiles or the fixture partial of the project mode "contact". Both list elements offer
 * the switch, show one profile per page and the letter navigation; the profiles are Adams
 * under A, and Baker and Brown under B.
 */
final class ProfileViewModeRouteTest extends AbstractAcademicPersonsTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    private const LIST_PAGE = 2;
    private const LIST_AND_DETAIL_PAGE = 3;
    private const DETAIL_PAGE = 4;

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration();
        $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
        $this->addTestExtensionsToLoad('tests/test-profile-view-modes');
        parent::setUp();
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    /**
     * @param bool $contactViewMode Whether the site allows the project mode "contact".
     * @param array<string, mixed> $routeEnhancers Merged over the imported enhancers by the
     *        site configuration loader, as an integrator's own `routeEnhancers` are.
     */
    private function setUpTestCase(bool $contactViewMode = false, array $routeEnhancers = []): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ProfileViewModeRoute/records.csv');
        $constants = [
            'EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript',
            'EXT:academic_persons/Configuration/TypoScript/Default/constants.typoscript',
        ];
        $setup = [
            'EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript',
            'EXT:academic_persons/Configuration/TypoScript/Default/setup.typoscript',
            'EXT:academic_persons/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/Rendering.typoscript',
        ];
        if ($contactViewMode) {
            $constants[] = 'EXT:test_profile_view_modes/Configuration/TypoScript/constants.typoscript';
            $setup[] = 'EXT:test_profile_view_modes/Configuration/TypoScript/setup.typoscript';
        }
        $this->setUpFrontendRootPage(
            pageId: 1,
            typoScriptFiles: [
                'constants' => $constants,
                'setup' => $setup,
            ],
        );

        $this->writeSiteConfiguration(
            identifier: 'acme',
            site: $this->buildSiteConfiguration(
                rootPageId: 1,
                base: self::FRONTEND_PLUGIN_TEST_BASE,
                additionalRootConfiguration: [
                    'imports' => [
                        ['resource' => 'EXT:academic_persons/Configuration/Routes/List.yaml'],
                        ['resource' => 'EXT:academic_persons/Configuration/Routes/ListAndDetail.yaml'],
                        ['resource' => 'EXT:academic_persons/Configuration/Routes/Detail.yaml'],
                    ],
                    'routeEnhancers' => array_replace_recursive(
                        [
                            'ProfileListPlugin' => ['limitToPages' => [self::LIST_PAGE]],
                            'ProfileListAndDetailPlugin' => ['limitToPages' => [self::LIST_AND_DETAIL_PAGE]],
                            'ProfileDetailPlugin' => ['limitToPages' => [self::DETAIL_PAGE]],
                        ],
                        $routeEnhancers,
                    ),
                ],
            ),
            languages: [
                $this->buildDefaultLanguageConfiguration(
                    identifier: 'EN',
                    base: '/',
                ),
            ],
        );
    }

    /**
     * @param array<string, mixed> $demand
     */
    private function listUri(int $pageId, string $pluginNamespace, array $demand): string
    {
        return (string)$this->get(SiteFinder::class)
            ->getSiteByIdentifier('acme')
            ->getRouter()
            ->generateUri($pageId, [$pluginNamespace => ['demand' => $demand]]);
    }

    private function render(string $uri): \DOMXPath
    {
        $document = new \DOMDocument();
        $document->loadHTML('<?xml encoding="utf-8" ?>' . $this->renderFrontendPage($uri), LIBXML_NOERROR);

        return new \DOMXPath($document);
    }

    /**
     * @return list<string>
     */
    private function texts(\DOMXPath $xpath, string $query): array
    {
        $nodes = $xpath->query($query);
        $this->assertInstanceOf(\DOMNodeList::class, $nodes);
        $texts = [];
        foreach ($nodes as $node) {
            $texts[] = trim((string)preg_replace('#\s+#u', ' ', $node->textContent));
        }

        return $texts;
    }

    /**
     * The names of the table rows; empty when no table renders.
     *
     * @return list<string>
     */
    private function tableNames(\DOMXPath $xpath): array
    {
        return $this->texts($xpath, '//table/tbody/tr/th');
    }

    /**
     * The names of the tiles; empty when no tile renders.
     *
     * @return list<string>
     */
    private function tileNames(\DOMXPath $xpath): array
    {
        return $this->texts($xpath, "//*[contains(concat(' ', normalize-space(@class), ' '), ' card-title ')]");
    }

    /**
     * @return list<string>
     */
    private function contactNames(\DOMXPath $xpath): array
    {
        return $this->texts($xpath, "//ul[contains(concat(' ', normalize-space(@class), ' '), ' test-contact-cards ')]/li");
    }

    private function switchHref(\DOMXPath $xpath, string $label): string
    {
        $links = $xpath->query(sprintf(
            "//nav[contains(concat(' ', normalize-space(@class), ' '), ' academic-persons-view-mode-switch ')]//a[normalize-space(.)='%s']",
            $label,
        ));
        $this->assertInstanceOf(\DOMNodeList::class, $links);
        $this->assertSame(1, $links->length, sprintf('Exactly one switch link "%s" is expected.', $label));
        $link = $links->item(0);
        $this->assertInstanceOf(\DOMElement::class, $link);

        return $this->absolute($link->getAttribute('href'));
    }

    /**
     * The templates link with the path, which is the site's base for these assertions.
     */
    private function absolute(string $href): string
    {
        return str_starts_with($href, '/') ? rtrim(self::FRONTEND_PLUGIN_TEST_BASE, '/') . $href : $href;
    }

    /**
     * @return \Generator<string, array{0: int, 1: string, 2: string}>
     */
    public static function listPluginsDataProvider(): \Generator
    {
        yield 'list' => [self::LIST_PAGE, 'tx_academicpersons_list', 'https://www.acme.com/persons'];
        yield 'list and detail' => [self::LIST_AND_DETAIL_PAGE, 'tx_academicpersons_listanddetail', 'https://www.acme.com/team'];
    }

    /**
     * A mode is a speaking segment, alone, with a page and with a letter. The default mode
     * travels as no mode at all, so its URLs are the ones the list always had.
     */
    #[DataProvider('listPluginsDataProvider')]
    #[Test]
    public function theViewModeRoutesAreGenerated(int $pageId, string $pluginNamespace, string $listUri): void
    {
        $this->setUpTestCase();

        $this->assertSame($listUri . '/view-mode/table', $this->listUri($pageId, $pluginNamespace, ['viewMode' => 'table']));
        $this->assertSame($listUri . '/view-mode/list', $this->listUri($pageId, $pluginNamespace, ['viewMode' => 'list']));
        $this->assertSame($listUri . '/view-mode/table/page-2', $this->listUri($pageId, $pluginNamespace, ['viewMode' => 'table', 'currentPage' => 2]));
        $this->assertSame($listUri . '/view-mode/table/b', $this->listUri($pageId, $pluginNamespace, ['viewMode' => 'table', 'alphabetFilter' => 'b']));
        $this->assertSame($listUri . '/page-2', $this->listUri($pageId, $pluginNamespace, ['currentPage' => 2]));
        $this->assertSame($listUri . '/b', $this->listUri($pageId, $pluginNamespace, ['alphabetFilter' => 'b']));
    }

    /**
     * Each of the three routes resolves to the list in that mode, and the list's own links
     * are the speaking URLs: the switch, and the page link of the table.
     */
    #[DataProvider('listPluginsDataProvider')]
    #[Test]
    public function theViewModeRoutesResolve(int $pageId, string $pluginNamespace, string $listUri): void
    {
        $this->setUpTestCase();

        $tiles = $this->render($listUri);
        $this->assertSame(['Anna Adams'], $this->tileNames($tiles));
        $this->assertSame($listUri . '/view-mode/table', $this->switchHref($tiles, 'Table'));

        $table = $this->render($listUri . '/view-mode/table');
        $this->assertSame(['Anna Adams'], $this->tableNames($table));
        $this->assertSame($listUri, $this->switchHref($table, 'Tiles'));
        $pageLinks = $table->query("//nav[contains(concat(' ', normalize-space(@class), ' '), ' academic-persons-list__pagination ')]//a[normalize-space(./span[1])='2']/@href");
        $this->assertInstanceOf(\DOMNodeList::class, $pageLinks);
        $this->assertSame($listUri . '/view-mode/table/page-2', $this->absolute((string)$pageLinks->item(0)?->nodeValue));

        $this->assertSame(['Ben Baker'], $this->tableNames($this->render($listUri . '/view-mode/table/page-2')));
        $this->assertSame(['Ben Baker', 'Bea Brown'], $this->tableNames($this->render($listUri . '/view-mode/table/b')));
        $this->assertSame(['Anna Adams'], $this->tileNames($this->render($listUri . '/view-mode/list')));
    }

    /**
     * A segment value the map does not hold matches no route, and the page does not exist.
     */
    #[DataProvider('listPluginsDataProvider')]
    #[Test]
    public function aViewModeSegmentTheRouteDoesNotKnowIsNotFound(int $pageId, string $pluginNamespace, string $listUri): void
    {
        $this->setUpTestCase();

        $this->assertSame(404, $this->requestFrontendPage($listUri . '/view-mode/slider')->getStatusCode());
        $this->assertSame(404, $this->requestFrontendPage($listUri . '/view-mode/slider/page-2')->getStatusCode());
    }

    /**
     * An allowed mode the shipped map does not hold cannot be a segment, so its links keep
     * the mode as a query argument, protected by a cHash - and still work.
     */
    #[DataProvider('listPluginsDataProvider')]
    #[Test]
    public function anAllowedModeMissingFromTheMapIsAQueryArgument(int $pageId, string $pluginNamespace, string $listUri): void
    {
        $this->setUpTestCase(contactViewMode: true);

        $href = $this->switchHref($this->render($listUri), 'contact');

        $this->assertStringStartsWith($listUri . '?', $href);
        parse_str((string)parse_url($href, PHP_URL_QUERY), $query);
        $this->assertSame('contact', $query[$pluginNamespace]['demand']['viewMode'] ?? null);
        $this->assertArrayHasKey('cHash', $query);
        $this->assertSame(['Anna Adams'], $this->contactNames($this->render($href)));
    }

    /**
     * A site adds its mode to the map of the shipped enhancer key in its own configuration,
     * next to the import, without copying the enhancer. The segment is the key of the map,
     * and need not be the name of the mode.
     */
    #[DataProvider('listPluginsDataProvider')]
    #[Test]
    public function aSiteAddsItsModeToTheMapOfTheShippedEnhancer(int $pageId, string $pluginNamespace, string $listUri): void
    {
        $map = ['aspects' => ['viewMode' => ['map' => ['contact-cards' => 'contact']]]];
        $this->setUpTestCase(
            contactViewMode: true,
            routeEnhancers: ['ProfileListPlugin' => $map, 'ProfileListAndDetailPlugin' => $map],
        );

        $href = $this->switchHref($this->render($listUri), 'contact');

        $this->assertSame($listUri . '/view-mode/contact-cards', $href);
        $this->assertSame(['Anna Adams'], $this->contactNames($this->render($href)));
        $this->assertSame(['Ben Baker'], $this->contactNames($this->render($listUri . '/view-mode/contact-cards/page-2')));
        // The shipped modes are still in the map.
        $this->assertSame(['Anna Adams'], $this->tableNames($this->render($listUri . '/view-mode/table')));
    }

    /**
     * The routes the two enhancers had keep resolving next to the new ones: the page, the
     * letter and - for list and detail - the profile, whose route matches any path.
     */
    #[Test]
    public function theExistingRoutesStillResolve(): void
    {
        $this->setUpTestCase();

        foreach (['https://www.acme.com/persons', 'https://www.acme.com/team'] as $listUri) {
            $this->assertSame(['Ben Baker'], $this->tileNames($this->render($listUri . '/page-2')));
            $this->assertSame(['Ben Baker', 'Bea Brown'], $this->tileNames($this->render($listUri . '/b')));
        }
        $detail = (string)$this->requestFrontendPage('https://www.acme.com/team/ben-baker')->getBody();
        $this->assertStringContainsString('Baker', $detail);
        $this->assertStringNotContainsString('Adams', $detail);
    }
}
