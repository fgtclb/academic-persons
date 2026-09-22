<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Plugins;

use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;

/**
 * The parts of the profile item and the profile list an integrator can override on their
 * own, and the markup the shipped partials render.
 *
 * Every test here renders the **shipped** templates - `EXT:test_plugin_templates` is
 * deliberately not loaded, because the markup is what is under test. The four content
 * elements of the `allPlugins` fixture sit on one page, so a single rendering shows what
 * the list, card, selected-profiles and selected-contracts plugins make of the same three
 * profiles. Each assertion is scoped to the wrapper class of its plugin.
 *
 * `EXT:test_profile_partial_overrides` ships one override per partial the split adds and
 * stays inert until a test includes its TypoScript, the way
 * {@see AcademicPersonsProfileQueryConstraintTest} keeps its listeners inert.
 *
 * Profile "Sean O'Neill" carries the apostrophe on purpose: the item hands the name and
 * the detail link to the header partials as values, which escape them once for output.
 * A name rendered through a partial that escapes as well would show `&amp;#039;`, and a
 * detail link would lose its query arguments - both are pinned below.
 */
final class AcademicPersonsProfileTemplatePartialsTest extends AbstractAcademicPersonsTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    /**
     * The wrapper class of every plugin that renders profile items, and the fixture
     * expectation that all four show the same three profiles.
     */
    private const PLUGIN_WRAPPERS = [
        'list' => 'academic-persons-list',
        'card' => 'academic-persons-card',
        'selected profiles' => 'academic-persons-profiles',
        'selected contracts' => 'academic-persons-contracts',
    ];

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration();
        $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
        $this->addTestExtensionsToLoad('tests/test-profile-partial-overrides');
        parent::setUp();
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    /**
     * @param list<string> $additionalConstantFiles Constants loaded after the shipped ones.
     */
    private function setUpTestCase(string $dataSet, array $additionalConstantFiles = []): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsProfileTemplatePartials/' . $dataSet . '.csv');
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
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration(
                identifier: 'EN',
                base: '/',
            ),
        ]);
    }

    private function renderHomePage(): string
    {
        return $this->renderFrontendPage('https://www.acme.com/home');
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

    /**
     * The element a plugin wraps its whole output in, as the context of every assertion
     * about that plugin.
     */
    private function wrapper(\DOMXPath $xpath, string $class): \DOMElement
    {
        $nodes = $this->nodes($xpath, sprintf('//*[%s]', $this->hasClass($class)));
        $this->assertSame(1, $nodes->length, sprintf('Exactly one "%s" element is expected.', $class));
        $wrapper = $nodes->item(0);
        $this->assertInstanceOf(\DOMElement::class, $wrapper);

        return $wrapper;
    }

    /**
     * The group headers of a list, told apart from the item headings by the class the item
     * gives its own heading. Both are rendered by the same header partials, and which
     * heading level each one gets depends on the content element's header layout.
     */
    private function groupHeaderQuery(): string
    {
        return sprintf('.//h2[not(%s)]', $this->hasClass('card-title'));
    }

    private function countIn(\DOMXPath $xpath, \DOMNode $context, string $query): int
    {
        return $this->nodes($xpath, $query, $context)->length;
    }

    private function textIn(\DOMXPath $xpath, \DOMNode $context, string $query): string
    {
        $nodes = $this->nodes($xpath, $query, $context);
        $texts = [];
        foreach ($nodes as $node) {
            $texts[] = trim((string)preg_replace('#\s+#u', ' ', $node->textContent));
        }

        return implode('|', $texts);
    }

    /**
     * The markup every profile item renders, whichever plugin renders it: the card
     * wrapper, the heading, the contract list and the image.
     */
    private function assertItemMarkup(\DOMXPath $xpath, \DOMElement $wrapper, int $expectedItems, string $plugin): void
    {
        $items = $this->nodes($xpath, sprintf('.//div[%s]', $this->hasClass('academic-persons-item')), $wrapper);
        $this->assertSame($expectedItems, $items->length, sprintf('The %s plugin renders %d items.', $plugin, $expectedItems));

        foreach ($items as $item) {
            $this->assertInstanceOf(\DOMElement::class, $item);
            $this->assertStringContainsString('card', (string)$item->getAttribute('class'));
            $this->assertStringContainsString('flex-column-reverse', (string)$item->getAttribute('class'));
            $this->assertSame(1, $this->countIn($xpath, $item, sprintf('.//div[%s]', $this->hasClass('card-body'))), $plugin);
            $this->assertSame(1, $this->countIn($xpath, $item, sprintf('.//*[%s]', $this->hasClass('card-title'))), $plugin);
            $this->assertSame(1, $this->countIn($xpath, $item, sprintf('.//ul[%s]', $this->hasClass('list-group'))), $plugin);
            $this->assertSame(1, $this->countIn($xpath, $item, sprintf('.//*[%s]', $this->hasClass('card-img-top'))), $plugin);
        }
    }

    /**
     * The column classes the grid wraps every item in. They are what a project's stylesheet
     * builds on, so the split has to keep them.
     */
    private function assertGridColumns(\DOMXPath $xpath, \DOMElement $wrapper, int $expectedColumns, string $plugin): void
    {
        $this->assertSame(
            $expectedColumns,
            $this->countIn($xpath, $wrapper, sprintf(
                './/div[%s][%s][%s][%s]',
                $this->hasClass('col-12'),
                $this->hasClass('col-md-6'),
                $this->hasClass('col-lg-4'),
                $this->hasClass('col-xl-3'),
            )),
            sprintf('The %s plugin wraps every item in the shipped column classes.', $plugin),
        );
    }

    /**
     * The classes the split adds, which the changelog publishes as a contract for project
     * stylesheets. The grid and the empty state are blocks of their own because they sit
     * inside four different ones; everything else is a part of the block it renders in.
     */
    #[Test]
    public function everyPartialCarriesItsStableClass(): void
    {
        $this->setUpTestCase('allPlugins');

        $xpath = $this->xpath($this->renderHomePage());
        // Each class is asserted together with what it has to sit on, so moving one to
        // another element fails the test rather than keeping the count. Grouped by
        // default, so one group header and one grid per first letter of the last name.
        $expected = [
            'academic-persons-list__group-header' => ['//h2[%s]', 3],
            'academic-persons-grid' => ['//div[%s][' . $this->hasClass('row') . ']', 6],
            'academic-persons-grid__item' => ['//div[%s][' . $this->hasClass('col-12') . ']', 12],
            'academic-persons-item__name' => ['//*[%s][' . $this->hasClass('card-title') . ']', 12],
            'academic-persons-item__image' => ['//*[%s][' . $this->hasClass('card-img-top') . ']', 12],
        ];
        foreach ($expected as $class => [$query, $count]) {
            $this->assertSame(
                $count,
                $this->nodes($xpath, sprintf($query, $this->hasClass($class)))->length,
                sprintf('The class "%s" sits on %d elements of its own kind.', $class, $count),
            );
        }
        // The list keeps the class it had next to the new one.
        $this->assertSame(
            3,
            $this->nodes($xpath, sprintf('//div[%s][%s]', $this->hasClass('academic-persons-grid'), $this->hasClass('academic-persons-itemlist')))->length,
            'Only the three grids of the list carry the class the list had before.',
        );
    }

    #[Test]
    public function thePaginationPartialsCarryTheirClasses(): void
    {
        $this->setUpTestCase('paginatedList', [
            'EXT:academic_persons/Tests/Functional/Plugins/Fixtures/TypoScript/Constants/UngroupedList.typoscript',
        ]);

        $xpath = $this->xpath($this->renderHomePage());
        $list = $this->wrapper($xpath, 'academic-persons-list');
        $this->assertSame(
            1,
            $this->countIn($xpath, $list, sprintf('.//nav[%s]', $this->hasClass('academic-persons-list__pagination'))),
        );
        $this->assertSame(
            1,
            $this->countIn($xpath, $list, sprintf('.//nav[%s][%s]', $this->hasClass('academic-persons-list__alphabet-pagination'), $this->hasClass('alphabetical-pagination'))),
            'The letter navigation keeps the class it had next to the new one.',
        );
    }

    #[Test]
    public function theEmptyStateCarriesItsClass(): void
    {
        $this->setUpTestCase('noProfiles');

        $xpath = $this->xpath($this->renderHomePage());
        $this->assertSame(
            4,
            $this->nodes($xpath, sprintf('//p[%s]', $this->hasClass('academic-persons-empty-state')))->length,
            'All four elements render the empty state partial.',
        );
    }

    #[Test]
    public function groupedListRendersOneGridAndOneHeaderPerGroup(): void
    {
        $this->setUpTestCase('allPlugins');

        $xpath = $this->xpath($this->renderHomePage());
        $list = $this->wrapper($xpath, 'academic-persons-list');

        $this->assertSame(
            'A|M|O',
            $this->textIn($xpath, $list, $this->groupHeaderQuery()),
            'One uppercased group header per first letter of the last name, in the sorted order.',
        );
        $this->assertSame(
            3,
            $this->countIn($xpath, $list, sprintf('.//div[%s]', $this->hasClass('academic-persons-itemlist'))),
            'One item grid per group.',
        );
        $this->assertSame(
            3,
            $this->countIn($xpath, $list, sprintf('.//div[%s][%s]', $this->hasClass('academic-persons-itemlist'), $this->hasClass('row'))),
            'Every item grid keeps its Bootstrap row class.',
        );
        $this->assertGridColumns($xpath, $list, 3, 'list');
        $this->assertItemMarkup($xpath, $list, 3, 'list');
    }

    #[Test]
    public function ungroupedListRendersOneGridWithoutAGroupHeader(): void
    {
        $this->setUpTestCase('allPlugins', [
            'EXT:academic_persons/Tests/Functional/Plugins/Fixtures/TypoScript/Constants/UngroupedList.typoscript',
        ]);

        $xpath = $this->xpath($this->renderHomePage());
        $list = $this->wrapper($xpath, 'academic-persons-list');

        $this->assertSame('', $this->textIn($xpath, $list, $this->groupHeaderQuery()), 'An ungrouped list has no group header.');
        $this->assertSame(
            1,
            $this->countIn($xpath, $list, sprintf('.//div[%s]', $this->hasClass('academic-persons-itemlist'))),
            'One item grid for the whole list.',
        );
        $this->assertGridColumns($xpath, $list, 3, 'list');
        $this->assertItemMarkup($xpath, $list, 3, 'list');
    }

    #[Test]
    public function cardSelectedProfilesAndSelectedContractsRenderTheSameItemMarkup(): void
    {
        $this->setUpTestCase('allPlugins');

        $xpath = $this->xpath($this->renderHomePage());
        foreach (['card' => 'academic-persons-card', 'selected profiles' => 'academic-persons-profiles', 'selected contracts' => 'academic-persons-contracts'] as $plugin => $class) {
            $wrapper = $this->wrapper($xpath, $class);
            $this->assertSame(
                1,
                $this->countIn($xpath, $wrapper, sprintf('.//div[%s]', $this->hasClass('row'))),
                sprintf('The %s plugin renders one row.', $plugin),
            );
            $this->assertGridColumns($xpath, $wrapper, 3, $plugin);
            $this->assertItemMarkup($xpath, $wrapper, 3, $plugin);
        }
    }

    #[Test]
    public function everyPluginRendersTheProfileNamesAndTheirContracts(): void
    {
        $this->setUpTestCase('allPlugins');

        $content = $this->renderHomePage();
        $xpath = $this->xpath($content);
        foreach (self::PLUGIN_WRAPPERS as $plugin => $class) {
            $wrapper = $this->wrapper($xpath, $class);
            $names = $this->textIn($xpath, $wrapper, sprintf('.//*[%s]', $this->hasClass('card-title')));
            foreach (['Achterberg', 'Müllermann', 'O\'Neill'] as $lastName) {
                $this->assertStringContainsString($lastName, $names, sprintf('The %s plugin renders "%s".', $plugin, $lastName));
            }
            $positions = $this->textIn($xpath, $wrapper, sprintf('.//ul[%s]', $this->hasClass('list-group')));
            foreach (['Professor', 'Lecturer', 'Assistant'] as $position) {
                $this->assertStringContainsString($position, $positions, sprintf('The %s plugin renders "%s".', $plugin, $position));
            }
        }
    }

    /**
     * The name and the detail link reach the header partials as values, and those escape
     * them for output. Escaping them a second time on the way - which is what rendering
     * them through a partial does unless the partial hands over the raw text - shows in
     * the markup as `&amp;#039;` and turns the query arguments of the link into text.
     */
    #[Test]
    public function theNameAndTheDetailLinkAreEscapedExactlyOnce(): void
    {
        $this->setUpTestCase('allPlugins');

        $content = $this->renderHomePage();
        $this->assertStringContainsString('O&#039;Neill', $content, 'The apostrophe of the name is escaped.');
        $this->assertStringNotContainsString('&amp;#039;', $content, 'The name is not escaped twice.');
        $this->assertStringNotContainsString('&amp;amp;', $content, 'The detail link is not escaped twice.');

        $xpath = $this->xpath($content);
        $card = $this->wrapper($xpath, 'academic-persons-card');
        $hrefs = [];
        foreach ($this->nodes($xpath, './/a/@href', $card) as $href) {
            $hrefs[] = $href->nodeValue;
        }
        $this->assertNotSame([], $hrefs, 'Every card heading links to the detail view.');
        foreach ($hrefs as $href) {
            $this->assertStringContainsString('/profiles', (string)$href, 'The link targets the configured detail page.');
            $this->assertMatchesRegularExpression(
                '#tx_academicpersons_detail%5Bprofile%5D=\d+&cHash=#',
                (string)$href,
                'The link keeps its query arguments.',
            );
        }
    }

    #[Test]
    public function anEmptyResultRendersTheNoProfilesFoundText(): void
    {
        $this->setUpTestCase('noProfiles');

        $content = $this->renderHomePage();
        $xpath = $this->xpath($content);
        foreach (['list' => 'academic-persons-list', 'card' => 'academic-persons-card', 'selected profiles' => 'academic-persons-profiles'] as $plugin => $class) {
            $wrapper = $this->wrapper($xpath, $class);
            $this->assertSame(
                'No profiles found.',
                $this->textIn($xpath, $wrapper, './/p'),
                sprintf('The %s plugin shows the empty state.', $plugin),
            );
            $this->assertSame(0, $this->countIn($xpath, $wrapper, sprintf('.//div[%s]', $this->hasClass('academic-persons-item'))), $plugin);
        }
        $contracts = $this->wrapper($xpath, 'academic-persons-contracts');
        $this->assertSame(
            'No contracts found.',
            $this->textIn($xpath, $contracts, './/p'),
            'The selected contracts plugin has an empty state of its own.',
        );
    }

    #[Test]
    public function theNameShowsTheAcademicTitle(): void
    {
        $this->setUpTestCase('allPlugins');

        $xpath = $this->xpath($this->renderHomePage());
        foreach (self::PLUGIN_WRAPPERS as $plugin => $class) {
            $wrapper = $this->wrapper($xpath, $class);
            $names = $this->textIn($xpath, $wrapper, sprintf('.//*[%s]', $this->hasClass('card-title')));
            $this->assertStringContainsString(
                'Prof. Dr. Anna Bettina Achterberg',
                $names,
                sprintf('The %s plugin renders the academic title in front of the name.', $plugin),
            );
        }
    }

    /**
     * The name is joined from four parts and an empty one is left out. The old item built
     * it as "{firstName} {middleName} {lastName}" with literal spaces, so a profile
     * without a middle name carried two of them - "Max  Müllermann" in the markup.
     *
     * This asserts the raw markup rather than the text content: every text helper of this
     * class normalises whitespace, which is exactly what would hide the difference.
     */
    #[Test]
    public function anEmptyNamePartLeavesNoExtraSpace(): void
    {
        $this->setUpTestCase('allPlugins');

        $content = $this->renderHomePage();
        // Max has no title and no middle name, Anna has both.
        $this->assertStringContainsString('>Max Müllermann</a>', $content);
        $this->assertStringNotContainsString('Max  Müllermann', $content);
        $this->assertStringContainsString('>Prof. Dr. Anna Bettina Achterberg</a>', $content);
    }

    #[Test]
    public function anOverriddenNamePartialReachesEveryPlugin(): void
    {
        $this->setUpTestCase('allPlugins', [
            'EXT:test_profile_partial_overrides/Configuration/TypoScript/PartialOverrides.typoscript',
        ]);

        $xpath = $this->xpath($this->renderHomePage());
        foreach (self::PLUGIN_WRAPPERS as $plugin => $class) {
            $wrapper = $this->wrapper($xpath, $class);
            $names = $this->textIn($xpath, $wrapper, sprintf('.//*[%s]', $this->hasClass('card-title')));
            foreach (['Achterberg', 'Müllermann', 'O\'Neill'] as $lastName) {
                $this->assertStringContainsString(
                    sprintf('NAME-OVERRIDE[%s]', $lastName),
                    $names,
                    sprintf('The %s plugin renders the overridden name partial.', $plugin),
                );
            }
            // The rest of the item is untouched by an override of the name alone.
            $this->assertItemMarkup($xpath, $wrapper, 3, $plugin);
            $this->assertStringContainsString(
                'Professor',
                $this->textIn($xpath, $wrapper, sprintf('.//ul[%s]', $this->hasClass('list-group'))),
                sprintf('The %s plugin still renders the contracts.', $plugin),
            );
        }
    }

    #[Test]
    public function anOverriddenEmptyStatePartialReachesEveryProfilePlugin(): void
    {
        $this->setUpTestCase('noProfiles', [
            'EXT:test_profile_partial_overrides/Configuration/TypoScript/PartialOverrides.typoscript',
        ]);

        $xpath = $this->xpath($this->renderHomePage());
        foreach (self::PLUGIN_WRAPPERS as $plugin => $class) {
            $wrapper = $this->wrapper($xpath, $class);
            $this->assertSame(
                'EMPTY-STATE-OVERRIDE',
                $this->textIn($xpath, $wrapper, './/p'),
                sprintf('The %s plugin renders the overridden empty state.', $plugin),
            );
        }
    }

    /**
     * The shipped result count renders nothing: printing a number by default would change
     * every list that exists today.
     */
    #[Test]
    public function theShippedResultCountRendersNothing(): void
    {
        $this->setUpTestCase('allPlugins');

        $xpath = $this->xpath($this->renderHomePage());
        $list = $this->wrapper($xpath, 'academic-persons-list');
        $this->assertSame('', $this->textIn($xpath, $list, './/p'), 'A list with profiles renders no paragraph of its own.');
    }

    #[Test]
    public function anOverriddenResultCountPartialShowsTheNumberOfProfiles(): void
    {
        $this->setUpTestCase('allPlugins', [
            'EXT:test_profile_partial_overrides/Configuration/TypoScript/PartialOverrides.typoscript',
        ]);

        $xpath = $this->xpath($this->renderHomePage());
        $list = $this->wrapper($xpath, 'academic-persons-list');
        $this->assertSame('RESULT-COUNT-OVERRIDE[3 of 3]', $this->textIn($xpath, $list, './/p'));
    }

    /**
     * The count is the page and the total is the whole result, which is the pair a
     * "showing x of y" needs. The paginator cannot supply the total: its
     * `getTotalAmountOfItems()` is protected on both supported core versions, so Fluid
     * cannot reach it and a template that tries renders an empty string.
     */
    #[Test]
    public function anOverriddenResultCountSeparatesThePageFromTheTotal(): void
    {
        $this->setUpTestCase('paginatedList', [
            'EXT:academic_persons/Tests/Functional/Plugins/Fixtures/TypoScript/Constants/UngroupedList.typoscript',
            'EXT:test_profile_partial_overrides/Configuration/TypoScript/PartialOverrides.typoscript',
        ]);

        $xpath = $this->xpath($this->renderHomePage());
        $list = $this->wrapper($xpath, 'academic-persons-list');
        $this->assertSame(
            'RESULT-COUNT-OVERRIDE[2 of 3]',
            $this->textIn($xpath, $list, sprintf('.//p[%s]', $this->hasClass('result-count-override'))),
            'Two of the three profiles are on the first page.',
        );
    }

    /**
     * The grid is the one partial four elements share, so its override is the one that has
     * to reach all four - and the one an integrator most easily expects to reach the
     * contacts element of EXT:academic_contacts4pages too, which builds a grid of its own.
     */
    #[Test]
    public function anOverriddenGridPartialReachesEveryElementOfThisExtension(): void
    {
        $this->setUpTestCase('allPlugins', [
            'EXT:test_profile_partial_overrides/Configuration/TypoScript/GridOverride.typoscript',
        ]);

        $xpath = $this->xpath($this->renderHomePage());
        foreach (self::PLUGIN_WRAPPERS as $plugin => $class) {
            $wrapper = $this->wrapper($xpath, $class);
            $this->assertSame(
                3,
                $this->countIn($xpath, $wrapper, sprintf('.//div[%s]', $this->hasClass('grid-override__item'))),
                sprintf('The %s plugin renders the overridden grid.', $plugin),
            );
            $this->assertSame(
                0,
                $this->countIn($xpath, $wrapper, sprintf('.//div[%s]', $this->hasClass('academic-persons-item'))),
                sprintf('The %s plugin renders nothing of the shipped grid.', $plugin),
            );
        }
    }

    /**
     * A template that renders the item outside the persons plugins - a project's own
     * template, the contacts content element - links the profiles without depending on
     * the plugin setting.
     */
    #[Test]
    public function anExplicitDetailPageWinsOverTheSetting(): void
    {
        $this->setUpTestCase('cardPlugin', [
            'EXT:test_profile_partial_overrides/Configuration/TypoScript/ExplicitDetailPage.typoscript',
        ]);

        $xpath = $this->xpath($this->renderHomePage());
        $card = $this->wrapper($xpath, 'academic-persons-card');
        $hrefs = [];
        foreach ($this->nodes($xpath, './/a/@href', $card) as $href) {
            $hrefs[] = (string)$href->nodeValue;
        }
        $this->assertNotSame([], $hrefs);
        foreach ($hrefs as $href) {
            $this->assertStringContainsString('/people', $href, 'The passed detail page is used.');
            $this->assertStringNotContainsString('/profiles', $href, 'The plugin setting is not.');
        }
    }
}
