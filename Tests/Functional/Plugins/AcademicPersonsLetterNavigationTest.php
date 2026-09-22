<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Plugins;

use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;

/**
 * The letter navigation of the persons list, as the **shipped** templates render it -
 * `EXT:test_plugin_templates` is deliberately not loaded, because the markup is what is
 * under test.
 */
final class AcademicPersonsLetterNavigationTest extends AbstractAcademicPersonsTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    private const NAVIGATION_CLASS = 'academic-persons-list__alphabet-pagination';

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

    private function setUpTestCase(string $dataSet): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsLetterNavigation/' . $dataSet . '.csv');
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
}
