<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Plugins;

use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;

/**
 * The detail link of a profile item: the setting `plugin.tx_academicpersons.detailLink`
 * and the choice of the same name in the plugin options of an element.
 *
 * The list, the card, the selected profiles and the selected contracts sit on `/home`
 * (content elements 1 to 4), the list-and-detail element on `/list-and-detail` (5) and a
 * list in the table view mode on `/table` (6). No detail page is configured, so a detail
 * link points to the page of its element, the way it always has.
 */
final class AcademicPersonsDetailLinkTest extends AbstractAcademicPersonsTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    private const CONSTANTS = 'EXT:academic_persons/Tests/Functional/Plugins/Fixtures/TypoScript/Constants/';
    private const AGGREGATE_SET = 'fgtclb/academic-persons';
    private const NAMES = ['Max Müllermann', 'Anna Achterberg'];

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration();
        $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsDetailLink/records.csv');
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    /**
     * The four elements that offer the choice, with the page they sit on.
     *
     * @return \Generator<string, array{0: int}>
     */
    public static function elementsWithTheChoiceDataProvider(): \Generator
    {
        yield 'list' => [1];
        yield 'card' => [2];
        yield 'selected profiles' => [3];
        yield 'selected contracts' => [4];
    }

    /**
     * @param list<string> $constants file names below the shared constants fixture folder
     */
    private function setUpStaticTemplateSite(array $constants = []): void
    {
        $this->setUpFrontendRootPage(
            pageId: 1,
            typoScriptFiles: [
                'constants' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript',
                    'EXT:academic_persons/Configuration/TypoScript/Default/constants.typoscript',
                    ...array_map(static fn(string $file): string => self::CONSTANTS . $file . '.typoscript', $constants),
                ],
                'setup' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript',
                    'EXT:academic_persons/Configuration/TypoScript/Default/setup.typoscript',
                    'EXT:academic_persons/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/Rendering.typoscript',
                ],
            ],
        );
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration('EN', '/'),
        ]);
    }

    /**
     * A site that has no TypoScript record of the extension and gets everything from the
     * persons set.
     *
     * @param array<string, string> $settings
     */
    private function setUpSiteSetSite(array $settings): void
    {
        // The page object only; "clear = 0" keeps what the sets contribute.
        $this->getConnectionPool()->getConnectionForTable('sys_template')->insert(
            'sys_template',
            [
                'pid' => 1,
                'root' => 1,
                'clear' => 0,
                'title' => 'Site package',
                'constants' => '',
                'config' => '@import \'EXT:academic_persons/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/Rendering.typoscript\'',
            ],
        );
        $this->writeSiteConfiguration(
            // The site identifier is part of several caches the test instance keeps for
            // the whole class, so differently configured sites need different ones.
            identifier: 'acme-' . substr(md5(json_encode($settings, JSON_THROW_ON_ERROR)), 0, 10),
            site: $this->buildSiteConfiguration(
                rootPageId: 1,
                base: self::FRONTEND_PLUGIN_TEST_BASE,
                additionalRootConfiguration: [
                    'dependencies' => ['typo3/fluid-styled-content', self::AGGREGATE_SET],
                    'settings' => $settings,
                ],
            ),
            languages: [
                $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
            ],
        );
    }

    /**
     * Saves the choice into the FlexForm of a content element, as the backend does. The
     * backend stores the empty choice as an empty value, not as a missing field.
     */
    private function chooseDetailLink(int $contentElement, string $choice): void
    {
        $connection = $this->getConnectionPool()->getConnectionForTable('tt_content');
        $flexForm = $connection->select(['pi_flexform'], 'tt_content', ['uid' => $contentElement])->fetchOne();
        $this->assertIsString($flexForm);
        $connection->update(
            'tt_content',
            [
                'pi_flexform' => str_replace(
                    '</language>',
                    sprintf('<field index="settings.detailLink"><value index="vDEF">%s</value></field></language>', $choice),
                    $flexForm,
                ),
            ],
            ['uid' => $contentElement],
        );
    }

    private function render(string $path): \DOMXPath
    {
        $document = new \DOMDocument();
        $document->loadHTML('<?xml encoding="utf-8" ?>' . $this->renderFrontendPage('https://www.acme.com' . $path), LIBXML_NOERROR);

        return new \DOMXPath($document);
    }

    /**
     * The names an element renders, each with the address it links to, or null for a name
     * without a link.
     *
     * @return array<string, string|null>
     */
    private function namesOf(\DOMXPath $xpath, int $contentElement): array
    {
        $headings = $xpath->query(sprintf(
            '//*[@id = "c%d"]//*[contains(concat(" ", normalize-space(@class), " "), " academic-persons-item__name ")]'
            . ' | //*[@id = "c%1$d"]//table/tbody/tr/th',
            $contentElement,
        ));
        $this->assertInstanceOf(\DOMNodeList::class, $headings);
        $names = [];
        foreach ($headings as $heading) {
            $this->assertInstanceOf(\DOMElement::class, $heading);
            $anchors = $heading->getElementsByTagName('a');
            $this->assertLessThanOrEqual(1, $anchors->length);
            $anchor = $anchors->item(0);
            $names[trim(preg_replace('/\s+/u', ' ', $heading->textContent) ?? '')] = $anchor?->getAttribute('href');
        }
        $this->assertNotSame([], $names, sprintf('Content element %d rendered no profile.', $contentElement));

        return $names;
    }

    private function assertNamesWithoutLink(\DOMXPath $xpath, int $contentElement): void
    {
        $names = $this->namesOf($xpath, $contentElement);
        $this->assertEqualsCanonicalizing(self::NAMES, array_keys($names));
        $this->assertSame([null], array_values(array_unique($names, SORT_REGULAR)), sprintf('Content element %d links a name.', $contentElement));
    }

    /**
     * Every name links to the detail view of its profile, on the given page and through the
     * given plugin.
     */
    private function assertNamesLinkTo(\DOMXPath $xpath, int $contentElement, string $path, string $pluginNamespace = 'tx_academicpersons_detail'): void
    {
        $names = $this->namesOf($xpath, $contentElement);
        $this->assertEqualsCanonicalizing(self::NAMES, array_keys($names));
        foreach (['Max Müllermann' => 1, 'Anna Achterberg' => 2] as $name => $uid) {
            $href = $names[$name];
            $this->assertIsString($href, sprintf('Content element %d renders "%s" without a link.', $contentElement, $name));
            $this->assertStringStartsWith($path . '?', $href);
            $this->assertStringContainsString(sprintf('%s%%5Bprofile%%5D=%d', $pluginNamespace, $uid), $href);
        }
    }

    #[DataProvider('elementsWithTheChoiceDataProvider')]
    #[Test]
    public function withoutConfigurationTheNameLinksToTheDetailViewOnTheSamePage(int $contentElement): void
    {
        $this->setUpStaticTemplateSite();

        $this->assertNamesLinkTo($this->render('/home'), $contentElement, '/home');
    }

    #[Test]
    public function withoutConfigurationTheTableLinksTheName(): void
    {
        $this->setUpStaticTemplateSite();

        $this->assertNamesLinkTo($this->render('/table'), 6, '/table');
    }

    #[DataProvider('elementsWithTheChoiceDataProvider')]
    #[Test]
    public function theSiteWithoutDetailPagesRendersTheNameAsText(int $contentElement): void
    {
        $this->setUpStaticTemplateSite(['DetailLinkNone']);

        $this->assertNamesWithoutLink($this->render('/home'), $contentElement);
    }

    /**
     * An element saved with the empty choice, "Use the site setting", follows the site.
     */
    #[DataProvider('elementsWithTheChoiceDataProvider')]
    #[Test]
    public function anElementWithTheEmptyChoiceFollowsTheSite(int $contentElement): void
    {
        $this->chooseDetailLink($contentElement, '');
        $this->setUpStaticTemplateSite(['DetailLinkNone']);

        $this->assertNamesWithoutLink($this->render('/home'), $contentElement);
    }

    #[Test]
    public function theTableRendersTheNameAsTextAsWell(): void
    {
        $this->setUpStaticTemplateSite(['DetailLinkNone']);

        $this->assertNamesWithoutLink($this->render('/table'), 6);
    }

    #[Test]
    public function oneElementWithoutLinkLeavesTheOthersLinked(): void
    {
        $this->chooseDetailLink(3, 'none');
        $this->setUpStaticTemplateSite();

        $xpath = $this->render('/home');
        $this->assertNamesWithoutLink($xpath, 3);
        foreach ([1, 2, 4] as $contentElement) {
            $this->assertNamesLinkTo($xpath, $contentElement, '/home');
        }
    }

    #[Test]
    public function oneElementWithLinkLinksOnASiteWithoutDetailPages(): void
    {
        $this->chooseDetailLink(1, 'link');
        $this->setUpStaticTemplateSite(['DetailLinkNone']);

        $xpath = $this->render('/home');
        $this->assertNamesLinkTo($xpath, 1, '/home');
        foreach ([2, 3, 4] as $contentElement) {
            $this->assertNamesWithoutLink($xpath, $contentElement);
        }
    }

    /**
     * The list-and-detail element shows the detail view itself.
     */
    #[Test]
    public function theListAndDetailElementLinksOnASiteWithoutDetailPages(): void
    {
        $this->setUpStaticTemplateSite(['DetailLinkNone']);

        $this->assertNamesLinkTo($this->render('/list-and-detail'), 5, '/list-and-detail', 'tx_academicpersons_listanddetail');
    }

    /**
     * A choice stored before the element type was changed to list-and-detail does not
     * take its links away either.
     */
    #[Test]
    public function theListAndDetailElementLinksWhateverItsStoredChoice(): void
    {
        $this->chooseDetailLink(5, 'none');
        $this->setUpStaticTemplateSite();

        $this->assertNamesLinkTo($this->render('/list-and-detail'), 5, '/list-and-detail', 'tx_academicpersons_listanddetail');
    }

    #[Test]
    public function aPassedDetailPageLinksOnASiteWithoutDetailPages(): void
    {
        $this->setUpStaticTemplateSite(['DetailLinkNone', 'DetailLinkPassedDetailPage']);

        $this->assertNamesLinkTo($this->render('/home'), 1, '/profiles');
    }

    #[DataProvider('elementsWithTheChoiceDataProvider')]
    #[Test]
    public function theSiteSettingRendersTheNameAsText(int $contentElement): void
    {
        $this->setUpSiteSetSite(['plugin.tx_academicpersons.detailLink' => 'none']);

        $this->assertNamesWithoutLink($this->render('/home'), $contentElement);
    }

    #[DataProvider('elementsWithTheChoiceDataProvider')]
    #[Test]
    public function withoutASiteSettingTheSiteSetLinksTheName(int $contentElement): void
    {
        $this->setUpSiteSetSite([]);

        $this->assertNamesLinkTo($this->render('/home'), $contentElement, '/home');
    }
}
