<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Plugins;

use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TESTS\TestProfileQueryConstraints\EventListener\RestrictContractQueryListener;
use TESTS\TestProfileQueryConstraints\EventListener\RestrictProfileQueryListener;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequestContext;

/**
 * What an installed extension can do to the profiles and contracts the plugins show, through
 * `ModifyProfileQueryEvent` and `ModifyContractQueryEvent`.
 *
 * `EXT:test_profile_query_constraints` ships one listener per event, registered with the
 * `event.listener` tag because `TYPO3\CMS\Core\Attribute\AsEventListener` does not exist on
 * TYPO3 v12. Both stay inert until a test sets one of their static switches, so each test below
 * asks for the behaviour it is about. That the plugins render unrestricted while *no* extension
 * listens is what every other plugin test of this extension asserts - none of them loads this
 * fixture.
 */
final class AcademicPersonsProfileQueryConstraintTest extends AbstractAcademicPersonsTestCase
{
    use SiteBasedTestTrait;

    protected array $configurationToUseInTestInstance = [
        'SYS' => [
            'encryptionKey' => '4408d27a916d51e624b69af3554f516dbab61037a9f7b9fd6f81b4d3bedeccb6',
            'features' => [
                'subrequestPageErrors' => true,
            ],
        ],
        'FE' => [
            'cacheHash' => [
                'requireCacheHashPresenceParameters' => ['value', 'testing[value]', 'tx_testing_link[value]'],
                'excludedParameters' => ['L', 'tx_testing_link[excludedValue]'],
                'enforceValidation' => true,
            ],
            'debug' => false,
        ],
    ];

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    protected function setUp(): void
    {
        $this->coreExtensionsToLoad = array_unique([
            ...array_values($this->coreExtensionsToLoad),
            ...array_values([
                'typo3/cms-fluid-styled-content',
            ]),
        ]);
        $this->testExtensionsToLoad = array_unique([
            ...array_values($this->testExtensionsToLoad),
            ...array_values([
                'georgringer/numbered-pagination',
                'tests/plugin-templates',
                'tests/test-profile-query-constraints',
            ]),
        ]);
        parent::setUp();
    }

    protected function tearDown(): void
    {
        RestrictProfileQueryListener::reset();
        RestrictContractQueryListener::reset();
        GeneralUtility::rmdir($this->instancePath . '/typo3conf/sites', true);
        parent::tearDown();
    }

