<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Plugins;

use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TESTS\TestProfileQueryConstraints\EventListener\CountProfileQueryListener;

/**
 * The letter navigation of the persons list, as the **shipped** templates render it -
 * `EXT:test_plugin_templates` is deliberately not loaded, because the markup is what is
 * under test.
 *
 * Profiles exist under A and B only, plus a hidden one under C, so C is the letter the
 * hidden records option decides and D to Z are empty in every scenario. A letter is followed
 * through the link the navigation rendered, cHash included, rather than through a URL the
 * test assembles.
 */
final class AcademicPersonsLetterNavigationTest extends AbstractAcademicPersonsTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
        'DE' => ['id' => 1, 'title' => 'Deutsch', 'locale' => 'de_DE.UTF8', 'iso' => 'de', 'hrefLang' => 'de-DE', 'direction' => ''],
    ];

    private const NAVIGATION_CLASS = 'academic-persons-list__alphabet-pagination';

    /**
     * What a disabled letter says to assistive technology, in the language of the page.
     */
    private string $noProfilesLabel = 'no profiles';

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration();
        $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
        $this->addTestExtensionsToLoad('tests/test-profile-query-constraints', 'tests/test-profile-partial-overrides');
        parent::setUp();
        CountProfileQueryListener::$dispatches = 0;
    }

    protected function tearDown(): void
    {
        CountProfileQueryListener::$dispatches = 0;
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    /**
     * @param list<string> $additionalConstantFiles Constants loaded after the shipped ones.
     * @param list<array<string, mixed>>|null $languages The site languages, English alone by default.
     */
    private function setUpTestCase(string $dataSet, array $additionalConstantFiles = [], ?array $languages = null): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsLetterNavigation/' . $dataSet . '.csv');
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
                'setup' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript',
                    'EXT:academic_persons/Configuration/TypoScript/Default/setup.typoscript',
                    'EXT:academic_persons/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/Rendering.typoscript',
                ],
            ],
        );
        $this->writeFrontendPluginTestSite($languages ?? [
            $this->buildDefaultLanguageConfiguration(
                identifier: 'EN',
                base: '/',
            ),
        ]);
    }

    private function xpath(string $content): \DOMXPath
    {
        $document = new \DOMDocument();
        $document->loadHTML('<?xml encoding="utf-8" ?>' . $content, LIBXML_NOERROR);

        return new \DOMXPath($document);
    }

    /**
     * @return \DOMNodeList<\DOMNode>
     */
    private function nodes(\DOMXPath $xpath, string $query, ?\DOMNode $context = null): \DOMNodeList
    {
        $nodes = $xpath->query($query, $context);
        $this->assertInstanceOf(\DOMNodeList::class, $nodes, sprintf('The query "%s" is invalid.', $query));

        return $nodes;
    }

    private function hasClass(string $class): string
    {
        return sprintf("contains(concat(' ', normalize-space(@class), ' '), ' %s ')", $class);
    }

    private function navigationCount(\DOMXPath $xpath): int
    {
        return $this->nodes($xpath, sprintf('//nav[%s]', $this->hasClass(self::NAVIGATION_CLASS)))->length;
    }

    private function navigation(\DOMXPath $xpath): \DOMElement
    {
        $nodes = $this->nodes($xpath, sprintf('//nav[%s]', $this->hasClass(self::NAVIGATION_CLASS)));
        $this->assertSame(1, $nodes->length, 'Exactly one letter navigation is expected.');
        $navigation = $nodes->item(0);
        $this->assertInstanceOf(\DOMElement::class, $navigation);

        return $navigation;
    }

    /**
     * The item of one entry of the navigation, found by its visible text: "A-Z" or a letter.
     * The visually hidden text of a disabled letter is not part of what is matched.
     */
    private function item(\DOMXPath $xpath, string $label): \DOMElement
    {
        $items = $this->nodes(
            $xpath,
            sprintf(
                './/li[normalize-space(concat(./a, ./span/text()))="%s"]',
                $label,
            ),
            $this->navigation($xpath),
        );
        $this->assertSame(1, $items->length, sprintf('Exactly one item "%s" is expected.', $label));
        $item = $items->item(0);
        $this->assertInstanceOf(\DOMElement::class, $item);

        return $item;
    }

    /**
     * The state an item renders in: "link", "current" (not a link, marked as the current
     * page), "current link" (a link marked as the current page) or "disabled" (neither a link
     * nor current, with the hidden explanation), each with "active" when the item carries
     * the active class. Anything else fails the test.
     */
    private function state(\DOMXPath $xpath, string $label): string
    {
        $item = $this->item($xpath, $label);
        $classes = ' ' . $item->getAttribute('class') . ' ';
        $links = $this->nodes($xpath, './a[@href]', $item);
        $current = $this->nodes($xpath, './/*[@aria-current="page"]', $item)->length === 1;
        $active = str_contains($classes, ' active ') ? ' active' : '';

        if ($links->length === 1) {
            return ($current ? 'current link' : 'link') . $active;
        }
        $this->assertSame(0, $links->length, sprintf('Item "%s" has more than one link.', $label));
        if ($current) {
            return 'current' . $active;
        }
        $this->assertStringContainsString(' disabled ', $classes, sprintf('Item "%s" is neither a link nor current.', $label));
        $this->assertSame(
            $this->noProfilesLabel,
            trim($this->nodes($xpath, sprintf('.//span[%s]', $this->hasClass('visually-hidden')), $item)->item(0)?->textContent ?? '', " -\n"),
            sprintf('Disabled item "%s" says why to assistive technology.', $label),
        );

        return 'disabled' . $active;
    }

    /**
     * @param list<string> $labels
     * @return array<string, string>
     */
    private function states(\DOMXPath $xpath, array $labels): array
    {
        $states = [];
        foreach ($labels as $label) {
            $states[$label] = $this->state($xpath, $label);
        }

        return $states;
    }

    private function href(\DOMXPath $xpath, string $label): string
    {
        $link = $this->nodes($xpath, './a[@href]', $this->item($xpath, $label))->item(0);
        $this->assertInstanceOf(\DOMElement::class, $link, sprintf('Item "%s" is not a link.', $label));

        return $link->getAttribute('href');
    }

    private function follow(\DOMXPath $xpath, string $label): \DOMXPath
    {
        return $this->xpath($this->renderFrontendPage('https://www.acme.com' . $this->href($xpath, $label)));
    }

    /**
     * The last names the list renders, in order, taken from the item headings.
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
     * The baseline the manual selection test is measured against: the same content element
     * without a selection renders the navigation, so its absence below is caused by the
     * selection and not by the fixture.
     */
    #[Test]
    public function letterNavigationIsRenderedWithoutAManualSelection(): void
    {
        $this->setUpTestCase('list');

        $xpath = $this->xpath($this->renderFrontendPage('https://www.acme.com/home'));

        $this->assertSame(1, $this->navigationCount($xpath));
    }

    /**
     * A manual selection ignores the letter filter - the repository matches the selected
     * uids and nothing else - so every letter would show the whole selection. The
     * navigation is left out, even though the content element has it switched on
     * (ACE-599).
     */
    #[Test]
    public function letterNavigationIsNotRenderedForAManualSelection(): void
    {
        $this->setUpTestCase('manualSelection');

        $xpath = $this->xpath($this->renderFrontendPage('https://www.acme.com/home'));

        $this->assertSame(0, $this->navigationCount($xpath));
        $this->assertSame(['Ben Baker'], $this->listedNames($xpath));
    }

    /**
     * @return \Generator<string, array{0: string, 1: int}>
     */
    public static function profileQueriesPerRenderingDataProvider(): \Generator
    {
        yield 'navigation on: the list and its letters' => ['list', 2];
        yield 'navigation off: the list only' => ['listWithoutNavigation', 1];
        yield 'manual selection: the list only' => ['manualSelection', 1];
    }

    /**
     * The letters cost one statement, and only a rendering that shows them pays for it. Seen
     * through the query event, which the list query and the letter query both dispatch. A
     * manual selection would not reach the database for its letters either way - the
     * repository answers it without a query - so that the list action leaves them out there
     * is asserted in `AcademicPersonsListPluginTest`, through the view.
     */
    #[DataProvider('profileQueriesPerRenderingDataProvider')]
    #[Test]
    public function theLetterQueryRunsOnlyForARenderedNavigation(string $dataSet, int $expectedQueries): void
    {
        $this->setUpTestCase($dataSet);

        $this->renderFrontendPage('https://www.acme.com/home');

        $this->assertSame($expectedQueries, CountProfileQueryListener::$dispatches);
    }

    /**
     * Letters with profiles are links, every other letter is disabled and says why to
     * assistive technology; "A-Z" is the current item while no letter is selected, and the
     * navigation has a name (ACE-598).
     */
    #[Test]
    public function lettersWithoutProfilesAreDisabled(): void
    {
        $this->setUpTestCase('list');

        $xpath = $this->xpath($this->renderFrontendPage('https://www.acme.com/home'));

        $this->assertSame('Filter by initial of the last name', $this->navigation($xpath)->getAttribute('aria-label'));
        $this->assertSame(
            ['A-Z' => 'current link active', 'A' => 'link', 'B' => 'link', 'C' => 'disabled', 'D' => 'disabled', 'Z' => 'disabled'],
            $this->states($xpath, ['A-Z', 'A', 'B', 'C', 'D', 'Z']),
        );
        $this->assertSame(3, $this->nodes($xpath, './/a[@href]', $this->navigation($xpath))->length, 'A-Z, A and B are the only links.');
        $this->assertSame(24, $this->nodes($xpath, sprintf('.//li[%s]', $this->hasClass('disabled')), $this->navigation($xpath))->length);
    }

    /**
     * Under a selected letter the other letters keep their state: the letters are computed
     * without the selection, so B stays a link while only A is listed.
     */
    #[Test]
    public function aSelectedLetterIsCurrentAndTheOthersKeepTheirState(): void
    {
        $this->setUpTestCase('list');

        $xpath = $this->follow($this->xpath($this->renderFrontendPage('https://www.acme.com/home')), 'A');

        $this->assertSame(['Anna Adams'], $this->listedNames($xpath));
        $this->assertSame(
            ['A-Z' => 'link', 'A' => 'current active', 'B' => 'link', 'C' => 'disabled'],
            $this->states($xpath, ['A-Z', 'A', 'B', 'C']),
        );
    }

    #[Test]
    public function aHiddenProfileMakesItsLetterAvailableWithTheHiddenRecordsOption(): void
    {
        $this->setUpTestCase('listShowingHiddenRecords');

        $xpath = $this->xpath($this->renderFrontendPage('https://www.acme.com/home'));

        $this->assertSame(['C' => 'link', 'D' => 'disabled'], $this->states($xpath, ['C', 'D']));
    }

    #[Test]
    public function theListAndDetailPluginRendersTheSameNavigation(): void
    {
        $this->setUpTestCase('listAndDetail');

        $xpath = $this->xpath($this->renderFrontendPage('https://www.acme.com/home'));

        $this->assertSame(
            ['A-Z' => 'current link active', 'A' => 'link', 'B' => 'link', 'C' => 'disabled'],
            $this->states($xpath, ['A-Z', 'A', 'B', 'C']),
        );
    }

    /**
     * @return \Generator<string, array{0: non-empty-string, 1: list<non-empty-string>, 2: string}>
     */
    public static function languageModesDataProvider(): \Generator
    {
        yield 'strict: Moore has no translation' => ['strict', [], 'disabled'];
        yield 'fallback: Moore falls back to English' => ['fallback', ['EN'], 'link'];
    }

    /**
     * @param non-empty-string $fallbackType
     * @param list<non-empty-string> $fallbackIdentifiers
     */
    #[DataProvider('languageModesDataProvider')]
    #[Test]
    public function theLanguageModeOfTheSiteDecidesAnUntranslatedProfile(string $fallbackType, array $fallbackIdentifiers, string $expectedMoore): void
    {
        $this->setUpTestCase('languages', [], [
            $this->buildDefaultLanguageConfiguration(
                identifier: 'EN',
                base: '/',
            ),
            $this->buildLanguageConfiguration(
                identifier: 'DE',
                base: '/de/',
                fallbackIdentifiers: $fallbackIdentifiers,
                fallbackType: $fallbackType,
            ),
        ]);

        $this->noProfilesLabel = 'keine Profile';
        $xpath = $this->xpath($this->renderFrontendPage('https://www.acme.com/de/home'));

        $this->assertSame('Nach Anfangsbuchstaben des Nachnamens filtern', $this->navigation($xpath)->getAttribute('aria-label'));
        $this->assertSame(['L' => 'link', 'M' => $expectedMoore], $this->states($xpath, ['L', 'M']));
    }

    /**
     * A project whose list template renders the shipped navigation without the availability
     * keeps what it had: every letter a link.
     */
    #[Test]
    public function aListTemplateWithoutTheAvailabilityKeepsEveryLetterALink(): void
    {
        $this->setUpTestCase('list', ['EXT:test_profile_partial_overrides/Configuration/TypoScript/ProjectListTemplate.typoscript']);

        $xpath = $this->xpath($this->renderFrontendPage('https://www.acme.com/home'));

        $this->assertSame(27, $this->nodes($xpath, './/a[@href]', $this->navigation($xpath))->length, 'A-Z and all 26 letters are links.');
        $this->assertSame(0, $this->nodes($xpath, sprintf('.//li[%s]', $this->hasClass('disabled')), $this->navigation($xpath))->length);
    }
}
