<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Plugins;

use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;

/**
 * What an installed extension can do to the profiles and contracts the plugins show, through
 * `ModifyProfileQueryEvent` and `ModifyContractQueryEvent`.
 *
 * `EXT:test_profile_query_constraints` ships one listener per event. Both stay inert until a
 * plugin setting asks for a constraint, so each test below includes the TypoScript file of the
 * behaviour it is about. That the plugins render unrestricted while *no* extension listens is
 * what every other plugin test of this extension asserts - none of them loads this fixture.
 */
final class AcademicPersonsProfileQueryConstraintTest extends AbstractAcademicPersonsTestCase
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
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    /**
     * @param string[] $listenerTypoScriptFiles The behaviour the fixture listeners are asked for.
     */
    private function setUpFrontendRootPageForTestCase(array $listenerTypoScriptFiles = []): void
    {
        $this->setUpFrontendRootPage(
            pageId: 1,
            typoScriptFiles: [
                'constants' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript',
                    'EXT:academic_persons/Configuration/TypoScript/Default/constants.typoscript',
                    'EXT:test_plugin_templates/Configuration/TypoScript/constants.typoscript',
                    'EXT:academic_persons/Tests/Functional/Plugins/Fixtures/TypoScript/Constants/PluginConfiguration.typoscript',
                ],
                'setup' => array_merge(
                    [
                        'EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript',
                        'EXT:academic_persons/Configuration/TypoScript/Default/setup.typoscript',
                        'EXT:test_plugin_templates/Configuration/TypoScript/setup.typoscript',
                        'EXT:academic_persons/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/Rendering.typoscript',
                    ],
                    $listenerTypoScriptFiles,
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

    private function renderHomePage(): string
    {
        return $this->renderFrontendPage('https://www.acme.com/home');
    }

    /**
     * The card template composes the heading from first, middle and last name, so an empty
     * middle name leaves two spaces in the markup.
     */
    private function assertRendersProfileName(string $content, string $first, string $last): void
    {
        $this->assertMatchesRegularExpression(
            sprintf('#%s\s+%s#u', preg_quote($first, '#'), preg_quote($last, '#')),
            $content,
        );
    }

    private function assertRendersNoProfileName(string $content, string $first, string $last): void
    {
        $this->assertDoesNotMatchRegularExpression(
            sprintf('#%s\s+%s#u', preg_quote($first, '#'), preg_quote($last, '#')),
            $content,
        );
    }

    #[Test]
    public function listPluginShowsOnlyTheProfilesTheListenerLeaves(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsProfileQueryConstraint/listPlugin.csv');
        $this->setUpFrontendRootPageForTestCase([
            'EXT:test_profile_query_constraints/Configuration/TypoScript/RestrictLastName.typoscript',
        ]);

        $content = $this->renderHomePage();
        $this->assertStringContainsString('<h2>Profilelist</h2>', $content);
        $this->assertStringContainsString('#0(2): Anna Achterberg', $content);
        $this->assertStringNotContainsString('Müllermann', $content);
        $this->assertStringNotContainsString('Huber', $content);
    }

    /**
     * The control of the test above: the same fixture extension is loaded, and the plugin shows
     * every profile while no setting asks the listener for a constraint.
     */
    #[Test]
    public function listPluginShowsEveryProfileWhileTheListenerAddsNothing(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsProfileQueryConstraint/listPlugin.csv');
        $this->setUpFrontendRootPageForTestCase();

        $content = $this->renderHomePage();
        $this->assertStringContainsString('#0(1): Max Müllermann', $content);
        $this->assertStringContainsString('#1(2): Anna Achterberg', $content);
        $this->assertStringContainsString('#2(3): Horst Huber', $content);
    }

    #[Test]
    public function cardPluginShowsOnlyTheProfilesTheListenerLeaves(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsProfileQueryConstraint/cardPlugin.csv');
        $this->setUpFrontendRootPageForTestCase([
            'EXT:test_profile_query_constraints/Configuration/TypoScript/RestrictLastName.typoscript',
        ]);

        $content = $this->renderHomePage();
        $this->assertRendersProfileName($content, 'Anna', 'Achterberg');
        $this->assertRendersNoProfileName($content, 'Max', 'Müllermann');
        $this->assertRendersNoProfileName($content, 'Horst', 'Huber');
    }

    #[Test]
    public function selectedProfilesPluginShowsOnlyTheProfilesTheListenerLeaves(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsProfileQueryConstraint/selectedProfilesPlugin.csv');
        $this->setUpFrontendRootPageForTestCase([
            'EXT:test_profile_query_constraints/Configuration/TypoScript/RestrictLastName.typoscript',
        ]);

        $content = $this->renderHomePage();
        $this->assertStringContainsString('<h2>Selected Profiles</h2>', $content);
        $this->assertStringContainsString('#0(2): Anna Achterberg', $content);
        $this->assertStringNotContainsString('Müllermann', $content);
        $this->assertStringNotContainsString('Huber', $content);
    }

    /**
     * The listener reads a setting of the content element itself - the selection the editor
     * made in the FlexForm of this very plugin - and drops its first uid.
     */
    #[Test]
    public function selectedProfilesPluginListenerReadsTheContentElementSetting(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsProfileQueryConstraint/selectedProfilesPlugin.csv');
        $this->setUpFrontendRootPageForTestCase([
            'EXT:test_profile_query_constraints/Configuration/TypoScript/DropFirstSelectedProfile.typoscript',
        ]);

        $content = $this->renderHomePage();
        // The selection is "3,2,1" and the first of it is dropped, the rest keeps its order.
        $this->assertStringContainsString('#0(2): Anna Achterberg', $content);
        $this->assertStringContainsString('#1(1): Max Müllermann', $content);
        $this->assertStringNotContainsString('Huber', $content);
    }

    #[Test]
    public function selectedContractsPluginDoesNotShowTheContractTheListenerExcludes(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsProfileQueryConstraint/selectedContractsPlugin.csv');
        $this->setUpFrontendRootPageForTestCase([
            'EXT:test_profile_query_constraints/Configuration/TypoScript/ExcludeContract.typoscript',
        ]);

        $content = $this->renderHomePage();
        $this->assertStringContainsString('<h2>Selected Contracts</h2>', $content);
        $this->assertStringContainsString('#0(2): Manager', $content);
        $this->assertStringNotContainsString('Worker', $content);
    }

    /**
     * The order asserted here is the one the plugin defines and not an accident of the
     * fixture: the constants of these tests leave `demand.sortBy` empty, so the ordering is
     * the deterministic `uid` fallback of ACE-491 - which is also what a `lastName` sorting
     * would fall back to, the two remaining profiles sharing their last name.
     */
    #[Test]
    public function paginationCountsOnlyTheProfilesTheListenerLeaves(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsProfileQueryConstraint/paginatedListPlugin.csv');
        $this->setUpFrontendRootPageForTestCase([
            'EXT:test_profile_query_constraints/Configuration/TypoScript/RestrictLastName.typoscript',
        ]);

        $content = $this->renderHomePage();
        // Two of five profiles are left and two fit on a page, so the five become one page.
        $this->assertStringContainsString('PAGINATION: page 1 of 1', $content);
        $this->assertStringContainsString('#0(2): Anna Achterberg', $content);
        $this->assertStringContainsString('#1(4): Bernd Achterberg', $content);
        $this->assertStringNotContainsString('Müllermann', $content);
        $this->assertStringNotContainsString('Huber', $content);
        $this->assertStringNotContainsString('Beispiel', $content);
    }

    /**
     * The control of the test above: without a constraint the same five profiles paginate into
     * three pages, so the assertion there is about the constraint and not about the fixture.
     */
    #[Test]
    public function paginationCountsEveryProfileWhileTheListenerAddsNothing(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsProfileQueryConstraint/paginatedListPlugin.csv');
        $this->setUpFrontendRootPageForTestCase();

        $content = $this->renderHomePage();
        $this->assertStringContainsString('PAGINATION: page 1 of 3', $content);
    }

    /**
     * The capability `academic-persons/profile-list-pagination` is about a manual selection,
     * which paginates through `ArrayPaginator` after `ProfileController::listAction()` restored
     * the editor's order - a different path from the `QueryResultPaginator` of the test above.
     * A listener narrows that one as well, and the pages follow.
     */
    #[Test]
    public function paginatedManualSelectionFollowsTheListenerAndKeepsItsOrder(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsProfileQueryConstraint/paginatedSelectionPlugin.csv');
        $this->setUpFrontendRootPageForTestCase([
            'EXT:test_profile_query_constraints/Configuration/TypoScript/RestrictLastName.typoscript',
        ]);

        $content = $this->renderHomePage();
        // The selection is "5,4,3,2,1" and two profiles fit on a page; the listener leaves the
        // two Achterbergs, so the five become one page - in the order of the selection, which
        // puts uid 4 before uid 2.
        $this->assertStringContainsString('PAGINATION: page 1 of 1', $content);
        $this->assertStringContainsString('#0(4): Bernd Achterberg', $content);
        $this->assertStringContainsString('#1(2): Anna Achterberg', $content);
        $this->assertStringNotContainsString('Müllermann', $content);
        $this->assertStringNotContainsString('Huber', $content);
        $this->assertStringNotContainsString('Beispiel', $content);
    }

    #[Test]
    public function listenerConstraintAndEditorFilterNarrowTheListTogether(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsProfileQueryConstraint/listPluginWithOrganisationalUnits.csv');
        $this->setUpFrontendRootPageForTestCase([
            'EXT:test_profile_query_constraints/Configuration/TypoScript/RestrictLastName.typoscript',
        ]);

        $content = $this->renderHomePage();
        // Anna satisfies both, Max only the editor's organisational unit and Clara only the
        // listener's last name.
        $this->assertStringContainsString('#0(2): Anna Achterberg', $content);
        $this->assertStringNotContainsString('Müllermann', $content);
        $this->assertStringNotContainsString('Clara', $content);
    }

    /**
     * The branch that tells the three possible implementations apart, and the reason the
     * repository reads `getConstraint()` after the dispatch at all.
     *
     * The content element filters by organisational unit, so the repository has a constraint of
     * its own, and the listener sets a second one with `matching()` instead of
     * `addConstraint()` - which *replaces* what is on the query. Three outcomes are possible:
     *
     * - folded in, as implemented: both conditions hold, and only Anna is left;
     * - the listener's call left standing: it replaced the editor's filter, so Anna **and**
     *   Clara render - the result is wider than the content element asked for;
     * - the listener's call dropped: the filter alone, so Anna and Max render.
     *
     * Each of the three renders a different list, so this test can fail for either regression.
     */
    #[Test]
    public function aConstraintAListenerSetsWithMatchingIsFoldedInBesideTheEditorFilter(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsProfileQueryConstraint/listPluginWithOrganisationalUnits.csv');
        $this->setUpFrontendRootPageForTestCase([
            'EXT:test_profile_query_constraints/Configuration/TypoScript/MatchingBesideAnEditorFilter.typoscript',
        ]);

        $content = $this->renderHomePage();
        $this->assertStringContainsString('<h2>Profilelist</h2>', $content);
        $this->assertStringContainsString('#0(2): Anna Achterberg', $content);
        // Clara satisfies the listener and not the editor's filter, Max the other way round.
        $this->assertStringNotContainsString('Clara', $content);
        $this->assertStringNotContainsString('Müllermann', $content);
        $this->assertStringNotContainsString('#1(', $content);
    }

    /**
     * The same, where the repository has no constraint of its own: the listener's condition
     * matches nothing, and the list is empty rather than complete.
     *
     * This case alone would not prove the fold - a dropped `matching()` cannot be told from a
     * folded one when there is nothing else to combine it with, which is why
     * {@see self::aConstraintAListenerSetsWithMatchingIsFoldedInBesideTheEditorFilter()} exists.
     * It pins that the plain list behaves the same way as the filtered one.
     */
    #[Test]
    public function aConstraintAListenerSetsWithMatchingAlsoAppliesWithoutAnEditorFilter(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsProfileQueryConstraint/listPlugin.csv');
        $this->setUpFrontendRootPageForTestCase([
            'EXT:test_profile_query_constraints/Configuration/TypoScript/FightTheRepository.typoscript',
        ]);

        $content = $this->renderHomePage();
        // The list template renders nothing at all for an empty result, heading included, so the
        // anchor is the content element frame around it - the plugin did render, and it rendered
        // no profile.
        $this->assertStringContainsString('frame-type-academicpersons_list', $content);
        $this->assertStringNotContainsString('<h2>Profilelist</h2>', $content);
        $this->assertStringNotContainsString('#0(', $content);
        $this->assertStringNotContainsString('Müllermann', $content);
        $this->assertStringNotContainsString('Achterberg', $content);
        $this->assertStringNotContainsString('Huber', $content);
    }

    /**
     * The ordering stays with the plugin. The fixture listener asks for last name descending,
     * which would put Müllermann first; the list renders in the `uid` order the repository sets.
     */
    #[Test]
    public function aListenerCannotChangeTheOrderOfTheList(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsProfileQueryConstraint/listPlugin.csv');
        $this->setUpFrontendRootPageForTestCase([
            'EXT:test_profile_query_constraints/Configuration/TypoScript/ReorderTheList.typoscript',
        ]);

        $content = $this->renderHomePage();
        $this->assertStringContainsString('#0(1): Max Müllermann', $content);
        $this->assertStringContainsString('#1(2): Anna Achterberg', $content);
        $this->assertStringContainsString('#2(3): Horst Huber', $content);
    }

    #[Test]
    public function listenerThatChecksThePluginNameLeavesTheCardPluginUnrestricted(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsProfileQueryConstraint/listAndCardPlugin.csv');
        $this->setUpFrontendRootPageForTestCase([
            'EXT:test_profile_query_constraints/Configuration/TypoScript/RestrictLastNameForListPluginOnly.typoscript',
        ]);

        $content = $this->renderHomePage();
        // The list renders exactly one item, and it is the one the listener leaves.
        $this->assertStringContainsString('#0(2): Anna Achterberg', $content);
        $this->assertStringNotContainsString('#1(', $content);
        // The card plugin on the same page keeps all three.
        $this->assertRendersProfileName($content, 'Max', 'Müllermann');
        $this->assertRendersProfileName($content, 'Horst', 'Huber');
    }
}
