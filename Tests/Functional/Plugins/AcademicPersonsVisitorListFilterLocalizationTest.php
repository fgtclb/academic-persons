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
 * The visitor filters of a list restricted by the editor, rendered in a translation.
 *
 * The restriction of the content element names default language uids, and so does a
 * filter value. In a translation the options are the translated records, and each still
 * has to count as the record the restriction names, in every language mode of the site.
 *
 * The fixture is the one of {@see AcademicPersonsVisitorListFilterTest}, with German
 * translations of the list page, the list element, the function types and the units. The
 * profiles are not translated, so only the fallback mode lists them.
 */
final class AcademicPersonsVisitorListFilterLocalizationTest extends AbstractAcademicPersonsTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
        'DE' => ['id' => 1, 'title' => 'Deutsch', 'locale' => 'de_DE.UTF8', 'iso' => 'de', 'hrefLang' => 'de-DE', 'direction' => ''],
    ];

    private const LIST_ELEMENT = 1;
    private const LIST_ELEMENT_DE = 11;

    private const PROFESSOR = 1;
    private const LECTURER = 2;
    private const ASSISTANT = 3;

    private const PHYSICS = 1;
    private const BIOLOGY = 3;

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
     * @param non-empty-string $fallbackType
     */
    private function setUpTestCase(string $fallbackType): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsVisitorListFilter/localized.csv');
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
                    'EXT:academic_persons/Tests/Functional/Plugins/Fixtures/AcademicPersonsVisitorListFilter/TypoScript/FilterOptionsTemplate.typoscript',
                ],
            ],
        );
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration(
                identifier: 'EN',
                base: '/',
            ),
            $this->buildLanguageConfiguration(
                identifier: 'DE',
                base: '/de/',
                fallbackIdentifiers: $fallbackType === 'fallback' ? ['EN'] : [],
                fallbackType: $fallbackType,
            ),
        ]);
        // Both the list element and its translation, which carries plugin options of its own.
        foreach ([self::LIST_ELEMENT, self::LIST_ELEMENT_DE] as $contentElement) {
            $this->addFlexFormFields($contentElement, [
                'settings.filter.functionType' => '1',
                'settings.filter.organisationalUnit' => '1',
                'settings.functionTypes' => self::PROFESSOR . ',' . self::LECTURER,
                'settings.organisationalUnits' => self::BIOLOGY . ',' . self::PHYSICS,
            ]);
        }
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

    /**
     * @param array<string, mixed> $demand
     */
    private function renderList(string $languagePrefix, array $demand): \DOMXPath
    {
        $query = HttpUtility::buildQueryString(['tx_academicpersons_list' => ['demand' => $demand]]);
        $cacheHash = GeneralUtility::makeInstance(CacheHashCalculator::class)
            ->generateForParameters('id=2&' . $query);
        $document = new \DOMDocument();
        $document->loadHTML(
            '<?xml encoding="utf-8" ?>' . $this->renderFrontendPage('https://www.acme.com' . $languagePrefix . 'list?' . $query . '&cHash=' . $cacheHash),
            LIBXML_NOERROR,
        );

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

    /**
     * The options the fixture template prints for one filter, uid to name, in order.
     *
     * @return array<int, string>
     */
    private function filterOptions(\DOMXPath $xpath, string $filter): array
    {
        $options = [];
        foreach ($this->nodes($xpath, sprintf('//ul[@class="test-filter-options__%s"]/li', $filter)) as $option) {
            $this->assertInstanceOf(\DOMElement::class, $option);
            $options[(int)$option->getAttribute('data-uid')] = trim($option->textContent);
        }

        return $options;
    }

    private function keptFunctionTypeFilter(\DOMXPath $xpath): string
    {
        $marker = $this->nodes($xpath, '//div[@class="test-filter-options"]')->item(0);
        $this->assertInstanceOf(\DOMElement::class, $marker, 'The fixture template did not render.');

        return $marker->getAttribute('data-function-type-filter');
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
     * @return \Generator<string, array{0: non-empty-string}>
     */
    public static function languageModesDataProvider(): \Generator
    {
        yield 'fallback' => ['fallback'];
        yield 'strict' => ['strict'];
        yield 'free' => ['free'];
    }

    /**
     * The options are the translated records of the restriction, known by their default
     * language uid, ordered by their translated names, and the filter value of a restricted
     * record is kept. "Hochschullehrer" sorts before "Lehrkraft", while "Professor" sorts
     * after "Lecturer", which tells the order by the translated name from the one by the
     * English name.
     *
     * @param non-empty-string $fallbackType
     */
    #[DataProvider('languageModesDataProvider')]
    #[Test]
    public function aTranslationOffersTheRestrictedRecordsAndKeepsTheFilter(string $fallbackType): void
    {
        $this->setUpTestCase($fallbackType);

        $xpath = $this->renderList('/de/', ['functionTypeFilter' => self::LECTURER]);

        $this->assertSame([self::PROFESSOR => 'Hochschullehrer', self::LECTURER => 'Lehrkraft'], $this->filterOptions($xpath, 'function-types'));
        $this->assertSame([self::BIOLOGY => 'Biologie', self::PHYSICS => 'Physik'], $this->filterOptions($xpath, 'organisational-units'));
        $this->assertSame((string)self::LECTURER, $this->keptFunctionTypeFilter($xpath));
    }

    /**
     * Outside the restriction stays outside in a translation.
     *
     * @param non-empty-string $fallbackType
     */
    #[DataProvider('languageModesDataProvider')]
    #[Test]
    public function aTranslationIgnoresAValueOutsideTheRestriction(string $fallbackType): void
    {
        $this->setUpTestCase($fallbackType);

        $this->assertSame('0', $this->keptFunctionTypeFilter($this->renderList('/de/', ['functionTypeFilter' => self::ASSISTANT])));
    }

    /**
     * The profiles are not translated, so the fallback mode shows them, filtered.
     */
    #[Test]
    public function theFallbackModeListsTheFilteredProfiles(): void
    {
        $this->setUpTestCase('fallback');

        $this->assertSame(['Ben Baker', 'Eva Evans'], $this->listedNames($this->renderList('/de/', ['functionTypeFilter' => self::LECTURER])));
    }
}
