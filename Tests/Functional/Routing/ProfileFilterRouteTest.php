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
 * The filter routes of the list and list-and-detail enhancers: a function type, an
 * organisational unit or both, each alone, with a page, with a letter, and each of these
 * with the view mode segment - eighteen routes per enhancer.
 *
 * The site imports the shipped files and confines each enhancer to its page, as
 * {@see ProfileViewModeRouteTest} does. Both list elements offer both filters, the view
 * mode switch and the letter navigation, and show one profile per page:
 *
 * - Adams, Baker: Professor in Biology
 * - Brown: Professor in Physics
 * - Clark: Lecturer in Biology
 * - Dunn: Assistant in Physics - the assistant has no slug
 * - Evans: Dean in Physics - the dean is stored in a folder of another site
 * - Fox: Lecturer in Chemistry - the slug of Chemistry has a slash
 * - Green: Lecturer in Mathematics - Mathematics has no slug
 *
 * The professor is translated into German as "Professorin", and the list page as
 * "/personen".
 */
final class ProfileFilterRouteTest extends AbstractAcademicPersonsTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
        'DE' => ['id' => 1, 'title' => 'Deutsch', 'locale' => 'de_DE.UTF8', 'iso' => 'de', 'hrefLang' => 'de-DE', 'direction' => ''],
    ];

    private const LIST_PAGE = 2;
    private const LIST_AND_DETAIL_PAGE = 3;
    private const DETAIL_PAGE = 4;

    private const PROFESSOR = 1;
    private const ASSISTANT = 3;
    private const DEAN = 5;
    private const BIOLOGY = 2;
    private const CHEMISTRY = 3;
    private const MATHEMATICS = 4;

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration();
        $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ProfileFilterRoute/records.csv');
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
                    'EXT:academic_persons/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/Rendering.typoscript',
                ],
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
                    'routeEnhancers' => [
                        'ProfileListPlugin' => ['limitToPages' => [self::LIST_PAGE]],
                        'ProfileListAndDetailPlugin' => ['limitToPages' => [self::LIST_AND_DETAIL_PAGE]],
                        'ProfileDetailPlugin' => ['limitToPages' => [self::DETAIL_PAGE]],
                    ],
                ],
            ),
            languages: [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildLanguageConfiguration('DE', '/de/', ['EN'], 'fallback'),
            ],
        );
        // The site the folder of the dean belongs to. Without it the folder is in no site,
        // and the core treats such a record as part of every site.
        $this->writeSiteConfiguration(
            identifier: 'other',
            site: $this->buildSiteConfiguration(rootPageId: 200, base: 'https://www.other.com/'),
            languages: [$this->buildDefaultLanguageConfiguration('EN', '/')],
        );
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    /**
     * @param array<string, mixed> $demand
     * @param array<string, mixed> $parameters
     */
    private function listUri(int $pageId, string $pluginNamespace, array $demand, array $parameters = []): string
    {
        return (string)$this->get(SiteFinder::class)
            ->getSiteByIdentifier('acme')
            ->getRouter()
            ->generateUri($pageId, array_merge($parameters, [$pluginNamespace => ['demand' => $demand]]));
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
     * The names the list renders, and the view mode it renders them in.
     *
     * @return array{0: string, 1: list<string>}
     */
    private function listed(string $uri): array
    {
        $xpath = $this->render($uri);
        $table = $this->texts($xpath, '//table/tbody/tr/th');
        if ($table !== []) {
            return ['table', $table];
        }

        return ['list', $this->texts($xpath, "//*[contains(concat(' ', normalize-space(@class), ' '), ' card-title ')]")];
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
     * Every filter route: its demand, its path below the list, and the names of the list it
     * resolves to. The professors are Adams, Baker and Brown, Biology has Adams, Baker and
     * Clark, and one profile per page. A letter switches the pagination off.
     *
     * @return array<string, array{0: array<string, mixed>, 1: string, 2: list<string>}>
     */
    private static function filterRoutes(): array
    {
        $filters = [
            'function type' => [['functionTypeFilter' => self::PROFESSOR], '/function/professor', ['Anna Adams', 'Ben Baker', 'Bea Brown']],
            'unit' => [['organisationalUnitFilter' => self::BIOLOGY], '/unit/biology', ['Anna Adams', 'Ben Baker', 'Carl Clark']],
            'both' => [['functionTypeFilter' => self::PROFESSOR, 'organisationalUnitFilter' => self::BIOLOGY], '/function/professor/unit/biology', ['Anna Adams', 'Ben Baker']],
        ];
        $routes = [];
        foreach ($filters as $name => [$demand, $path, $names]) {
            foreach (['' => [], ', table' => ['viewMode' => 'table']] as $modeName => $mode) {
                $modePath = $mode === [] ? '' : '/view-mode/table';
                $routes[$name . $modeName] = [$demand + $mode, $path . $modePath, [$names[0]]];
                $routes[$name . $modeName . ', page 2'] = [$demand + $mode + ['currentPage' => 2], $path . $modePath . '/page-2', [$names[1]]];
                $routes[$name . $modeName . ', letter b'] = [
                    $demand + $mode + ['alphabetFilter' => 'b'],
                    $path . $modePath . '/b',
                    array_values(array_filter($names, static fn(string $profile): bool => str_contains($profile, ' B'))),
                ];
            }
        }

        return $routes;
    }

    #[DataProvider('listPluginsDataProvider')]
    #[Test]
    public function theFilterRoutesAreGenerated(int $pageId, string $pluginNamespace, string $listUri): void
    {
        $routes = self::filterRoutes();
        $this->assertCount(18, $routes);
        foreach ($routes as $name => [$demand, $path]) {
            $this->assertSame($listUri . $path, $this->listUri($pageId, $pluginNamespace, $demand), $name);
        }
    }

    #[DataProvider('listPluginsDataProvider')]
    #[Test]
    public function theFilterRoutesResolve(int $pageId, string $pluginNamespace, string $listUri): void
    {
        foreach (self::filterRoutes() as $name => [$demand, $path, $names]) {
            $this->assertSame(
                [isset($demand['viewMode']) ? 'table' : 'list', $names],
                $this->listed($listUri . $path),
                $name,
            );
        }
    }

    /**
     * The form of a list shown as a table under a letter leads to the speaking URL of the
     * filtered table under that letter.
     */
    #[DataProvider('listPluginsDataProvider')]
    #[Test]
    public function aSubmissionLeadsToTheSpeakingUrlOfTheFilteredList(int $pageId, string $pluginNamespace, string $listUri): void
    {
        $response = $this->submitFrontendForm(
            $listUri . '/view-mode/table/b',
            'academic-persons-list__filter',
            [$pluginNamespace => ['demand' => ['functionTypeFilter' => (string)self::PROFESSOR]]],
        );

        $this->assertSame(303, $response->getStatusCode());
        $location = $response->getHeaderLine('Location');
        $this->assertSame($listUri . '/function/professor/view-mode/table/b', $location);
        $this->assertSame(['table', ['Ben Baker', 'Bea Brown']], $this->listed($location));
    }

    #[DataProvider('listPluginsDataProvider')]
    #[Test]
    public function aSlugNoRecordCarriesIsNotFound(int $pageId, string $pluginNamespace, string $listUri): void
    {
        $this->assertSame(404, $this->requestFrontendPage($listUri . '/function/nobody')->getStatusCode());
        $this->assertSame(404, $this->requestFrontendPage($listUri . '/unit/nowhere/page-2')->getStatusCode());
        $this->assertSame(404, $this->requestFrontendPage($listUri . '/function/professor/unit/nowhere')->getStatusCode());
    }

    /**
     * @return \Generator<string, array{0: int, 1: string, 2: string, 3: string, 4: int, 5: string}>
     */
    public static function recordsWithoutASegmentDataProvider(): \Generator
    {
        foreach (self::listPluginsDataProvider() as $plugin => [$pageId, $pluginNamespace, $listUri]) {
            yield $plugin . ', function type without a slug' => [$pageId, $pluginNamespace, $listUri, 'functionTypeFilter', self::ASSISTANT, 'Dora Dunn'];
            yield $plugin . ', unit without a slug' => [$pageId, $pluginNamespace, $listUri, 'organisationalUnitFilter', self::MATHEMATICS, 'Gus Green'];
            yield $plugin . ', unit whose slug has a slash' => [$pageId, $pluginNamespace, $listUri, 'organisationalUnitFilter', self::CHEMISTRY, 'Fay Fox'];
        }
    }

    /**
     * A slug that cannot be one segment, empty or with a slash, gives no route, so the
     * filter is a query argument with a cHash - and still works.
     */
    #[DataProvider('recordsWithoutASegmentDataProvider')]
    #[Test]
    public function aRecordWithoutAUsableSlugIsAQueryArgument(int $pageId, string $pluginNamespace, string $listUri, string $filter, int $uid, string $name): void
    {
        $uri = $this->listUri($pageId, $pluginNamespace, [$filter => $uid]);

        $this->assertStringStartsWith($listUri . '?', $uri);
        parse_str((string)parse_url($uri, PHP_URL_QUERY), $query);
        $this->assertSame((string)$uid, $query[$pluginNamespace]['demand'][$filter] ?? null);
        $this->assertArrayHasKey('cHash', $query);
        $this->assertSame(['list', [$name]], $this->listed($uri));
    }

    /**
     * A list may show records of a folder in another site. Their slug is unique in the
     * installation, so the URL resolves on the site of the list as well.
     */
    #[DataProvider('listPluginsDataProvider')]
    #[Test]
    public function aRecordStoredInAnotherSiteResolves(int $pageId, string $pluginNamespace, string $listUri): void
    {
        $uri = $this->listUri($pageId, $pluginNamespace, ['functionTypeFilter' => self::DEAN]);

        $this->assertSame($listUri . '/function/dean', $uri);
        $this->assertSame(['list', ['Emil Evans']], $this->listed($uri));
    }

    /**
     * The key segment follows the locale of the site language, and the slug is the one of
     * the translated record.
     */
    #[Test]
    public function aGermanListHasGermanSegments(): void
    {
        $uri = $this->listUri(self::LIST_PAGE, 'tx_academicpersons_list', ['functionTypeFilter' => self::PROFESSOR], ['_language' => 1]);

        $this->assertSame('https://www.acme.com/de/personen/funktion/professorin', $uri);
        $this->assertSame(['list', ['Anna Adams']], $this->listed($uri));

        // A record without a translation keeps the slug of its default language.
        $uri = $this->listUri(self::LIST_PAGE, 'tx_academicpersons_list', ['functionTypeFilter' => 2, 'organisationalUnitFilter' => self::BIOLOGY], ['_language' => 1]);

        $this->assertSame('https://www.acme.com/de/personen/funktion/lecturer/einheit/biology', $uri);
        $this->assertSame(['list', ['Carl Clark']], $this->listed($uri));
    }

    /**
     * The routes the enhancers had keep resolving next to the filter routes: the page, the
     * letter, the view mode and - for list and detail - the profile, whose route matches
     * any path.
     */
    #[Test]
    public function theExistingRoutesStillResolve(): void
    {
        foreach (['https://www.acme.com/persons', 'https://www.acme.com/team'] as $listUri) {
            $this->assertSame(['list', ['Ben Baker']], $this->listed($listUri . '/page-2'));
            $this->assertSame(['list', ['Ben Baker', 'Bea Brown']], $this->listed($listUri . '/b'));
            $this->assertSame(['table', ['Anna Adams']], $this->listed($listUri . '/view-mode/table'));
        }
        $detail = (string)$this->requestFrontendPage('https://www.acme.com/team/ben-baker')->getBody();
        $this->assertStringContainsString('Baker', $detail);
        $this->assertStringNotContainsString('Adams', $detail);
    }
}
