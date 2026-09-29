<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Plugins;

use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Frontend\Page\CacheHashCalculator;

/**
 * The visitor filters of the list and list-and-detail elements: a function type or an
 * organisational unit from the request narrows the list, while the element allows it and
 * the value is one of the options.
 *
 * Six profiles, each contract a pair of function type and unit:
 *
 * - Adams: Professor in Biology
 * - Baker: Lecturer in Biology
 * - Clark: Professor in Chemistry, Professor in Physics - two matching contracts
 * - Davis: no contract
 * - Evans: Professor in Physics, Lecturer in Biology - a professor, and in Biology, but
 *   never both on one contract
 * - Foster: Assistant in Chemistry, and a contract of a hidden function type
 *
 * The names of the function types and units are in reverse uid order, so an option list in
 * uid order differs from one ordered by name. The filter fields are written into the
 * FlexForm of each test's element, the way an editor saves them.
 */
final class AcademicPersonsVisitorListFilterTest extends AbstractAcademicPersonsTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    private const LIST_ELEMENT = 1;
    private const LIST_AND_DETAIL_ELEMENT = 2;
    private const SELECTION_ELEMENT = 3;

    private const PROFESSOR = 1;
    private const LECTURER = 2;
    private const ASSISTANT = 3;
    private const HIDDEN_FUNCTION_TYPE = 4;

    private const PHYSICS = 1;
    private const CHEMISTRY = 2;
    private const BIOLOGY = 3;

    private const EVERYBODY = ['Anna Adams', 'Ben Baker', 'Cora Clark', 'Dan Davis', 'Eva Evans', 'Finn Foster'];

    private const FIXTURE_TEMPLATE = 'EXT:academic_persons/Tests/Functional/Plugins/Fixtures/AcademicPersonsVisitorListFilter/TypoScript/FilterOptionsTemplate.typoscript';

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration();
        $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
        parent::setUp();
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    /**
     * @param list<string> $additionalSetupFiles Setup loaded after the shipped one.
     */
    private function setUpTestCase(array $additionalSetupFiles = []): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsVisitorListFilter/records.csv');
        $this->setUpFrontendRootPage(
            pageId: 1,
            typoScriptFiles: [
                'constants' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript',
                    'EXT:academic_persons/Configuration/TypoScript/Default/constants.typoscript',
                    'EXT:academic_persons/Tests/Functional/Plugins/Fixtures/TypoScript/Constants/DetailPage.typoscript',
                ],
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

    /**
     * Switch the visitor filters of a content element on, as an editor saves them.
     */
    private function enableFilters(int $contentElement, bool $functionType = true, bool $organisationalUnit = true): void
    {
        $this->addFlexFormFields($contentElement, [
            'settings.filter.functionType' => (string)(int)$functionType,
            'settings.filter.organisationalUnit' => (string)(int)$organisationalUnit,
        ]);
    }

    /**
     * @param array<string, string> $fields
     */
    private function addFlexFormFields(int $contentElement, array $fields): void
    {
        $connection = $this->getConnectionPool()->getConnectionForTable('tt_content');
        $flexForm = $connection->select(['pi_flexform'], 'tt_content', ['uid' => $contentElement])->fetchOne();
        $this->assertIsString($flexForm);
        $xml = '';
        foreach ($fields as $field => $value) {
            $xml .= sprintf('<field index="%s"><value index="vDEF">%s</value></field>', $field, htmlspecialchars($value));
        }
        $connection->update(
            'tt_content',
            ['pi_flexform' => str_replace('</language>', $xml . '</language>', $flexForm)],
            ['uid' => $contentElement],
        );
    }

    private function render(string $uri): \DOMXPath
    {
        $document = new \DOMDocument();
        $document->loadHTML('<?xml encoding="utf-8" ?>' . $this->renderFrontendPage('https://www.acme.com' . $uri), LIBXML_NOERROR);

        return new \DOMXPath($document);
    }

    /**
     * The list of a content element with demand arguments, carrying the cHash a link would
     * carry.
     *
     * @param array<string, mixed> $demand
     */
    private function renderList(int $contentElement, array $demand): \DOMXPath
    {
        [$path, $pageId, $pluginNamespace] = match ($contentElement) {
            self::LIST_ELEMENT => ['/list', 2, 'tx_academicpersons_list'],
            self::LIST_AND_DETAIL_ELEMENT => ['/list-and-detail', 3, 'tx_academicpersons_listanddetail'],
            self::SELECTION_ELEMENT => ['/selection', 4, 'tx_academicpersons_list'],
            default => throw new \InvalidArgumentException('Unknown content element.', 1790802601),
        };
        if ($demand === []) {
            return $this->render($path);
        }
        $query = HttpUtility::buildQueryString([$pluginNamespace => ['demand' => $demand]]);
        $cacheHash = GeneralUtility::makeInstance(CacheHashCalculator::class)
            ->generateForParameters('id=' . $pageId . '&' . $query);

        return $this->render($path . '?' . $query . '&cHash=' . $cacheHash);
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

    private function text(\DOMNode $node): string
    {
        return trim((string)preg_replace('#\s+#u', ' ', $node->textContent));
    }

    /**
     * The names the list renders, in order, taken from the item headings.
     *
     * @return list<string>
     */
    private function listedNames(\DOMXPath $xpath): array
    {
        $names = [];
        foreach ($this->nodes($xpath, sprintf('//*[%s]', $this->hasClass('academic-persons-item__name'))) as $heading) {
            $names[] = $this->text($heading);
        }

        return $names;
    }

    /**
     * The options the fixture template prints for one filter, uid to name, in order.
     *
     * @return array<int, string>
     */
    private function filterOptions(\DOMXPath $xpath, string $filter): array
    {
        $options = [];
        foreach ($this->nodes($xpath, sprintf('//ul[%s]/li', $this->hasClass('test-filter-options__' . $filter))) as $option) {
            $this->assertInstanceOf(\DOMElement::class, $option);
            $options[(int)$option->getAttribute('data-uid')] = $this->text($option);
        }

        return $options;
    }

    private function hasFilterOptions(\DOMXPath $xpath): bool
    {
        $marker = $this->nodes($xpath, sprintf('//div[%s]', $this->hasClass('test-filter-options')))->item(0);
        $this->assertInstanceOf(\DOMElement::class, $marker, 'The fixture template did not render.');

        return $marker->getAttribute('data-any') === '1';
    }

    /**
     * The demand arguments of every link of a navigation, one list per link.
     *
     * @return list<array<string, mixed>>
     */
    private function linkDemands(\DOMXPath $xpath, string $navigationClass, string $pluginNamespace): array
    {
        $demands = [];
        foreach ($this->nodes($xpath, sprintf('//nav[%s]//a[@href]', $this->hasClass($navigationClass))) as $link) {
            $this->assertInstanceOf(\DOMElement::class, $link);
            parse_str((string)parse_url($link->getAttribute('href'), PHP_URL_QUERY), $query);
            $demand = $query[$pluginNamespace]['demand'] ?? [];
            $this->assertIsArray($demand);
            ksort($demand);
            $demands[] = $demand;
        }

        return $demands;
    }

    /**
     * @return \Generator<string, array{0: int}>
     */
    public static function filterElementsDataProvider(): \Generator
    {
        yield 'list' => [self::LIST_ELEMENT];
        yield 'list and detail' => [self::LIST_AND_DETAIL_ELEMENT];
    }

    /**
     * Clark has two contracts of the requested function type and is listed once.
     */
    #[DataProvider('filterElementsDataProvider')]
    #[Test]
    public function aFunctionTypeFilterListsTheProfilesWithAContractOfThatType(int $contentElement): void
    {
        $this->setUpTestCase();
        $this->enableFilters($contentElement);

        $this->assertSame(
            ['Anna Adams', 'Cora Clark', 'Eva Evans'],
            $this->listedNames($this->renderList($contentElement, ['functionTypeFilter' => self::PROFESSOR])),
        );
    }

    #[DataProvider('filterElementsDataProvider')]
    #[Test]
    public function anOrganisationalUnitFilterListsTheProfilesWithAContractInThatUnit(int $contentElement): void
    {
        $this->setUpTestCase();
        $this->enableFilters($contentElement);

        $this->assertSame(
            ['Anna Adams', 'Ben Baker', 'Eva Evans'],
            $this->listedNames($this->renderList($contentElement, ['organisationalUnitFilter' => self::BIOLOGY])),
        );
    }

    /**
     * Evans is a professor, and has a contract in Biology, but not as a professor: listing
     * her under "professors in Biology" would be wrong.
     */
    #[Test]
    public function bothFiltersMatchOneAndTheSameContract(): void
    {
        $this->setUpTestCase();
        $this->enableFilters(self::LIST_ELEMENT);

        $this->assertSame(
            ['Anna Adams'],
            $this->listedNames($this->renderList(self::LIST_ELEMENT, [
                'functionTypeFilter' => self::PROFESSOR,
                'organisationalUnitFilter' => self::BIOLOGY,
            ])),
        );
        $this->assertSame(
            ['Ben Baker', 'Eva Evans'],
            $this->listedNames($this->renderList(self::LIST_ELEMENT, [
                'functionTypeFilter' => self::LECTURER,
                'organisationalUnitFilter' => self::BIOLOGY,
            ])),
        );
    }

    /**
     * The baseline of every test below that expects the whole list.
     */
    #[Test]
    public function theListWithoutAFilterShowsEveryProfile(): void
    {
        $this->setUpTestCase();
        $this->enableFilters(self::LIST_ELEMENT);

        $this->assertSame(self::EVERYBODY, $this->listedNames($this->renderList(self::LIST_ELEMENT, [])));
    }

    /**
     * @return \Generator<string, array{0: bool, 1: bool, 2: array<string, int>}>
     */
    public static function disabledFilterDataProvider(): \Generator
    {
        yield 'function type, both options off' => [false, false, ['functionTypeFilter' => self::PROFESSOR]];
        yield 'function type, only the unit option on' => [false, true, ['functionTypeFilter' => self::PROFESSOR]];
        yield 'unit, both options off' => [false, false, ['organisationalUnitFilter' => self::BIOLOGY]];
        yield 'unit, only the function type option on' => [true, false, ['organisationalUnitFilter' => self::BIOLOGY]];
    }

    /**
     * @param array<string, int> $demand
     */
    #[DataProvider('disabledFilterDataProvider')]
    #[Test]
    public function aFilterIsIgnoredWhileTheElementDoesNotOfferIt(bool $functionType, bool $organisationalUnit, array $demand): void
    {
        $this->setUpTestCase();
        $this->enableFilters(self::LIST_ELEMENT, $functionType, $organisationalUnit);

        $this->assertSame(self::EVERYBODY, $this->listedNames($this->renderList(self::LIST_ELEMENT, $demand)));
    }

    /**
     * @return \Generator<string, array{0: array<string, mixed>}>
     */
    public static function valueNotOfferedDataProvider(): \Generator
    {
        yield 'unknown function type' => [['functionTypeFilter' => 999]];
        yield 'hidden function type' => [['functionTypeFilter' => self::HIDDEN_FUNCTION_TYPE]];
        yield 'negative function type' => [['functionTypeFilter' => -1]];
        yield 'function type with a fraction' => [['functionTypeFilter' => '1.5']];
        yield 'function type followed by text' => [['functionTypeFilter' => '1abc']];
        yield 'function type as text' => [['functionTypeFilter' => 'professor']];
        yield 'function type as a list' => [['functionTypeFilter' => [self::PROFESSOR]]];
        yield 'unknown unit' => [['organisationalUnitFilter' => 999]];
        yield 'unit as text' => [['organisationalUnitFilter' => 'biology']];
    }

    /**
     * A value that is no option leaves the list as it is without a value, and the page
     * renders: an argument the property mapping could not convert would fail it.
     *
     * @param array<string, mixed> $demand
     */
    #[DataProvider('valueNotOfferedDataProvider')]
    #[Test]
    public function aValueThatIsNoOptionIsIgnored(array $demand): void
    {
        $this->setUpTestCase();
        $this->enableFilters(self::LIST_ELEMENT);

        $this->assertSame(self::EVERYBODY, $this->listedNames($this->renderList(self::LIST_ELEMENT, $demand)));
    }

    /**
     * The element shows lecturers and professors only. An assistant is no option, and
     * asking for one keeps that list rather than emptying it.
     */
    #[Test]
    public function aValueOutsideTheRestrictionOfTheElementIsIgnored(): void
    {
        $this->setUpTestCase();
        $this->enableFilters(self::LIST_ELEMENT);
        $this->addFlexFormFields(self::LIST_ELEMENT, ['settings.functionTypes' => self::PROFESSOR . ',' . self::LECTURER]);

        $restricted = ['Anna Adams', 'Ben Baker', 'Cora Clark', 'Eva Evans'];
        $this->assertSame($restricted, $this->listedNames($this->renderList(self::LIST_ELEMENT, [])));
        $this->assertSame(
            $restricted,
            $this->listedNames($this->renderList(self::LIST_ELEMENT, ['functionTypeFilter' => self::ASSISTANT])),
        );
        $this->assertSame(
            ['Ben Baker', 'Eva Evans'],
            $this->listedNames($this->renderList(self::LIST_ELEMENT, ['functionTypeFilter' => self::LECTURER])),
        );
    }

    #[Test]
    public function aUnitOutsideTheRestrictionOfTheElementIsIgnored(): void
    {
        $this->setUpTestCase();
        $this->enableFilters(self::LIST_ELEMENT);
        $this->addFlexFormFields(self::LIST_ELEMENT, ['settings.organisationalUnits' => self::CHEMISTRY . ',' . self::PHYSICS]);

        $this->assertSame(
            ['Cora Clark', 'Eva Evans', 'Finn Foster'],
            $this->listedNames($this->renderList(self::LIST_ELEMENT, ['organisationalUnitFilter' => self::BIOLOGY])),
        );
    }

    /**
     * Adams has no lecturer contract. A manual selection lists what the editor selected,
     * in the order selected, and offers no filter.
     */
    #[Test]
    public function aManualSelectionIgnoresTheFilter(): void
    {
        $this->setUpTestCase([self::FIXTURE_TEMPLATE]);
        $this->enableFilters(self::SELECTION_ELEMENT);

        $xpath = $this->renderList(self::SELECTION_ELEMENT, ['functionTypeFilter' => self::LECTURER]);

        $this->assertSame(['Eva Evans', 'Anna Adams'], $this->listedNames($xpath));
        $this->assertFalse($this->hasFilterOptions($xpath));
    }

    /**
     * Three professors on pages of two: two pages, where the whole list has three. Every
     * page link keeps the filter, and the second page holds the third professor.
     */
    #[Test]
    public function thePaginationCountsTheFilteredProfilesAndKeepsTheFilter(): void
    {
        $this->setUpTestCase();
        $this->enableFilters(self::LIST_ELEMENT);
        $this->addFlexFormFields(self::LIST_ELEMENT, [
            'settings.paginationEnabled' => '1',
            'settings.pagination.resultsPerPage' => '2',
        ]);

        $xpath = $this->renderList(self::LIST_ELEMENT, ['functionTypeFilter' => self::PROFESSOR]);

        $this->assertSame(['Anna Adams', 'Cora Clark'], $this->listedNames($xpath));
        $pages = [];
        foreach ($this->linkDemands($xpath, 'academic-persons-list__pagination', 'tx_academicpersons_list') as $demand) {
            $this->assertSame((string)self::PROFESSOR, $demand['functionTypeFilter'] ?? null);
            $pages[] = $demand['currentPage'] ?? '';
        }
        $this->assertSame(['2'], array_values(array_unique($pages)));
        $this->assertSame(
            ['Eva Evans'],
            $this->listedNames($this->renderList(self::LIST_ELEMENT, ['functionTypeFilter' => self::PROFESSOR, 'currentPage' => 2])),
        );
    }

    /**
     * Baker, Davis and Foster are no professors, so their letters lead nowhere under the
     * filter, and the letters that remain keep it.
     */
    #[Test]
    public function theLetterNavigationFollowsTheFilterAndKeepsIt(): void
    {
        $this->setUpTestCase();
        $this->enableFilters(self::LIST_ELEMENT);
        $this->addFlexFormFields(self::LIST_ELEMENT, ['settings.alphabetPaginationEnabled' => '1']);

        $demands = $this->linkDemands(
            $this->renderList(self::LIST_ELEMENT, ['functionTypeFilter' => self::PROFESSOR]),
            'academic-persons-list__alphabet-pagination',
            'tx_academicpersons_list',
        );

        $letters = [];
        foreach ($demands as $demand) {
            $this->assertSame((string)self::PROFESSOR, $demand['functionTypeFilter'] ?? null);
            $letters[] = $demand['alphabetFilter'] ?? '';
        }
        $this->assertSame(['', 'a', 'c', 'e'], $letters);
    }

    /**
     * Hidden records are no options. Names order the options, not uids.
     */
    #[Test]
    public function theListReceivesTheOptionsOfEveryFilterOrderedByName(): void
    {
        $this->setUpTestCase([self::FIXTURE_TEMPLATE]);
        $this->enableFilters(self::LIST_ELEMENT);

        $xpath = $this->renderList(self::LIST_ELEMENT, []);

        $this->assertSame(
            [self::ASSISTANT => 'Assistant', self::LECTURER => 'Lecturer', self::PROFESSOR => 'Professor'],
            $this->filterOptions($xpath, 'function-types'),
        );
        $this->assertSame(
            [self::BIOLOGY => 'Biology', self::CHEMISTRY => 'Chemistry', self::PHYSICS => 'Physics'],
            $this->filterOptions($xpath, 'organisational-units'),
        );
    }

    #[Test]
    public function theOptionsAreTheRecordsTheElementIsRestrictedTo(): void
    {
        $this->setUpTestCase([self::FIXTURE_TEMPLATE]);
        $this->enableFilters(self::LIST_ELEMENT);
        $this->addFlexFormFields(self::LIST_ELEMENT, [
            'settings.functionTypes' => self::PROFESSOR . ',' . self::LECTURER,
            'settings.organisationalUnits' => (string)self::PHYSICS,
        ]);

        $xpath = $this->renderList(self::LIST_ELEMENT, []);

        $this->assertSame([self::LECTURER => 'Lecturer', self::PROFESSOR => 'Professor'], $this->filterOptions($xpath, 'function-types'));
        $this->assertSame([self::PHYSICS => 'Physics'], $this->filterOptions($xpath, 'organisational-units'));
    }

    /**
     * The link of the manual's template example leads to the filtered list, cHash and all,
     * and keeps the letter the visitor chose while it drops the page.
     */
    #[Test]
    public function aLinkBuiltFromTheOptionsLeadsToTheFilteredList(): void
    {
        $this->setUpTestCase([self::FIXTURE_TEMPLATE]);
        $this->enableFilters(self::LIST_ELEMENT);

        $href = $this->filterLink($this->renderList(self::LIST_ELEMENT, ['alphabetFilter' => 'e', 'currentPage' => 2]), 'Lecturer');
        parse_str((string)parse_url($href, PHP_URL_QUERY), $query);
        $this->assertSame(
            ['alphabetFilter' => 'e', 'functionTypeFilter' => (string)self::LECTURER],
            $query['tx_academicpersons_list']['demand'] ?? null,
        );

        $href = $this->filterLink($this->renderList(self::LIST_ELEMENT, []), 'Lecturer');
        $this->assertSame(['Ben Baker', 'Eva Evans'], $this->listedNames($this->render($href)));
    }

    /**
     * The target of the link the fixture template renders for one function type.
     */
    private function filterLink(\DOMXPath $xpath, string $label): string
    {
        $link = $this->nodes($xpath, sprintf('//nav[@class="test-filter-links"]/a[normalize-space(.)="%s"]', $label))->item(0);
        $this->assertInstanceOf(\DOMElement::class, $link);

        return $link->getAttribute('href');
    }

    /**
     * Only the filters the element offers get options, and without any there are none.
     */
    #[Test]
    public function onlyTheOfferedFiltersReceiveOptions(): void
    {
        $this->setUpTestCase([self::FIXTURE_TEMPLATE]);
        $this->enableFilters(self::LIST_ELEMENT, true, false);
        $this->enableFilters(self::LIST_AND_DETAIL_ELEMENT, false, false);

        $xpath = $this->renderList(self::LIST_ELEMENT, []);
        $this->assertTrue($this->hasFilterOptions($xpath));
        $this->assertCount(3, $this->filterOptions($xpath, 'function-types'));
        $this->assertSame([], $this->filterOptions($xpath, 'organisational-units'));

        $this->assertFalse($this->hasFilterOptions($this->renderList(self::LIST_AND_DETAIL_ELEMENT, [])));
    }
}
