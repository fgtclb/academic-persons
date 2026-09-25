<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Plugins;

use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TESTS\TestProfileQueryConstraints\EventListener\ReplaceListDemandListener;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Frontend\Page\CacheHashCalculator;

/**
 * Which choices of a visitor the pagination and letter links of the persons list carry.
 *
 * Pagination is switched off under a letter, so page and letter alone cannot show a link
 * that keeps a second value. The list template of the fixture folder adds one, `viewMode`,
 * to the active list arguments before the **shipped** partials render, and prints what the
 * list action assigned. The partials carry it without knowing it, which is what the tests
 * with the fixture template assert; the view mode switch of the content element is off,
 * so the plugin itself does not take the value from the request.
 * {@see AcademicPersonsListViewModesTest} covers the switch. The other tests render the
 * shipped templates as they are.
 *
 * Three profiles, Adams under A and Baker and Brown under B, one per page. Links are
 * followed as rendered, cHash included, rather than assembled by the test.
 */
final class AcademicPersonsListNavigationStateTest extends AbstractAcademicPersonsTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    private const PAGINATION_CLASS = 'academic-persons-list__pagination';

    private const LETTER_NAVIGATION_CLASS = 'academic-persons-list__alphabet-pagination';

    private const FIXTURE_TEMPLATE = 'EXT:academic_persons/Tests/Functional/Plugins/Fixtures/AcademicPersonsListNavigationState/TypoScript/ActiveListArgumentsTemplate.typoscript';

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration();
        $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
        $this->addTestExtensionsToLoad('tests/test-profile-query-constraints');
        parent::setUp();
        ReplaceListDemandListener::$alphabetFilter = null;
    }

    protected function tearDown(): void
    {
        ReplaceListDemandListener::$alphabetFilter = null;
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    /**
     * @param list<string> $additionalSetupFiles Setup loaded after the shipped one.
     * @param list<string> $additionalConstantFiles Constants loaded after the shipped ones.
     */
    private function setUpTestCase(string $dataSet, array $additionalSetupFiles = [], array $additionalConstantFiles = []): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsListNavigationState/' . $dataSet . '.csv');
        $this->setUpFrontendRootPage(
            pageId: 1,
            typoScriptFiles: [
                'constants' => array_merge(
                    [
                        'EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript',
                        'EXT:academic_persons/Configuration/TypoScript/Default/constants.typoscript',
                        'EXT:academic_persons/Tests/Functional/Plugins/Fixtures/TypoScript/Constants/DetailPage.typoscript',
                    ],
                    $additionalConstantFiles,
                ),
                'setup' => array_merge(
                    [
                        'EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript',
                        'EXT:academic_persons/Configuration/TypoScript/Default/setup.typoscript',
                        'EXT:academic_persons/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/Rendering.typoscript',
                    ],
                    $additionalSetupFiles,
                ),
            ],
        );
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration(
                identifier: 'EN',
                base: '/',
            ),
        ]);
    }

    private function render(string $uri): \DOMXPath
    {
        $document = new \DOMDocument();
        $document->loadHTML('<?xml encoding="utf-8" ?>' . $this->renderFrontendPage('https://www.acme.com' . $uri), LIBXML_NOERROR);

        return new \DOMXPath($document);
    }

    /**
     * @return \DOMNodeList<\DOMNode>
     */
    private function nodes(\DOMXPath $xpath, string $query): \DOMNodeList
    {
        $nodes = $xpath->query($query);
        $this->assertInstanceOf(\DOMNodeList::class, $nodes, sprintf('The query "%s" is invalid.', $query));

        return $nodes;
    }

    private function hasClass(string $class): string
    {
        return sprintf("contains(concat(' ', normalize-space(@class), ' '), ' %s ')", $class);
    }

    /**
     * The target of the one link of a navigation whose visible text is the label: a page
     * number, "A-Z" or a letter. The visually hidden text of a link is not matched.
     */
    private function href(\DOMXPath $xpath, string $navigationClass, string $label): string
    {
        $links = $this->nodes($xpath, sprintf(
            '//nav[%s]//a[@href][normalize-space(./span[1])="%s"]',
            $this->hasClass($navigationClass),
            $label,
        ));
        $this->assertSame(1, $links->length, sprintf('Exactly one link "%s" is expected.', $label));
        $link = $links->item(0);
        $this->assertInstanceOf(\DOMElement::class, $link);

        return $link->getAttribute('href');
    }

    private function pageHref(\DOMXPath $xpath, string $label): string
    {
        return $this->href($xpath, self::PAGINATION_CLASS, $label);
    }

    /**
     * The target of the page link in the item of the given class: "first", "previous",
     * "next" or "last".
     */
    private function pageItemHref(\DOMXPath $xpath, string $itemClass): string
    {
        $links = $this->nodes($xpath, sprintf(
            '//nav[%s]//li[%s]/a[@href]',
            $this->hasClass(self::PAGINATION_CLASS),
            $this->hasClass($itemClass),
        ));
        $this->assertSame(1, $links->length, sprintf('Exactly one "%s" link is expected.', $itemClass));
        $link = $links->item(0);
        $this->assertInstanceOf(\DOMElement::class, $link);

        return $link->getAttribute('href');
    }

    private function letterHref(\DOMXPath $xpath, string $label): string
    {
        return $this->href($xpath, self::LETTER_NAVIGATION_CLASS, $label);
    }

    /**
     * The demand a link carries, sorted by key: the order of the query arguments is not
     * what is under test.
     *
     * @return array<string, mixed>
     */
    private function demand(string $href, string $pluginNamespace): array
    {
        parse_str((string)parse_url($href, PHP_URL_QUERY), $query);
        $demand = $query[$pluginNamespace]['demand'] ?? null;
        $this->assertIsArray($demand, sprintf('The link "%s" carries no demand.', $href));
        ksort($demand);

        return $demand;
    }

    /**
     * What the list action assigned as `activeListArguments`, as the fixture template
     * prints it.
     *
     * @return array<string, mixed>|null
     */
    private function activeListArguments(\DOMXPath $xpath): ?array
    {
        $nodes = $this->nodes($xpath, sprintf('//div[%s]', $this->hasClass('test-active-list-arguments')));
        $this->assertSame(1, $nodes->length);
        $node = $nodes->item(0);
        $this->assertInstanceOf(\DOMElement::class, $node);
        $value = json_decode($node->getAttribute('data-arguments'), true);

        return is_array($value) ? $value : null;
    }

    /**
     * The names the list renders, in order, taken from the item headings.
     *
     * @return list<string>
     */
    private function listedNames(\DOMXPath $xpath): array
    {
        $names = [];
        foreach ($this->nodes($xpath, sprintf('//*[%s]', $this->hasClass('card-title'))) as $heading) {
            $names[] = trim((string)preg_replace('#\s+#u', ' ', $heading->textContent));
        }

        return $names;
    }

    /**
     * @return \Generator<string, array{0: string, 1: string}>
     */
    public static function pluginsDataProvider(): \Generator
    {
        yield 'list' => ['list', 'tx_academicpersons_list'];
        yield 'list and detail' => ['listAndDetail', 'tx_academicpersons_listanddetail'];
    }

    /**
     * The list action assigns the visitor's choices, and only those that differ from
     * their default: nothing on the bare page, the page on page 2, the letter under a
     * letter. The `viewMode` the fixture template adds to every link arrives with the
     * next request and is not among them: the content element does not offer the switch,
     * so the plugin resolves the default mode, which no link carries.
     */
    #[DataProvider('pluginsDataProvider')]
    #[Test]
    public function activeListArgumentsHoldTheAcceptedVisitorChoicesOnly(string $dataSet, string $pluginNamespace): void
    {
        $this->setUpTestCase($dataSet, [self::FIXTURE_TEMPLATE]);

        $firstPage = $this->render('/home');
        $secondPage = $this->render($this->pageHref($firstPage, '2'));
        $letterPage = $this->render($this->letterHref($firstPage, 'B'));

        $this->assertSame([], $this->activeListArguments($firstPage));
        $this->assertSame(['currentPage' => 2], $this->activeListArguments($secondPage));
        $this->assertSame(['alphabetFilter' => 'b'], $this->activeListArguments($letterPage));
    }

    /**
     * A pagination link changes the page and nothing else, so a value the visitor chose -
     * `viewMode` here - survives paging, forward and back: the page numbers as well as
     * first, previous, next and last.
     */
    #[DataProvider('pluginsDataProvider')]
    #[Test]
    public function paginationLinksKeepTheOtherVisitorValues(string $dataSet, string $pluginNamespace): void
    {
        $this->setUpTestCase($dataSet, [self::FIXTURE_TEMPLATE]);

        $firstPage = $this->render('/home');
        $this->assertSame(['currentPage' => '2', 'viewMode' => 'table'], $this->demand($this->pageHref($firstPage, '2'), $pluginNamespace));
        $this->assertSame(['currentPage' => '3', 'viewMode' => 'table'], $this->demand($this->pageHref($firstPage, '3'), $pluginNamespace));

        $secondPage = $this->render($this->pageHref($firstPage, '2'));
        $this->assertSame(['Ben Baker'], $this->listedNames($secondPage));
        $this->assertSame(['currentPage' => '1', 'viewMode' => 'table'], $this->demand($this->pageHref($secondPage, '1'), $pluginNamespace));
        $this->assertSame(['currentPage' => '3', 'viewMode' => 'table'], $this->demand($this->pageHref($secondPage, '3'), $pluginNamespace));
        $this->assertSame(['currentPage' => '1', 'viewMode' => 'table'], $this->demand($this->pageItemHref($secondPage, 'first'), $pluginNamespace));
        $this->assertSame(['currentPage' => '1', 'viewMode' => 'table'], $this->demand($this->pageItemHref($secondPage, 'previous'), $pluginNamespace));
        $this->assertSame(['currentPage' => '3', 'viewMode' => 'table'], $this->demand($this->pageItemHref($secondPage, 'next'), $pluginNamespace));
        $this->assertSame(['currentPage' => '3', 'viewMode' => 'table'], $this->demand($this->pageItemHref($secondPage, 'last'), $pluginNamespace));
    }

    /**
     * A letter link and the link back to all letters change the letter, keep the other
     * values and lead to the first page: from page 2 they carry no page.
     */
    #[DataProvider('pluginsDataProvider')]
    #[Test]
    public function letterLinksKeepTheOtherVisitorValuesAndStartOnTheFirstPage(string $dataSet, string $pluginNamespace): void
    {
        $this->setUpTestCase($dataSet, [self::FIXTURE_TEMPLATE]);

        $secondPage = $this->render($this->pageHref($this->render('/home'), '2'));
        $this->assertSame(['alphabetFilter' => 'a', 'viewMode' => 'table'], $this->demand($this->letterHref($secondPage, 'A'), $pluginNamespace));
        $this->assertSame(['alphabetFilter' => 'b', 'viewMode' => 'table'], $this->demand($this->letterHref($secondPage, 'B'), $pluginNamespace));
        $this->assertSame(['alphabetFilter' => '', 'viewMode' => 'table'], $this->demand($this->letterHref($secondPage, 'A-Z'), $pluginNamespace));

        $letterPage = $this->render($this->letterHref($secondPage, 'B'));
        $this->assertSame(['Ben Baker', 'Bea Brown'], $this->listedNames($letterPage));
        $this->assertSame(['alphabetFilter' => '', 'viewMode' => 'table'], $this->demand($this->letterHref($letterPage, 'A-Z'), $pluginNamespace));
        $this->assertSame(['alphabetFilter' => 'a', 'viewMode' => 'table'], $this->demand($this->letterHref($letterPage, 'A'), $pluginNamespace));
    }

    /**
     * With the reset option on, the selected letter links back to the whole list, like
     * "A-Z": it keeps the other values as well.
     */
    #[DataProvider('pluginsDataProvider')]
    #[Test]
    public function theSelectedLetterResetLinkKeepsTheOtherVisitorValues(string $dataSet, string $pluginNamespace): void
    {
        $this->setUpTestCase(
            $dataSet,
            [self::FIXTURE_TEMPLATE],
            ['EXT:academic_persons/Tests/Functional/Plugins/Fixtures/TypoScript/Constants/ActiveLetterResets.typoscript'],
        );

        $letterPage = $this->render($this->letterHref($this->render('/home'), 'B'));

        $this->assertSame(['alphabetFilter' => '', 'viewMode' => 'table'], $this->demand($this->letterHref($letterPage, 'B'), $pluginNamespace));
    }

    /**
     * The active list arguments are what the visitor asked for, read before the list
     * event: a listener that hands the view a demand of its own - with a letter here -
     * acts again on the request a link leads to, so its value does not travel in the URL.
     */
    #[Test]
    public function aListenerOfTheListEventDoesNotChangeTheActiveListArguments(): void
    {
        $this->setUpTestCase('list', [self::FIXTURE_TEMPLATE]);
        ReplaceListDemandListener::$alphabetFilter = 'b';

        $xpath = $this->render('/home');

        $this->assertSame([], $this->activeListArguments($xpath));
        // The replaced demand did reach the view: under a letter the list is not paginated,
        // and B is the current letter.
        $this->assertSame(0, $this->nodes($xpath, sprintf('//nav[%s]', $this->hasClass(self::PAGINATION_CLASS)))->length);
        $this->assertSame(
            'B',
            trim($this->nodes($xpath, sprintf('//nav[%s]//*[@aria-current="page"]', $this->hasClass(self::LETTER_NAVIGATION_CLASS)))->item(0)?->textContent ?? ''),
        );
    }

    /**
     * The shipped templates build the links they always built: the page alone, the letter
     * alone, and "A-Z" with the empty letter. A site that renders them sees no other URL.
     */
    #[DataProvider('pluginsDataProvider')]
    #[Test]
    public function theShippedTemplatesKeepTheirLinks(string $dataSet, string $pluginNamespace): void
    {
        $this->setUpTestCase($dataSet);

        $firstPage = $this->render('/home');
        $this->assertSame(['currentPage' => '2'], $this->demand($this->pageHref($firstPage, '2'), $pluginNamespace));
        $this->assertSame(['alphabetFilter' => 'b'], $this->demand($this->letterHref($firstPage, 'B'), $pluginNamespace));
        $this->assertSame(['alphabetFilter' => ''], $this->demand($this->letterHref($firstPage, 'A-Z'), $pluginNamespace));

        $secondPage = $this->render($this->pageHref($firstPage, '2'));
        $this->assertSame(['currentPage' => '1'], $this->demand($this->pageHref($secondPage, '1'), $pluginNamespace));
        $this->assertSame(['alphabetFilter' => 'a'], $this->demand($this->letterHref($secondPage, 'A'), $pluginNamespace));
        $this->assertSame(['alphabetFilter' => ''], $this->demand($this->letterHref($secondPage, 'A-Z'), $pluginNamespace));

        $letterPage = $this->render($this->letterHref($secondPage, 'B'));
        $this->assertSame(['Ben Baker', 'Bea Brown'], $this->listedNames($letterPage));
        $this->assertSame(['alphabetFilter' => ''], $this->demand($this->letterHref($letterPage, 'A-Z'), $pluginNamespace));
    }

    /**
     * Nothing the plugin does not accept from a visitor reaches a link: not a foreign query
     * parameter, not an unknown plugin argument, and not a sorting the content element
     * sets. The sorting is shown to be ignored, not only left out of the links: by first
     * name, page 2 would list Brown.
     */
    #[Test]
    public function foreignParametersAndEditorValuesAreNotCarried(): void
    {
        $this->setUpTestCase('list');
        $query = HttpUtility::buildQueryString([
            'foreign' => 'value',
            'tx_academicpersons_list' => [
                'unknown' => 'value',
                'demand' => [
                    'currentPage' => 2,
                    'sortBy' => 'firstName',
                ],
            ],
        ]);
        $cacheHash = GeneralUtility::makeInstance(CacheHashCalculator::class)->generateForParameters('id=2&' . $query);

        $xpath = $this->render('/home?' . $query . '&cHash=' . $cacheHash);

        $this->assertSame(['Ben Baker'], $this->listedNames($xpath));
        $hrefs = [];
        foreach ([self::PAGINATION_CLASS, self::LETTER_NAVIGATION_CLASS] as $navigationClass) {
            foreach ($this->nodes($xpath, sprintf('//nav[%s]//a[@href]', $this->hasClass($navigationClass))) as $link) {
                $this->assertInstanceOf(\DOMElement::class, $link);
                $hrefs[] = urldecode($link->getAttribute('href'));
            }
        }
        // First, previous, 1, 3, next and last; A-Z, A and B.
        $this->assertCount(9, $hrefs);
        foreach ($hrefs as $href) {
            $this->assertStringNotContainsString('foreign', $href);
            $this->assertStringNotContainsString('unknown', $href);
            $this->assertStringNotContainsString('sortBy', $href);
        }
    }
}
