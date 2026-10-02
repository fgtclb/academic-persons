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
 * The filter form the shipped list template renders above the profiles, and the redirect
 * its submission answers with. The site imports no route enhancer, so the filtered list
 * is a URL with query arguments. The speaking URLs are covered by
 * {@see \FGTCLB\AcademicPersons\Tests\Functional\Routing\ProfileFilterRouteTest}.
 *
 * The records are those of {@see AcademicPersonsVisitorListFilterTest}: the function types
 * Professor (1), Lecturer (2), Assistant (3) and a hidden one (4), the units Physics (1),
 * Chemistry (2) and Biology (3), named in reverse uid order. Professors are Adams, Clark
 * and Evans.
 */
final class AcademicPersonsVisitorFilterFormTest extends AbstractAcademicPersonsTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    private const LIST_ELEMENT = 1;
    private const LIST_AND_DETAIL_ELEMENT = 2;
    private const SELECTION_ELEMENT = 3;

    private const FORM_CLASS = 'academic-persons-list__filter';

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration();
        $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsVisitorListFilter/records.csv');
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

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    /**
     * Write fields into the FlexForm of a content element, as an editor saves them.
     *
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

    private function enableFilters(int $contentElement, bool $functionType = true, bool $organisationalUnit = true): void
    {
        $this->addFlexFormFields($contentElement, [
            'settings.filter.functionType' => (string)(int)$functionType,
            'settings.filter.organisationalUnit' => (string)(int)$organisationalUnit,
        ]);
    }

    /**
     * @return array{0: string, 1: int, 2: string}
     */
    private function element(int $contentElement): array
    {
        return match ($contentElement) {
            self::LIST_ELEMENT => ['https://www.acme.com/list', 2, 'tx_academicpersons_list'],
            self::LIST_AND_DETAIL_ELEMENT => ['https://www.acme.com/list-and-detail', 3, 'tx_academicpersons_listanddetail'],
            self::SELECTION_ELEMENT => ['https://www.acme.com/selection', 4, 'tx_academicpersons_list'],
            default => throw new \InvalidArgumentException('Unknown content element.', 1791043301),
        };
    }

    /**
     * The URL of the list of a content element with demand arguments, carrying the cHash
     * a link would carry.
     *
     * @param array<string, mixed> $demand
     */
    private function listUrl(int $contentElement, array $demand = []): string
    {
        [$url, $pageId, $pluginNamespace] = $this->element($contentElement);
        if ($demand === []) {
            return $url;
        }
        $query = HttpUtility::buildQueryString([$pluginNamespace => ['demand' => $demand]]);
        $cacheHash = GeneralUtility::makeInstance(CacheHashCalculator::class)
            ->generateForParameters('id=' . $pageId . '&' . $query);

        return $url . '?' . $query . '&cHash=' . $cacheHash;
    }

    private function render(string $url): \DOMXPath
    {
        $document = new \DOMDocument();
        $document->loadHTML('<?xml encoding="utf-8" ?>' . $this->renderFrontendPage($url), LIBXML_NOERROR);

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

    /**
     * @return \DOMNodeList<\DOMNode>
     */
    private function forms(\DOMXPath $xpath): \DOMNodeList
    {
        return $this->nodes($xpath, sprintf("//form[contains(concat(' ', normalize-space(@class), ' '), ' %s ')]", self::FORM_CLASS));
    }

    private function form(\DOMXPath $xpath): \DOMElement
    {
        $forms = $this->forms($xpath);
        $this->assertSame(1, $forms->length, 'Exactly one filter form is expected.');
        $form = $forms->item(0);
        $this->assertInstanceOf(\DOMElement::class, $form);

        return $form;
    }

    /**
     * The options of the select of one demand property, value to label, in order, or null
     * when the form has no such select. The values are numbers, so PHP keys them as
     * integers.
     *
     * @return array<int|string, string>|null
     */
    private function options(\DOMXPath $xpath, string $property): ?array
    {
        $selects = $this->nodes($xpath, sprintf('.//select[contains(@name, "[demand][%s]")]', $property), $this->form($xpath));
        if ($selects->length === 0) {
            return null;
        }
        $options = [];
        foreach ($this->nodes($xpath, './/option', $selects->item(0)) as $option) {
            $this->assertInstanceOf(\DOMElement::class, $option);
            $options[$option->getAttribute('value')] = trim($option->textContent);
        }

        return $options;
    }

    /**
     * The values of the selected options of the select of one demand property.
     *
     * @return list<string>
     */
    private function selected(\DOMXPath $xpath, string $property): array
    {
        $values = [];
        foreach ($this->nodes($xpath, sprintf('.//select[contains(@name, "[demand][%s]")]/option[@selected]', $property), $this->form($xpath)) as $option) {
            $this->assertInstanceOf(\DOMElement::class, $option);
            $values[] = $option->getAttribute('value');
        }

        return $values;
    }

    /**
     * @return list<string>
     */
    private function listedNames(\DOMXPath $xpath): array
    {
        $names = [];
        foreach ($this->nodes($xpath, "//*[contains(concat(' ', normalize-space(@class), ' '), ' academic-persons-item__name ')]") as $heading) {
            $names[] = trim((string)preg_replace('#\s+#u', ' ', $heading->textContent));
        }

        return $names;
    }

    /**
     * The demand of a redirect target, which has to be a URL of the test site with a cHash.
     *
     * @return array<string, mixed>
     */
    private function redirectDemand(string $location, string $pluginNamespace): array
    {
        parse_str((string)parse_url($location, PHP_URL_QUERY), $query);
        $demand = $query[$pluginNamespace]['demand'] ?? [];
        $this->assertIsArray($demand);
        ksort($demand);

        return $demand;
    }

    /**
     * @return \Generator<string, array{0: int}>
     */
    public static function filterElementsDataProvider(): \Generator
    {
        yield 'list' => [self::LIST_ELEMENT];
        yield 'list and detail' => [self::LIST_AND_DETAIL_ELEMENT];
    }

    #[DataProvider('filterElementsDataProvider')]
    #[Test]
    public function noFormRendersWhileTheElementOffersNoFilter(int $contentElement): void
    {
        $xpath = $this->render($this->listUrl($contentElement));

        $this->assertSame(0, $this->forms($xpath)->length);
        $this->assertCount(6, $this->listedNames($xpath));
    }

    /**
     * A manual selection ignores the filters, so it has no options and no form.
     */
    #[Test]
    public function noFormRendersForAManualSelection(): void
    {
        $this->enableFilters(self::SELECTION_ELEMENT);

        $this->assertSame(0, $this->forms($this->render($this->listUrl(self::SELECTION_ELEMENT)))->length);
    }

    #[DataProvider('filterElementsDataProvider')]
    #[Test]
    public function theFormOffersOnlyTheEnabledFilterWithAllItsOptionsByName(int $contentElement): void
    {
        $this->enableFilters($contentElement, organisationalUnit: false);

        $xpath = $this->render($this->listUrl($contentElement));

        $this->assertSame(
            [0 => 'All functions', 3 => 'Assistant', 2 => 'Lecturer', 1 => 'Professor'],
            $this->options($xpath, 'functionTypeFilter'),
        );
        $this->assertNull($this->options($xpath, 'organisationalUnitFilter'));
        // Without a filter no option is marked, and a browser shows the first, "all".
        $this->assertSame([], $this->selected($xpath, 'functionTypeFilter'));
    }

    #[DataProvider('filterElementsDataProvider')]
    #[Test]
    public function theFormOffersBothFiltersWhenBothAreEnabled(int $contentElement): void
    {
        $this->enableFilters($contentElement);

        $xpath = $this->render($this->listUrl($contentElement));

        $this->assertSame(
            [0 => 'All functions', 3 => 'Assistant', 2 => 'Lecturer', 1 => 'Professor'],
            $this->options($xpath, 'functionTypeFilter'),
        );
        $this->assertSame(
            [0 => 'All organisational units', 3 => 'Biology', 2 => 'Chemistry', 1 => 'Physics'],
            $this->options($xpath, 'organisationalUnitFilter'),
        );
    }

    #[Test]
    public function theChoiceOffersOnlyTheRecordsTheElementIsRestrictedTo(): void
    {
        $this->enableFilters(self::LIST_ELEMENT, organisationalUnit: false);
        $this->addFlexFormFields(self::LIST_ELEMENT, ['settings.functionTypes' => '1,3']);

        $this->assertSame(
            [0 => 'All functions', 3 => 'Assistant', 1 => 'Professor'],
            $this->options($this->render($this->listUrl(self::LIST_ELEMENT)), 'functionTypeFilter'),
        );
    }

    #[DataProvider('filterElementsDataProvider')]
    #[Test]
    public function theActiveFilterIsSelected(int $contentElement): void
    {
        $this->enableFilters($contentElement);

        $xpath = $this->render($this->listUrl($contentElement, ['organisationalUnitFilter' => 3]));

        $this->assertSame(['3'], $this->selected($xpath, 'organisationalUnitFilter'));
        $this->assertSame([], $this->selected($xpath, 'functionTypeFilter'));
        $this->assertSame(['Anna Adams', 'Ben Baker', 'Eva Evans'], $this->listedNames($xpath));
    }

    /**
     * The form needs no JavaScript to work, and a strict Content Security Policy blocks
     * none of it: no script element, and no event handler attribute.
     */
    #[Test]
    public function theFormCarriesNoScriptAndNoEventHandler(): void
    {
        $this->enableFilters(self::LIST_ELEMENT);

        $xpath = $this->render($this->listUrl(self::LIST_ELEMENT));
        $form = $this->form($xpath);

        $this->assertSame(0, $this->nodes($xpath, './/script', $form)->length);
        $this->assertSame(0, $this->nodes($xpath, './/@*[starts-with(name(), "on")] | @*[starts-with(name(), "on")]', $form)->length);
        $this->assertSame('post', strtolower($form->getAttribute('method')));
    }

    #[DataProvider('filterElementsDataProvider')]
    #[Test]
    public function aSubmissionRedirectsToTheFilteredList(int $contentElement): void
    {
        $this->enableFilters($contentElement);
        [$url, , $pluginNamespace] = $this->element($contentElement);

        $response = $this->submitFrontendForm($url, self::FORM_CLASS, [$pluginNamespace => ['demand' => ['functionTypeFilter' => '1']]]);

        $location = $this->assertSeeOtherWithCacheHash($response);
        $this->assertStringStartsWith($url . '?', $location);
        $this->assertSame(['functionTypeFilter' => '1'], $this->redirectDemand($location, $pluginNamespace));
        $this->assertSame(['Anna Adams', 'Cora Clark', 'Eva Evans'], $this->listedNames($this->render($location)));
    }

    /**
     * A value that is not one of the options leads to the list without that filter. With
     * nothing left to carry, that is the page itself.
     */
    #[DataProvider('filterElementsDataProvider')]
    #[Test]
    public function aValueThatIsNoOptionRedirectsToTheUnfilteredList(int $contentElement): void
    {
        $this->enableFilters($contentElement);
        [$url, , $pluginNamespace] = $this->element($contentElement);

        $response = $this->submitFrontendForm($url, self::FORM_CLASS, [$pluginNamespace => ['demand' => ['functionTypeFilter' => '4']]]);

        $this->assertSame(303, $response->getStatusCode());
        $this->assertSame($url, $response->getHeaderLine('Location'));
    }

    /**
     * The form keeps the view mode and the letter of the list it is on, and the filtered
     * list starts on its first page.
     */
    #[Test]
    public function aSubmissionKeepsTheViewModeAndTheLetter(): void
    {
        $this->enableFilters(self::LIST_ELEMENT);
        $this->addFlexFormFields(self::LIST_ELEMENT, ['settings.viewMode.enabled' => '1']);
        $pageUrl = $this->listUrl(self::LIST_ELEMENT, ['viewMode' => 'table', 'alphabetFilter' => 'e', 'currentPage' => 2]);

        $response = $this->submitFrontendForm($pageUrl, self::FORM_CLASS, ['tx_academicpersons_list' => ['demand' => ['functionTypeFilter' => '1']]]);

        $location = $this->assertSeeOtherWithCacheHash($response);
        $this->assertSame(
            ['alphabetFilter' => 'e', 'functionTypeFilter' => '1', 'viewMode' => 'table'],
            $this->redirectDemand($location, 'tx_academicpersons_list'),
        );
    }

    /**
     * @return \Generator<string, array{0: array<string, string>}>
     */
    public static function valuesNotCarriedDataProvider(): \Generator
    {
        yield 'a page' => [['currentPage' => '3']];
        yield 'two letters' => [['alphabetFilter' => 'zz']];
        yield 'an upper case letter' => [['alphabetFilter' => 'B']];
        yield 'a letter outside a to z' => [['alphabetFilter' => 'ä']];
        yield 'a view mode the element does not offer' => [['viewMode' => 'table']];
    }

    /**
     * The redirect signs what it carries, so a value the list would never link is not
     * carried, whatever a hand-made submission posts: the page, which a new filter
     * resets, a letter the navigation does not offer, and a view mode while the element
     * offers no switch.
     *
     * @param array<string, string> $posted
     */
    #[DataProvider('valuesNotCarriedDataProvider')]
    #[Test]
    public function aPostedValueTheListWouldNotLinkIsNotCarried(array $posted): void
    {
        $this->enableFilters(self::LIST_ELEMENT);
        $action = $this->form($this->render($this->listUrl(self::LIST_ELEMENT)))->getAttribute('action');

        $response = $this->requestFrontendPage($this->frontendPostRequest(
            str_starts_with($action, '/') ? 'https://www.acme.com' . $action : $action,
            ['tx_academicpersons_list' => ['demand' => ['functionTypeFilter' => '1'] + $posted]],
        ));

        $location = $this->assertSeeOtherWithCacheHash($response);
        $this->assertSame(['functionTypeFilter' => '1'], $this->redirectDemand($location, 'tx_academicpersons_list'));
    }
}