    private function setUpFrontendRootPageForTestCase(): void
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
                'setup' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript',
                    'EXT:academic_persons/Configuration/TypoScript/Default/setup.typoscript',
                    'EXT:test_plugin_templates/Configuration/TypoScript/setup.typoscript',
                    'EXT:academic_persons/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/Rendering.typoscript',
                ],
            ],
        );
        $this->writeSiteConfiguration(
            identifier: 'acme',
            site: $this->buildSiteConfiguration(
                rootPageId: 1,
                base: 'https://www.acme.com/',
            ),
            languages: [
                $this->buildDefaultLanguageConfiguration(
                    identifier: 'EN',
                    base: '/',
                ),
            ],
        );
    }

    private function renderHomePage(): string
    {
        $response = $this->executeFrontendSubRequest(
            new InternalRequest('https://www.acme.com/home'),
            new InternalRequestContext(),
        );
        $this->assertSame(200, $response->getStatusCode());
        return (string)$response->getBody();
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

    #[Test]
    public function listPluginShowsOnlyTheProfilesTheListenerLeaves(): void
    {
        RestrictProfileQueryListener::$lastName = 'Achterberg';
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsProfileQueryConstraint/listPlugin.csv');
        $this->setUpFrontendRootPageForTestCase();

        $content = $this->renderHomePage();
        $this->assertStringContainsString('<h2>Profilelist</h2>', $content);
        $this->assertStringContainsString('#0(2): Anna Achterberg', $content);
        $this->assertStringNotContainsString('Müllermann', $content);
        $this->assertStringNotContainsString('Huber', $content);
    }

    /**
     * The control of the test above: the same fixture extension is loaded and the plugin shows
     * every profile while no switch asks the listener for a constraint.
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
    public function selectedProfilesPluginShowsOnlyTheProfilesTheListenerLeaves(): void
    {
        RestrictProfileQueryListener::$lastName = 'Achterberg';
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsProfileQueryConstraint/selectedProfilesPlugin.csv');
        $this->setUpFrontendRootPageForTestCase();

        $content = $this->renderHomePage();
        $this->assertStringContainsString('<h2>Selected Profiles</h2>', $content);
        $this->assertStringContainsString('#0(2): Anna Achterberg', $content);
        $this->assertStringNotContainsString('Müllermann', $content);
        $this->assertStringNotContainsString('Huber', $content);
    }

    #[Test]
    public function selectedProfilesPluginShowsEverySelectionWhileTheListenerAddsNothing(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsProfileQueryConstraint/selectedProfilesPlugin.csv');
        $this->setUpFrontendRootPageForTestCase();

        $content = $this->renderHomePage();
        // The selection is "3,2,1" and the controller restores that order.
        $this->assertStringContainsString('#0(3): Horst Huber', $content);
        $this->assertStringContainsString('#1(2): Anna Achterberg', $content);
        $this->assertStringContainsString('#2(1): Max Müllermann', $content);
    }

    #[Test]
    public function selectedContractsPluginDoesNotShowTheContractTheListenerExcludes(): void
    {
        RestrictContractQueryListener::$excludedContractUid = 1;
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsProfileQueryConstraint/selectedContractsPlugin.csv');
        $this->setUpFrontendRootPageForTestCase();

        $content = $this->renderHomePage();
        $this->assertStringContainsString('<h2>Selected Contracts</h2>', $content);
        $this->assertStringContainsString('#0(2): Manager', $content);
        $this->assertStringNotContainsString('Worker', $content);
    }

    #[Test]
    public function selectedContractsPluginShowsBothWhileTheListenerAddsNothing(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsProfileQueryConstraint/selectedContractsPlugin.csv');
        $this->setUpFrontendRootPageForTestCase();

        $content = $this->renderHomePage();
        $this->assertStringContainsString('#0(2): Manager', $content);
        $this->assertStringContainsString('#1(1): Worker', $content);
    }

    /**
     * The order asserted here is the one the plugin defines and not an accident of the fixture:
     * the constants of these tests leave `demand.sortBy` empty, so the ordering is the
     * deterministic `uid` fallback of ACE-491 - which is also what a `lastName` sorting would
     * fall back to, the two remaining profiles sharing their last name.
     */
    #[Test]
    public function paginationCountsOnlyTheProfilesTheListenerLeaves(): void
    {
        RestrictProfileQueryListener::$lastName = 'Achterberg';
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsProfileQueryConstraint/paginatedListPlugin.csv');
        $this->setUpFrontendRootPageForTestCase();

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
     * A manual selection paginates through `ArrayPaginator` after the controller restored the
     * editor's order - a different path from the `QueryResultPaginator` of the test above, and
     * the one the `profile-list-pagination` capability is about.
     */
    #[Test]
    public function paginatedManualSelectionFollowsTheListenerAndKeepsItsOrder(): void
    {
        RestrictProfileQueryListener::$lastName = 'Achterberg';
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsProfileQueryConstraint/paginatedSelectionPlugin.csv');
        $this->setUpFrontendRootPageForTestCase();

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
        RestrictProfileQueryListener::$lastName = 'Achterberg';
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsProfileQueryConstraint/listPluginWithOrganisationalUnits.csv');
        $this->setUpFrontendRootPageForTestCase();

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
     * its own, and the listener sets a second one with `matching()` - which *replaces* what is
     * on the query. Three outcomes are possible:
     *
     * - folded in, as implemented: both conditions hold, and only Anna is left;
     * - the listener's call left standing: it replaced the editor's filter, so Anna **and**
     *   Clara render - the result is wider than the content element asked for;
     * - the listener's call dropped: the filter alone, so Anna and Max render.
     */
    #[Test]
    public function aConstraintAListenerSetsWithMatchingIsFoldedInBesideTheEditorFilter(): void
    {
        RestrictProfileQueryListener::$lastNameViaMatching = 'Achterberg';
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsProfileQueryConstraint/listPluginWithOrganisationalUnits.csv');
        $this->setUpFrontendRootPageForTestCase();

        $content = $this->renderHomePage();
        $this->assertStringContainsString('<h2>Profilelist</h2>', $content);
        $this->assertStringContainsString('#0(2): Anna Achterberg', $content);
        $this->assertStringNotContainsString('Clara', $content);
        $this->assertStringNotContainsString('Müllermann', $content);
        $this->assertStringNotContainsString('#1(', $content);
    }

    /**
     * The ordering stays with the plugin. The fixture listener asks for last name descending,
     * which would put Müllermann first; the list renders in the `uid` order the repository sets.
     */
    #[Test]
    public function aListenerCannotChangeTheOrderOfTheList(): void
    {
        RestrictProfileQueryListener::$reorderByLastNameDescending = true;
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsProfileQueryConstraint/listPlugin.csv');
        $this->setUpFrontendRootPageForTestCase();

        $content = $this->renderHomePage();
        $this->assertStringContainsString('#0(1): Max Müllermann', $content);
        $this->assertStringContainsString('#1(2): Anna Achterberg', $content);
        $this->assertStringContainsString('#2(3): Horst Huber', $content);
    }

    /**
     * Without a plugin context a listener cannot act for one plugin only, and the
     * `Feature-*.rst` says so. This pins it: a list and a card plugin on one page are both
     * narrowed by the same listener.
     *
     * The card half is asserted on the card's own markup rather than on the rendered name -
     * the list plugin above it renders that name too, so a name assertion alone would pass
     * with no card on the page at all. Its `col-12` blocks are one per profile the card
     * shows, which is what has to go from three to one.
     */
    #[Test]
    public function aListenerNarrowsEveryPluginOfTheExtensionAlike(): void
    {
        RestrictProfileQueryListener::$lastName = 'Achterberg';
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsProfileQueryConstraint/listAndCardPlugin.csv');
        $this->setUpFrontendRootPageForTestCase();

        $content = $this->renderHomePage();
        // The list renders exactly the one profile the listener leaves.
        $this->assertStringContainsString('#0(2): Anna Achterberg', $content);
        $this->assertStringNotContainsString('#1(', $content);
        // The card plugin on the same page did render, and it renders one profile of three.
        $this->assertStringContainsString('academic-persons-card', $content);
        $this->assertStringNotContainsString('list.noProfilesFound', $content);
        $this->assertSame(1, substr_count($content, 'col-12 col-md-6'));
        $this->assertRendersProfileName($content, 'Anna', 'Achterberg');
        $this->assertStringNotContainsString('Müllermann', $content);
        $this->assertStringNotContainsString('Huber', $content);
    }

    /**
     * The control of the test above, and the only coverage this branch has that the card
     * plugin renders at all: without a constraint the same card shows all three profiles.
     */
    #[Test]
    public function listAndCardPluginShowEveryProfileWhileTheListenerAddsNothing(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsProfileQueryConstraint/listAndCardPlugin.csv');
        $this->setUpFrontendRootPageForTestCase();

        $content = $this->renderHomePage();
        $this->assertStringContainsString('#2(3): Horst Huber', $content);
        $this->assertStringContainsString('academic-persons-card', $content);
        $this->assertStringNotContainsString('list.noProfilesFound', $content);
        $this->assertSame(3, substr_count($content, 'col-12 col-md-6'));
    }

    /**
     * The plain-list half of the `matching()` contract: the repository has no constraint of
     * its own here, which is the case a `if ($constraints !== [])` guard would get wrong by
     * leaving the listener's own `matching()` standing.
     *
     * This case alone cannot tell "folded in" from "left standing" - both render the same
     * empty list - which is why
     * {@see self::aConstraintAListenerSetsWithMatchingIsFoldedInBesideTheEditorFilter()}
     * exists. It pins that the plain list behaves the same way as the filtered one.
     */
    #[Test]
    public function aConstraintAListenerSetsWithMatchingAlsoAppliesWithoutAnEditorFilter(): void
    {
        RestrictProfileQueryListener::$lastNameViaMatching = 'Nobody';
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsProfileQueryConstraint/listPlugin.csv');
        $this->setUpFrontendRootPageForTestCase();

        $content = $this->renderHomePage();
        // The list template renders nothing at all for an empty result, heading included, so
        // the anchor is the content element frame around it.
        $this->assertStringContainsString('frame-type-academicpersons_list', $content);
        $this->assertStringNotContainsString('<h2>Profilelist</h2>', $content);
        $this->assertStringNotContainsString('#0(', $content);
        $this->assertStringNotContainsString('Müllermann', $content);
        $this->assertStringNotContainsString('Achterberg', $content);
        $this->assertStringNotContainsString('Huber', $content);
    }
}
