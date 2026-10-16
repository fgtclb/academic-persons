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
 * The view mode a visitor chooses in a selected profiles or selected contracts element
 * belongs to that element: two elements of the same type on one page keep their modes
 * apart, and a translated element answers to the uid of its default-language record.
 *
 * Every element of the fixture offers the switch between the tiles and the table, with
 * the tiles as the default. `/selected-profiles` holds two selected profiles elements,
 * `/selected-contracts` two selected contracts elements, both pages translated to German
 * with their elements, and `/mixed` one element of each type. An element is found by its
 * header, which differs per element and per language.
 */
final class AcademicPersonsSelectedViewModePerElementTest extends AbstractAcademicPersonsTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
        'DE' => ['id' => 1, 'title' => 'Deutsch', 'locale' => 'de_DE.UTF8', 'iso' => 'de', 'hrefLang' => 'de-DE', 'direction' => ''],
    ];

    private const SWITCH_CLASS = 'ace-view-mode-switch';

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
     * @param 'strict'|'free'|null $germanFallbackType The fallback type of the German
     *        language, or null for a site in English only.
     */
    private function setUpTestCase(?string $germanFallbackType = null): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsSelectedViewModePerElement/records.csv');
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
        $languages = [
            $this->buildDefaultLanguageConfiguration(
                identifier: 'EN',
                base: '/',
            ),
        ];
        if ($germanFallbackType !== null) {
            $languages[] = $this->buildLanguageConfiguration(
                identifier: 'DE',
                base: '/de/',
                fallbackIdentifiers: $germanFallbackType === 'strict' ? [] : ['EN'],
                fallbackType: $germanFallbackType,
            );
        }
        $this->writeFrontendPluginTestSite($languages);
    }

    private function render(string $uri): \DOMXPath
    {
        $document = new \DOMDocument();
        $document->loadHTML('<?xml encoding="utf-8" ?>' . $this->renderFrontendPage('https://www.acme.com' . $uri), LIBXML_NOERROR);

        return new \DOMXPath($document);
    }

    /**
     * A request with plugin arguments, carrying the cHash a link would carry.
     *
     * @param array<string, mixed> $arguments
     */
    private function renderWithArguments(string $path, int $pageId, string $pluginNamespace, array $arguments): \DOMXPath
    {
        $query = HttpUtility::buildQueryString([$pluginNamespace => $arguments]);
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

    /**
     * The frame of the content element with the given header.
     */
    private function element(\DOMXPath $xpath, string $header): \DOMNode
    {
        $elements = $this->nodes($xpath, sprintf('//div[%s][./header//*[normalize-space(.)="%s"]]', $this->hasClass('frame'), $header));
        $this->assertSame(1, $elements->length, sprintf('Exactly one content element "%s" is expected.', $header));
        $element = $elements->item(0);
        $this->assertInstanceOf(\DOMNode::class, $element);

        return $element;
    }

    /**
     * Whether the element renders its items as a table, asserting that it renders exactly
     * one of the two modes.
     */
    private function rendersTable(\DOMXPath $xpath, string $header): bool
    {
        $element = $this->element($xpath, $header);
        $tables = $this->nodes($xpath, './/table', $element)->length;
        $tiles = $this->nodes($xpath, sprintf('.//article[%s]', $this->hasClass('ace-item')), $element)->length;
        $this->assertSame(1, (int)($tables > 0) + (int)($tiles > 0), sprintf('"%s" is expected to render either the table or the tiles.', $header));

        return $tables > 0;
    }

    /**
     * The link of the element's switch at the given position: the tiles first, the table
     * second, in the order of the allowed modes. The position, not the label, finds it, so
     * it does not depend on the language of the page.
     */
    private function switchLink(\DOMXPath $xpath, string $header, int $position): \DOMElement
    {
        $links = $this->nodes(
            $xpath,
            sprintf('.//nav[%s]//li[%d]/a', $this->hasClass(self::SWITCH_CLASS), $position),
            $this->element($xpath, $header),
        );
        $this->assertSame(1, $links->length, sprintf('The switch of "%s" is expected to have a link at position %d.', $header, $position));
        $link = $links->item(0);
        $this->assertInstanceOf(\DOMElement::class, $link);

        return $link;
    }

    private function tableLink(\DOMXPath $xpath, string $header): string
    {
        return $this->switchLink($xpath, $header, 2)->getAttribute('href');
    }

    /**
     * The view mode argument of a link in the namespace of the plugin, null without one.
     */
    private function viewModeArgument(string $href, string $pluginNamespace): mixed
    {
        parse_str((string)parse_url($href, PHP_URL_QUERY), $query);
        $arguments = $query[$pluginNamespace] ?? [];
        $this->assertIsArray($arguments);

        return $arguments['viewMode'] ?? null;
    }

    /**
     * @return \Generator<string, array{0: string, 1: string, 2: string, 3: string, 4: int, 5: int}>
     */
    public static function elementTypesDataProvider(): \Generator
    {
        yield 'selected profiles' => ['/selected-profiles', 'tx_academicpersons_selectedprofiles', 'First profiles', 'Second profiles', 1, 2];
        yield 'selected contracts' => ['/selected-contracts', 'tx_academicpersons_selectedcontracts', 'First contracts', 'Second contracts', 3, 4];
    }

    /**
     * Choosing the table in one element renders that element as a table and leaves the
     * other one in its default mode. A switch link carries the mode under the uid of its
     * own element and nothing for the other one.
     */
    #[DataProvider('elementTypesDataProvider')]
    #[Test]
    public function choosingAModeInOneElementLeavesTheOtherElementOfTheSameType(
        string $path,
        string $pluginNamespace,
        string $first,
        string $second,
        int $firstUid,
        int $secondUid,
    ): void {
        $this->setUpTestCase();

        $page = $this->render($path);
        $this->assertFalse($this->rendersTable($page, $first));
        $this->assertFalse($this->rendersTable($page, $second));
        $firstTableLink = $this->tableLink($page, $first);

        $firstChosen = $this->render($firstTableLink);
        $this->assertTrue($this->rendersTable($firstChosen, $first));
        $this->assertFalse($this->rendersTable($firstChosen, $second));
        $this->assertSame([(string)$firstUid => 'table'], $this->viewModeArgument($firstTableLink, $pluginNamespace));
        $this->assertSame([(string)$secondUid => 'table'], $this->viewModeArgument($this->tableLink($page, $second), $pluginNamespace));
        $this->assertSame('true', $this->switchLink($firstChosen, $first, 2)->getAttribute('aria-current'));
        $this->assertSame('true', $this->switchLink($firstChosen, $second, 1)->getAttribute('aria-current'));
        // The link back to the default mode is the page itself, without a mode.
        $this->assertNull($this->viewModeArgument($this->switchLink($firstChosen, $first, 1)->getAttribute('href'), $pluginNamespace));
        // The other element's link carries its own key only, so it does not keep the choice
        // of the first element.
        $secondTableLink = $this->tableLink($firstChosen, $second);
        $this->assertSame([(string)$secondUid => 'table'], $this->viewModeArgument($secondTableLink, $pluginNamespace));

        $secondChosen = $this->render($secondTableLink);
        $this->assertFalse($this->rendersTable($secondChosen, $first));
        $this->assertTrue($this->rendersTable($secondChosen, $second));
    }

    /**
     * @return \Generator<string, array{0: string, 1: int, 2: string, 3: string, 4: string, 5: mixed}>
     */
    public static function foreignViewModeArgumentDataProvider(): \Generator
    {
        foreach (self::elementTypesDataProvider() as $label => [$path, $pluginNamespace, $first, $second]) {
            $pageId = $path === '/selected-profiles' ? 10 : 20;
            yield $label . ', a mode without an element' => [$path, $pageId, $pluginNamespace, $first, $second, 'table'];
            yield $label . ', a mode of an element not on the page' => [$path, $pageId, $pluginNamespace, $first, $second, [99 => 'table']];
        }
    }

    /**
     * A mode that names no element of the page, or no element at all as the argument did
     * before it was keyed, changes no element.
     */
    #[DataProvider('foreignViewModeArgumentDataProvider')]
    #[Test]
    public function aModeThatNamesNoElementOfThePageIsIgnored(
        string $path,
        int $pageId,
        string $pluginNamespace,
        string $first,
        string $second,
        mixed $viewMode,
    ): void {
        $this->setUpTestCase();

        $xpath = $this->renderWithArguments($path, $pageId, $pluginNamespace, ['viewMode' => $viewMode]);

        $this->assertFalse($this->rendersTable($xpath, $first));
        $this->assertFalse($this->rendersTable($xpath, $second));
    }

    /**
     * A selected profiles and a selected contracts element have plugin namespaces of their
     * own, so a mode chosen in one never reached the other one, and it still does not.
     */
    #[Test]
    public function elementsOfDifferentTypesKeepTheirModesApart(): void
    {
        $this->setUpTestCase();

        $page = $this->render('/mixed');
        $profilesChosen = $this->render($this->tableLink($page, 'Mixed profiles'));
        $this->assertTrue($this->rendersTable($profilesChosen, 'Mixed profiles'));
        $this->assertFalse($this->rendersTable($profilesChosen, 'Mixed contracts'));

        $contractsChosen = $this->render($this->tableLink($page, 'Mixed contracts'));
        $this->assertFalse($this->rendersTable($contractsChosen, 'Mixed profiles'));
        $this->assertTrue($this->rendersTable($contractsChosen, 'Mixed contracts'));
    }

    /**
     * @return \Generator<string, array{0: 'strict'|'free', 1: string, 2: string, 3: string, 4: string, 5: string, 6: int}>
     */
    public static function translatedElementsDataProvider(): \Generator
    {
        foreach (['strict', 'free'] as $fallbackType) {
            yield 'selected profiles, ' . $fallbackType => [$fallbackType, '/selected-profiles', 'tx_academicpersons_selectedprofiles', 'First profiles', 'Erste Profile', 'Zweite Profile', 1];
            yield 'selected contracts, ' . $fallbackType => [$fallbackType, '/selected-contracts', 'tx_academicpersons_selectedcontracts', 'First contracts', 'Erste Verträge', 'Zweite Verträge', 3];
        }
    }

    /**
     * A translated element keys its mode by the uid of its default-language record, with
     * the translation overlaid on it ("strict") as well as selected in its own language
     * ("free"). Its links carry that uid, and a link of the default language page keeps
     * working on the translated page.
     *
     * @param 'strict'|'free' $fallbackType
     */
    #[DataProvider('translatedElementsDataProvider')]
    #[Test]
    public function aTranslatedElementUsesTheUidOfItsDefaultLanguageRecord(
        string $fallbackType,
        string $path,
        string $pluginNamespace,
        string $firstDefault,
        string $firstTranslated,
        string $secondTranslated,
        int $firstUid,
    ): void {
        $this->setUpTestCase($fallbackType);

        $page = $this->render('/de' . $path);
        $this->assertFalse($this->rendersTable($page, $firstTranslated));
        $tableLink = $this->tableLink($page, $firstTranslated);
        $this->assertStringStartsWith('/de' . $path, $tableLink);

        $chosen = $this->render($tableLink);
        $this->assertTrue($this->rendersTable($chosen, $firstTranslated));
        $this->assertFalse($this->rendersTable($chosen, $secondTranslated));
        $this->assertSame([(string)$firstUid => 'table'], $this->viewModeArgument($tableLink, $pluginNamespace));

        $defaultLanguageLink = $this->tableLink($this->render($path), $firstDefault);
        $translatedFromDefault = $this->render('/de' . $path . '?' . (string)parse_url($defaultLanguageLink, PHP_URL_QUERY));
        $this->assertTrue($this->rendersTable($translatedFromDefault, $firstTranslated));
        $this->assertFalse($this->rendersTable($translatedFromDefault, $secondTranslated));
    }
}
