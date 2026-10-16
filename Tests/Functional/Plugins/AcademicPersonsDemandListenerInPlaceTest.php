<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Plugins;

use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TESTS\TestProfileQueryConstraints\EventListener\ChangeProfileDemandInPlaceListener;

/**
 * A listener of `ModifyProfileDemandEvent` that changes the demand it receives in place,
 * instead of handing back a copy through `setDemand()`, changes the profiles the list queries
 * and nothing else: the order of a selection by hand, the page, the letter that switches the
 * pagination off, the demand a listener of `ModifyListProfilesEvent` receives and the demand of
 * the template follow the demand of the request (ACE-920).
 *
 * The list renders through `EXT:test_plugin_templates`, unsorted and ungrouped, so a list
 * without a selection comes out in uid order: Max Müllermann (1), Anna Achterberg (2), Horst
 * Huber (3). The fixture listener records the demand `ModifyListProfilesEvent` carries after
 * the query, which the list assigns to its view, so one assertion covers both.
 */
final class AcademicPersonsDemandListenerInPlaceTest extends AbstractAcademicPersonsTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration();
        $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
        $this->addTestExtensionsToLoad(
            'georgringer/numbered-pagination',
            'tests/plugin-templates',
            'tests/test-profile-query-constraints',
        );
        parent::setUp();
        ChangeProfileDemandInPlaceListener::reset();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsDemandListenerInPlace/records.csv');
        $this->setUpFrontendRootPage(
            pageId: 1,
            typoScriptFiles: [
                'constants' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript',
                    'EXT:academic_persons/Configuration/TypoScript/Default/constants.typoscript',
                    'EXT:test_plugin_templates/Configuration/TypoScript/constants.typoscript',
                    'EXT:academic_persons/Tests/Functional/Plugins/Fixtures/TypoScript/Constants/PluginConfiguration.typoscript',
                ],
                'setup' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript',
                    'EXT:academic_persons/Configuration/TypoScript/Default/setup.typoscript',
                    'EXT:test_plugin_templates/Configuration/TypoScript/setup.typoscript',
                    'EXT:academic_persons/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/Rendering.typoscript',
                ],
            ],
        );
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration('EN', '/'),
        ]);
    }

    protected function tearDown(): void
    {
        ChangeProfileDemandInPlaceListener::reset();
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    /**
     * @param array<string, string> $settings FlexForm fields below `settings.`
     */
    private function addListElement(array $settings): void
    {
        $fields = '';
        foreach (array_merge(['fallbackForNonTranslated' => '0'], $settings) as $name => $value) {
            $fields .= sprintf('<field index="settings.%s"><value index="vDEF">%s</value></field>', $name, htmlspecialchars($value));
        }
        $this->getConnectionPool()->getConnectionForTable('tt_content')->insert('tt_content', [
            'uid' => 1,
            'pid' => 2,
            'CType' => 'academicpersons_list',
            'header' => '',
            'pages' => '100',
            'pi_flexform' => '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>'
                . '<T3FlexForms><data><sheet index="sDEF"><language index="lDEF">'
                . $fields
                . '</language></sheet></data></T3FlexForms>',
        ]);
    }

    /**
     * The uids of the listed profiles, in the order of the markup.
     *
     * @return list<int>
     */
    private function listedUids(string $content): array
    {
        preg_match_all('/^#\d+\((\d+)\): /m', $content, $matches);
        return array_map('intval', $matches[1]);
    }

    private function renderHomePage(): string
    {
        return $this->renderFrontendPage('https://www.acme.com/home');
    }

    #[Test]
    public function aSelectionTheListenerMakesNarrowsTheQueryButIsNotOrderedByHand(): void
    {
        $this->addListElement([]);
        ChangeProfileDemandInPlaceListener::$profileList = '3,1';

        $content = $this->renderHomePage();

        // The query follows the listener and returns its selection, ordered by uid. The request
        // selects nothing by hand, so nothing restores the order of the listener's selection.
        $this->assertSame([1, 3], $this->listedUids($content));
        $this->assertSame(
            ['profileList' => '', 'currentPage' => 1, 'alphabetFilter' => ''],
            ChangeProfileDemandInPlaceListener::$listEventDemand,
        );
    }

    #[Test]
    public function aSelectionByHandTheListenerNarrowsKeepsTheOrderOfTheEditor(): void
    {
        $this->addListElement(['demand.profileList' => '3,1,2']);
        ChangeProfileDemandInPlaceListener::$profileList = '2,3';

        $content = $this->renderHomePage();

        $this->assertSame([3, 2], $this->listedUids($content));
        $this->assertSame(
            ['profileList' => '3,1,2', 'currentPage' => 1, 'alphabetFilter' => ''],
            ChangeProfileDemandInPlaceListener::$listEventDemand,
        );
    }

    /**
     * The query follows the listener and returns every profile, the list keeps to the profiles
     * of its own selection, in its order.
     */
    #[Test]
    public function aSelectionByHandTheListenerClearsDoesNotWidenTheList(): void
    {
        $this->addListElement(['demand.profileList' => '3,1']);
        ChangeProfileDemandInPlaceListener::$profileList = '';

        $content = $this->renderHomePage();

        $this->assertSame([3, 1], $this->listedUids($content));
        $this->assertSame(
            ['profileList' => '3,1', 'currentPage' => 1, 'alphabetFilter' => ''],
            ChangeProfileDemandInPlaceListener::$listEventDemand,
        );
    }

    /**
     * The list asks the listeners once per rendering on this version line, for its profiles,
     * and that call receives the page of the request.
     */
    #[Test]
    public function aPageTheListenerSetsLeavesThePaginationOnThePageOfTheRequest(): void
    {
        $this->addListElement([
            'paginationEnabled' => '1',
            'pagination.resultsPerPage' => '1',
        ]);
        ChangeProfileDemandInPlaceListener::$currentPage = 2;

        $content = $this->renderHomePage();

        $this->assertStringContainsString('PAGINATION: page 1 of 3', $content);
        $this->assertSame([1], $this->listedUids($content));
        $this->assertSame(
            ['profileList' => '', 'currentPage' => 1, 'alphabetFilter' => ''],
            ChangeProfileDemandInPlaceListener::$listEventDemand,
        );
        $this->assertSame(
            [['profileList' => '', 'currentPage' => 1, 'alphabetFilter' => '']],
            ChangeProfileDemandInPlaceListener::$received,
        );
    }

    /**
     * A letter of the request switches the pagination off. A letter the listener sets narrows
     * the query to the last names it starts, and the pagination of the list stays on.
     */
    #[Test]
    public function aLetterTheListenerSetsDoesNotSwitchThePaginationOff(): void
    {
        $this->addListElement([
            'paginationEnabled' => '1',
            'pagination.resultsPerPage' => '1',
        ]);
        ChangeProfileDemandInPlaceListener::$alphabetFilter = 'A';

        $content = $this->renderHomePage();

        $this->assertStringContainsString('PAGINATION: page 1 of 1', $content);
        $this->assertSame([2], $this->listedUids($content));
        $this->assertSame(
            ['profileList' => '', 'currentPage' => 1, 'alphabetFilter' => ''],
            ChangeProfileDemandInPlaceListener::$listEventDemand,
        );
    }
}
