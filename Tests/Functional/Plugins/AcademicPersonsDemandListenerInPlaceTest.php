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
 * and nothing the list reads from its demand: the order of a selection by hand, the page and
 * the demand of the template follow the request, and each further call of the same rendering
 * receives the demand of the request again (ACE-920).
 *
 * The list renders through `EXT:test_plugin_templates`, unsorted and ungrouped, so a list
 * without a selection comes out in uid order: Max Müllermann (1), Anna Achterberg (2), Horst
 * Huber (3).
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
    public function aSelectionTheListenerMakesDecidesTheQueryOnlyAndIsNotOrderedByHand(): void
    {
        $this->addListElement(['alphabetPaginationEnabled' => '1']);
        ChangeProfileDemandInPlaceListener::$profileList = '3,1';

        $content = $this->renderHomePage();

        // The query follows the listener and returns its selection, ordered by uid. The request
        // selects nothing by hand, so nothing restores the order of the listener's selection.
        $this->assertSame([1, 3], $this->listedUids($content));
        $this->assertSame(['profileList' => '', 'currentPage' => 1], ChangeProfileDemandInPlaceListener::$templateDemand);
        // The letter navigation follows the request as well, and the letters ask the listener,
        // whose selection ignores the letter: every letter leads to the same two profiles.
        $this->assertStringContainsString('LETTER a: available', $content);
        $this->assertStringContainsString('LETTER z: available', $content);
    }

    #[Test]
    public function aSelectionByHandTheListenerNarrowsKeepsTheOrderOfTheEditor(): void
    {
        $this->addListElement(['demand.profileList' => '3,1,2']);
        ChangeProfileDemandInPlaceListener::$profileList = '2,3';

        $content = $this->renderHomePage();

        $this->assertSame([3, 2], $this->listedUids($content));
        $this->assertSame(['profileList' => '3,1,2', 'currentPage' => 1], ChangeProfileDemandInPlaceListener::$templateDemand);
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
        $this->assertSame(['profileList' => '3,1', 'currentPage' => 1], ChangeProfileDemandInPlaceListener::$templateDemand);
    }

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
        $this->assertSame(['profileList' => '', 'currentPage' => 1], ChangeProfileDemandInPlaceListener::$templateDemand);
    }

    /**
     * The list asks the listeners for its profiles, for the next day its contracts change the
     * list and for its letters. Each call receives the demand of the request, never the one the
     * listener changed on an earlier call of the same rendering.
     */
    #[Test]
    public function everyCallOfOneRenderingReceivesTheDemandOfTheRequest(): void
    {
        $this->addListElement([
            'contracts.onlyValid' => '1',
            'alphabetPaginationEnabled' => '1',
        ]);
        ChangeProfileDemandInPlaceListener::$currentPage = 2;

        $content = $this->renderHomePage();

        $this->assertStringContainsString('LETTER a: available', $content);
        $this->assertSame(
            [
                ['profileList' => '', 'currentPage' => 1],
                ['profileList' => '', 'currentPage' => 1],
                ['profileList' => '', 'currentPage' => 1],
            ],
            ChangeProfileDemandInPlaceListener::$received,
        );
    }
}
