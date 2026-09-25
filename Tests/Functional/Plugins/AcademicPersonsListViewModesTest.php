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
 * The view modes of the list, selected profiles and selected contracts elements: the mode
 * the editor chose, the visitor switch, and a mode a project adds.
 *
 * The shipped templates render, without `EXT:test_plugin_templates`. Every element sits on
 * a page of its own and shows the same three profiles: Adams and Baker with a contract that
 * has a room, an e-mail address and - Adams only - a phone number, and O'Neill, whose
 * apostrophe shows a name escaped twice and whose title `<Dr.>` one not escaped at all. The
 * list shows two profiles per page and the letter navigation, grouped by the first letter
 * of the last name as the shipped site settings group it.
 *
 * The view mode fields are written into the FlexForm of each test's element, the way an
 * editor saves them, rather than kept as one fixture per combination.
 */
final class AcademicPersonsListViewModesTest extends AbstractAcademicPersonsTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    private const LIST_ELEMENT = 1;
    private const SELECTED_PROFILES_ELEMENT = 2;
    private const SELECTED_CONTRACTS_ELEMENT = 3;
    private const CARD_ELEMENT = 4;
    private const LIST_AND_DETAIL_ELEMENT = 5;

    private const SWITCH_CLASS = 'academic-persons-view-mode-switch';

    private const TABLE_HEADERS = ['Name', 'Position', 'E-mail', 'Phone', 'Room'];

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration();
        $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
        $this->addTestExtensionsToLoad('tests/test-profile-view-modes', 'tests/test-profile-query-constraints');
        parent::setUp();
        ReplaceListDemandListener::$viewMode = null;
    }

    protected function tearDown(): void
    {
        ReplaceListDemandListener::$viewMode = null;
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    /**
     * @param list<string> $additionalConstants Constants assigned after the shipped ones.
     * @param bool $contactViewMode Whether the project view mode "contact" is set up.
     */
    private function setUpTestCase(array $additionalConstants = [], bool $contactViewMode = false): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsListViewModes/records.csv');
        $constants = [
            'EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript',
            'EXT:academic_persons/Configuration/TypoScript/Default/constants.typoscript',
            'EXT:academic_persons/Tests/Functional/Plugins/Fixtures/TypoScript/Constants/DetailPage.typoscript',
        ];
        $setup = [
            'EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript',
            'EXT:academic_persons/Configuration/TypoScript/Default/setup.typoscript',
            'EXT:academic_persons/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/Rendering.typoscript',
        ];
        if ($contactViewMode) {
            $constants[] = 'EXT:test_profile_view_modes/Configuration/TypoScript/constants.typoscript';
            $setup[] = 'EXT:test_profile_view_modes/Configuration/TypoScript/setup.typoscript';
        }
        $this->setUpFrontendRootPage(
            pageId: 1,
            typoScriptFiles: [
                'constants' => $constants,
                'setup' => $setup,
            ],
        );
        if ($additionalConstants !== []) {
            // Appended to the root template the call above wrote, after its file imports.
            $connection = $this->getConnectionPool()->getConnectionForTable('sys_template');
            $constants = $connection->select(['constants'], 'sys_template', ['pid' => 1])->fetchOne();
            $connection->update(
                'sys_template',
                ['constants' => $constants . "\n" . implode("\n", $additionalConstants)],
                ['pid' => 1],
            );
        }
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration(
                identifier: 'EN',
                base: '/',
            ),
        ]);
    }

    /**
     * Save the view mode fields into the FlexForm of a content element, as the backend does.
     */
    private function setViewMode(int $contentElement, string $default, bool $enabled = false): void
    {
        $this->addFlexFormFields($contentElement, [
            'settings.viewMode.enabled' => (string)(int)$enabled,
            'settings.viewMode.default' => $default,
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

    private function text(\DOMNode $node): string
    {
        return trim((string)preg_replace('#\s+#u', ' ', $node->textContent));
    }

    /**
     * @return list<string>
     */
    private function texts(\DOMXPath $xpath, string $query, ?\DOMNode $context = null): array
    {
        $texts = [];
        foreach ($this->nodes($xpath, $query, $context) as $node) {
            $texts[] = $this->text($node);
        }

        return $texts;
    }

    /**
     * The headers of the first table. A list grouped by letter - the shipped default -
     * renders one table per group, each with the same headers.
     *
     * @return list<string>
     */
    private function tableHeaders(\DOMXPath $xpath): array
    {
        return $this->texts($xpath, sprintf('(//div[%s]//table)[1]/thead/tr/th', $this->hasClass('academic-persons-table')));
    }

    /**
     * The rows of the table, each as the text of its cells.
     *
     * @return list<list<string>>
     */
    private function tableRows(\DOMXPath $xpath): array
    {
        $rows = [];
        foreach ($this->nodes($xpath, sprintf('//div[%s]//table/tbody/tr', $this->hasClass('academic-persons-table'))) as $row) {
            $rows[] = $this->texts($xpath, './th|./td', $row);
        }

        return $rows;
    }

    private function gridItemCount(\DOMXPath $xpath): int
    {
        return $this->nodes($xpath, sprintf('//*[%s]', $this->hasClass('academic-persons-grid__item')))->length;
    }

    /**
     * The link of the switch whose text is the label, as an element.
     */
    private function switchLink(\DOMXPath $xpath, string $label): \DOMElement
    {
        $links = $this->nodes($xpath, sprintf('//nav[%s]//a[normalize-space(.)="%s"]', $this->hasClass(self::SWITCH_CLASS), $label));
        $this->assertSame(1, $links->length, sprintf('Exactly one switch link "%s" is expected.', $label));
        $link = $links->item(0);
        $this->assertInstanceOf(\DOMElement::class, $link);

        return $link;
    }

    /**
     * The plugin arguments of a link, sorted by key.
     *
     * @return array<string, mixed>
     */
    private function linkArguments(string $href, string $pluginNamespace): array
    {
        parse_str((string)parse_url($href, PHP_URL_QUERY), $query);
        $arguments = $query[$pluginNamespace] ?? [];
        $this->assertIsArray($arguments);
        ksort($arguments);

        return $arguments;
    }

    /**
     * @return \Generator<string, array{0: int, 1: string}>
     */
    public static function viewModeElementsDataProvider(): \Generator
    {
        yield 'list' => [self::LIST_ELEMENT, '/home'];
        yield 'list and detail' => [self::LIST_AND_DETAIL_ELEMENT, '/list-and-detail'];
        yield 'selected profiles' => [self::SELECTED_PROFILES_ELEMENT, '/selected-profiles'];
        yield 'selected contracts' => [self::SELECTED_CONTRACTS_ELEMENT, '/selected-contracts'];
    }

    /**
     * A content element saved before view modes rendered has no view mode at all, and one
     * saved since has "list": both show the tiles, as before.
     */
    #[DataProvider('viewModeElementsDataProvider')]
    #[Test]
    public function theTilesRenderForAnExistingElementAndForTheModeList(int $contentElement, string $path): void
    {
        $this->setUpTestCase();

        $existing = $this->render($path);
        $this->setViewMode($contentElement, 'list');
        $list = $this->render($path);

        foreach ([$existing, $list] as $xpath) {
            $this->assertGreaterThan(0, $this->gridItemCount($xpath));
            $this->assertSame(0, $this->nodes($xpath, '//table')->length);
            $this->assertSame(0, $this->nodes($xpath, sprintf('//nav[%s]', $this->hasClass(self::SWITCH_CLASS)))->length);
        }
    }

    #[DataProvider('viewModeElementsDataProvider')]
    #[Test]
    public function theDefaultModeTableRendersATableWithTheShippedColumns(int $contentElement, string $path): void
    {
        $this->setUpTestCase();
        $this->setViewMode($contentElement, 'table');

        $xpath = $this->render($path);

        $this->assertSame(self::TABLE_HEADERS, $this->tableHeaders($xpath));
        $this->assertSame(0, $this->gridItemCount($xpath));
        $this->assertNotSame([], $this->tableRows($xpath));
    }

    /**
     * One row per profile - per contract in the selected contracts element, where each
     * contract is one profile here - with the values of its contracts. The list shows its
     * first page, Adams and Baker.
     */
    #[Test]
    public function theTableShowsTheValuesOfTheContracts(): void
    {
        $this->setUpTestCase();
        foreach ([self::LIST_ELEMENT, self::SELECTED_PROFILES_ELEMENT, self::SELECTED_CONTRACTS_ELEMENT] as $contentElement) {
            $this->setViewMode($contentElement, 'table');
        }
        $expected = [
            ['Prof. Dr. Anna Adams', 'Professor', 'anna.adams@example.test', '+49 6241 509 123', 'B 1.02'],
            ['Ben Baker', 'Lecturer', 'ben.baker@example.test', '', 'C 0.11'],
            ['<Dr.> Sean O\'Neill', 'Assistant', '', '', ''],
        ];

        $list = $this->render('/home');
        $this->assertSame(array_slice($expected, 0, 2), $this->tableRows($list));
        // One table per letter group, each below its group header.
        $this->assertSame(2, $this->nodes($list, '//table')->length);
        $this->assertSame($expected, $this->tableRows($this->render('/selected-profiles')));
        $this->assertSame($expected, $this->tableRows($this->render('/selected-contracts')));
    }

    /**
     * The name links to the detail view, the e-mail address and the phone number are links
     * of their own, and the phone link dials the number without its spaces.
     */
    #[Test]
    public function theTableLinksTheNameTheEmailAddressAndThePhoneNumber(): void
    {
        $this->setUpTestCase();
        $this->setViewMode(self::SELECTED_PROFILES_ELEMENT, 'table');

        $xpath = $this->render('/selected-profiles');
        $firstRow = $this->nodes($xpath, '//table/tbody/tr[1]')->item(0);
        $this->assertInstanceOf(\DOMNode::class, $firstRow);
        $hrefs = [];
        foreach ($this->nodes($xpath, './/a', $firstRow) as $link) {
            $this->assertInstanceOf(\DOMElement::class, $link);
            $hrefs[] = $link->getAttribute('href');
        }

        $this->assertCount(3, $hrefs);
        $this->assertStringStartsWith('/profiles?', $hrefs[0]);
        $this->assertSame(
            ['action' => 'detail', 'controller' => 'Profile', 'profile' => '1'],
            $this->linkArguments($hrefs[0], 'tx_academicpersons_detail'),
        );
        $this->assertSame('mailto:anna.adams@example.test', $hrefs[1]);
        $this->assertSame('tel:+496241509123', $hrefs[2]);
    }

    #[Test]
    public function theColumnsFollowTheSiteSetting(): void
    {
        $this->setUpTestCase(['plugin.tx_academicpersons.table.columns = emailAddresses, name, unknown']);
        $this->setViewMode(self::SELECTED_PROFILES_ELEMENT, 'table');

        $xpath = $this->render('/selected-profiles');

        // A column without a label shows its key, and its cells stay empty.
        $this->assertSame(['E-mail', 'Name', 'unknown'], $this->tableHeaders($xpath));
        $this->assertSame(['anna.adams@example.test', 'Prof. Dr. Anna Adams', ''], $this->tableRows($xpath)[0]);
    }

    /**
     * @return \Generator<string, array{0: int, 1: string, 2: int, 3: string, 4: list<string>}>
     */
    public static function switchDataProvider(): \Generator
    {
        yield 'list' => [self::LIST_ELEMENT, '/home', 2, 'tx_academicpersons_list', ['demand', 'viewMode']];
        yield 'selected profiles' => [self::SELECTED_PROFILES_ELEMENT, '/selected-profiles', 4, 'tx_academicpersons_selectedprofiles', ['viewMode']];
        yield 'selected contracts' => [self::SELECTED_CONTRACTS_ELEMENT, '/selected-contracts', 5, 'tx_academicpersons_selectedcontracts', ['viewMode']];
    }

    /**
     * The switch offers every allowed mode, marks the active one, and a link changes the
     * mode. The link to the default mode carries none.
     *
     * @param list<string> $viewModePath Where the mode sits in the plugin arguments.
     */
    #[DataProvider('switchDataProvider')]
    #[Test]
    public function theSwitchLinksEveryModeAndMarksTheActiveOne(int $contentElement, string $path, int $pageId, string $pluginNamespace, array $viewModePath): void
    {
        $this->setUpTestCase();
        $this->setViewMode($contentElement, 'list', true);

        $tiles = $this->render($path);
        $this->assertSame('true', $this->switchLink($tiles, 'Tiles')->getAttribute('aria-current'));
        $this->assertSame('', $this->switchLink($tiles, 'Table')->getAttribute('aria-current'));
        $this->assertSame('nofollow', $this->switchLink($tiles, 'Table')->getAttribute('rel'));
        $tableHref = $this->switchLink($tiles, 'Table')->getAttribute('href');
        $this->assertSame('table', $this->valueAt($this->linkArguments($tableHref, $pluginNamespace), $viewModePath));
        $this->assertNull($this->valueAt($this->linkArguments($this->switchLink($tiles, 'Tiles')->getAttribute('href'), $pluginNamespace), $viewModePath));

        $table = $this->render($tableHref);
        $this->assertSame(self::TABLE_HEADERS, $this->tableHeaders($table));
        $this->assertSame('true', $this->switchLink($table, 'Table')->getAttribute('aria-current'));
        $this->assertSame('', $this->switchLink($table, 'Tiles')->getAttribute('aria-current'));
        $this->assertNull($this->valueAt($this->linkArguments($this->switchLink($table, 'Tiles')->getAttribute('href'), $pluginNamespace), $viewModePath));

        $backToTiles = $this->render($this->switchLink($table, 'Tiles')->getAttribute('href'));
        $this->assertGreaterThan(0, $this->gridItemCount($backToTiles));
    }

    /**
     * With the table as the default, the tiles are the mode a link has to name.
     */
    #[Test]
    public function theSwitchOfATableListNamesTheTiles(): void
    {
        $this->setUpTestCase();
        $this->setViewMode(self::LIST_ELEMENT, 'table', true);

        $xpath = $this->render('/home');

        $this->assertSame(self::TABLE_HEADERS, $this->tableHeaders($xpath));
        $this->assertSame(['viewMode' => 'list'], $this->linkArguments($this->switchLink($xpath, 'Tiles')->getAttribute('href'), 'tx_academicpersons_list')['demand'] ?? null);
        $this->assertArrayNotHasKey('demand', $this->linkArguments($this->switchLink($xpath, 'Table')->getAttribute('href'), 'tx_academicpersons_list'));
    }

    /**
     * @param array<string, mixed> $arguments
     * @param list<string> $path
     */
    private function valueAt(array $arguments, array $path): mixed
    {
        $value = $arguments;
        foreach ($path as $key) {
            if (!is_array($value) || !array_key_exists($key, $value)) {
                return null;
            }
            $value = $value[$key];
        }

        return $value;
    }

    /**
     * @return \Generator<string, array{0: string, 1: bool, 2: list<string>}>
     */
    public static function ignoredRequestDataProvider(): \Generator
    {
        yield 'a mode the site does not allow' => ['slider', true, []];
        yield 'an allowed mode while the switch is off' => ['table', false, []];
        // Allowed by a misconfigured setting, and still no partial name.
        yield 'a mode that is not a plain name' => ['../Table', true, ['plugin.tx_academicpersons.viewMode.allowed = list,table,../Table']];
    }

    /**
     * A requested mode the element may not render shows the default mode, without an
     * error, in all three elements.
     *
     * @param list<string> $constants
     */
    #[DataProvider('ignoredRequestDataProvider')]
    #[Test]
    public function aRequestedModeTheElementMayNotRenderShowsTheDefault(string $requested, bool $enabled, array $constants): void
    {
        $this->setUpTestCase($constants);
        foreach ([self::LIST_ELEMENT, self::SELECTED_PROFILES_ELEMENT, self::SELECTED_CONTRACTS_ELEMENT] as $contentElement) {
            $this->setViewMode($contentElement, 'list', $enabled);
        }

        $requests = [
            $this->renderWithArguments('/home', 2, 'tx_academicpersons_list', ['demand' => ['viewMode' => $requested]]),
            $this->renderWithArguments('/selected-profiles', 4, 'tx_academicpersons_selectedprofiles', ['viewMode' => $requested]),
            $this->renderWithArguments('/selected-contracts', 5, 'tx_academicpersons_selectedcontracts', ['viewMode' => $requested]),
        ];

        foreach ($requests as $xpath) {
            $this->assertGreaterThan(0, $this->gridItemCount($xpath));
            $this->assertSame(0, $this->nodes($xpath, '//table')->length);
        }
    }

    /**
     * The mode a visitor chose survives the list navigation: a page link and a letter link
     * of a table carry it, and lead to a table again.
     */
    #[Test]
    public function thePaginationAndLetterLinksOfATableCarryTheMode(): void
    {
        $this->setUpTestCase();
        $this->setViewMode(self::LIST_ELEMENT, 'list', true);

        $table = $this->render($this->switchLink($this->render('/home'), 'Table')->getAttribute('href'));

        $pageLinks = $this->nodes($table, sprintf('//nav[%s]//a[normalize-space(./span[1])="2"]', $this->hasClass('academic-persons-list__pagination')));
        $this->assertSame(1, $pageLinks->length);
        $pageLink = $pageLinks->item(0);
        $this->assertInstanceOf(\DOMElement::class, $pageLink);
        $this->assertSame(
            ['currentPage' => '2', 'viewMode' => 'table'],
            $this->sorted($this->linkArguments($pageLink->getAttribute('href'), 'tx_academicpersons_list')['demand'] ?? []),
        );
        $secondPage = $this->render($pageLink->getAttribute('href'));
        $this->assertSame([['<Dr.> Sean O\'Neill', 'Assistant', '', '', '']], $this->tableRows($secondPage));

        $letterLinks = $this->nodes($table, sprintf('//nav[%s]//a[normalize-space(./span[1])="B"]', $this->hasClass('academic-persons-list__alphabet-pagination')));
        $this->assertSame(1, $letterLinks->length);
        $letterLink = $letterLinks->item(0);
        $this->assertInstanceOf(\DOMElement::class, $letterLink);
        $this->assertSame(
            ['alphabetFilter' => 'b', 'viewMode' => 'table'],
            $this->sorted($this->linkArguments($letterLink->getAttribute('href'), 'tx_academicpersons_list')['demand'] ?? []),
        );
        $letterPage = $this->render($letterLink->getAttribute('href'));
        $this->assertSame([['Ben Baker', 'Lecturer', 'ben.baker@example.test', '', 'C 0.11']], $this->tableRows($letterPage));

        $allLinks = $this->nodes($letterPage, sprintf('//nav[%s]//a[normalize-space(./span[1])="A-Z"]', $this->hasClass('academic-persons-list__alphabet-pagination')));
        $this->assertSame(1, $allLinks->length);
        $allLink = $allLinks->item(0);
        $this->assertInstanceOf(\DOMElement::class, $allLink);
        $this->assertSame(
            ['alphabetFilter' => '', 'viewMode' => 'table'],
            $this->sorted($this->linkArguments($allLink->getAttribute('href'), 'tx_academicpersons_list')['demand'] ?? []),
        );
    }

    /**
     * @param mixed $arguments
     * @return array<string, mixed>
     */
    private function sorted(mixed $arguments): array
    {
        $this->assertIsArray($arguments);
        ksort($arguments);

        return $arguments;
    }

    /**
     * A project mode is a partial, an allowed mode and an item of the default view mode; the
     * shipped templates render it as they are.
     */
    #[DataProvider('viewModeElementsDataProvider')]
    #[Test]
    public function aProjectModeRendersItsOwnPartial(int $contentElement, string $path): void
    {
        $this->setUpTestCase([], true);
        $this->setViewMode($contentElement, 'contact');

        $xpath = $this->render($path);

        $this->assertNotSame([], $this->texts($xpath, sprintf('//ul[%s]/li', $this->hasClass('test-contact-cards'))));
        $this->assertSame(0, $this->gridItemCount($xpath));
    }

    /**
     * The switch offers the project mode next to the shipped ones, labelled with its name
     * while it has no label of its own.
     */
    #[Test]
    public function theSwitchOffersAProjectMode(): void
    {
        $this->setUpTestCase([], true);
        $this->setViewMode(self::LIST_ELEMENT, 'list', true);

        $xpath = $this->render($this->switchLink($this->render('/home'), 'contact')->getAttribute('href'));

        $this->assertSame(['Anna Adams', 'Ben Baker'], $this->texts($xpath, sprintf('//ul[%s]/li', $this->hasClass('test-contact-cards'))));
        $this->assertSame('true', $this->switchLink($xpath, 'contact')->getAttribute('aria-current'));
    }

    /**
     * @return \Generator<string, array{0: string, 1: string, 2: string}>
     */
    public static function defaultFallbackDataProvider(): \Generator
    {
        yield 'a default the site allows' => ['list,table', 'table', 'table'];
        yield 'a default the site does not allow falls back to the tiles' => ['list', 'table', 'list'];
        yield 'without the tiles, to the first allowed mode' => ['table', 'list', 'table'];
        yield 'a default that names no mode' => ['table,list', 'slider', 'list'];
        yield 'no valid allowed mode leaves the tiles' => [',../Table', 'table', 'list'];
    }

    /**
     * The default mode of the element renders while the site allows it; otherwise the
     * tiles, or the first allowed mode where the tiles are not allowed.
     */
    #[DataProvider('defaultFallbackDataProvider')]
    #[Test]
    public function theDefaultModeFallsBackToAnAllowedMode(string $allowed, string $default, string $renders): void
    {
        $this->setUpTestCase(['plugin.tx_academicpersons.viewMode.allowed = ' . $allowed]);
        $this->setViewMode(self::SELECTED_PROFILES_ELEMENT, $default);

        $xpath = $this->render('/selected-profiles');

        if ($renders === 'table') {
            $this->assertSame(self::TABLE_HEADERS, $this->tableHeaders($xpath));
            $this->assertSame(0, $this->gridItemCount($xpath));
        } else {
            $this->assertSame(3, $this->gridItemCount($xpath));
            $this->assertSame(0, $this->nodes($xpath, '//table')->length);
        }
    }

    /**
     * With one allowed mode there is nothing to switch between.
     */
    #[Test]
    public function theSwitchNeedsTwoModes(): void
    {
        $this->setUpTestCase(['plugin.tx_academicpersons.viewMode.allowed = table']);
        $this->setViewMode(self::LIST_ELEMENT, 'table', true);

        $xpath = $this->render('/home');

        $this->assertSame(self::TABLE_HEADERS, $this->tableHeaders($xpath));
        $this->assertSame(0, $this->nodes($xpath, sprintf('//nav[%s]', $this->hasClass(self::SWITCH_CLASS)))->length);
    }

    /**
     * A mode named in camel case is the partial with its first letter in upper case, the
     * rest as it is: "contactCards" renders "ContactCards.html".
     */
    #[Test]
    public function aCamelCaseModeRendersThePartialOfTheSameName(): void
    {
        $this->setUpTestCase(['plugin.tx_academicpersons.viewMode.allowed = list,contactCards'], true);
        $this->setViewMode(self::SELECTED_PROFILES_ELEMENT, 'contactCards');

        $xpath = $this->render('/selected-profiles');

        $this->assertSame(3, $this->nodes($xpath, sprintf('//ul[%s]/li', $this->hasClass('test-contact-cards-camel')))->length);
    }

    /**
     * An element that restricts its fields restricts the table: a contract column whose
     * field it leaves out is left out, the name stays.
     */
    #[Test]
    public function theTableLeavesOutTheContractFieldsTheElementDoesNotShow(): void
    {
        $this->setUpTestCase();
        $this->setViewMode(self::SELECTED_PROFILES_ELEMENT, 'table');
        $this->addFlexFormFields(self::SELECTED_PROFILES_ELEMENT, ['settings.showFields' => 'contracts.room,contracts.position']);

        $xpath = $this->render('/selected-profiles');

        $this->assertSame(['Name', 'Position', 'Room'], $this->tableHeaders($xpath));
        $this->assertSame(['Prof. Dr. Anna Adams', 'Professor', 'B 1.02'], $this->tableRows($xpath)[0]);
    }

    /**
     * The mode is read before the list event: a demand a listener hands back renders in
     * the mode the request resolved, whatever mode it names.
     */
    #[Test]
    public function aListenerOfTheListEventDoesNotChangeTheViewMode(): void
    {
        $this->setUpTestCase();
        $this->setViewMode(self::LIST_ELEMENT, 'list', true);
        ReplaceListDemandListener::$viewMode = 'table';

        $xpath = $this->render('/home');

        $this->assertGreaterThan(0, $this->gridItemCount($xpath));
        $this->assertSame(0, $this->nodes($xpath, '//table')->length);
    }

    /**
     * The card shares the FlexForm of the list, and its page TSconfig hides the view mode
     * fields. A value stored anyway changes nothing: the card renders the tiles.
     */
    #[Test]
    public function theCardRendersTheTilesWhateverViewModeItCarries(): void
    {
        $this->setUpTestCase();
        $this->setViewMode(self::CARD_ELEMENT, 'table', true);

        $xpath = $this->render('/card');

        $this->assertSame(3, $this->gridItemCount($xpath));
        $this->assertSame(0, $this->nodes($xpath, '//table')->length);
        $this->assertSame(0, $this->nodes($xpath, sprintf('//nav[%s]', $this->hasClass(self::SWITCH_CLASS)))->length);
    }
}
