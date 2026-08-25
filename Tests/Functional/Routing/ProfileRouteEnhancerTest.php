<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Routing;

use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Exercises the three route enhancer files shipped in `Configuration/Routes/` together.
 *
 * They are shipped as three files because the three plugins are three content elements,
 * but a site that offers all three of them merges all three enhancers into one site
 * configuration - and that is where they start to interfere. TYPO3 offers every enhancer
 * of a site to every page of that site unless the enhancer carries `limitToPages`, and
 * `PageUriMatcher::matchCollection()` returns the *first* candidate route whose regular
 * expression matches and whose aspects resolve. `ListAndDetail.yaml` and `Detail.yaml`
 * declare a byte-identical `/{profile_name}` route, backed by the same
 * `PersistedAliasMapper` on `tx_academicpersons_domain_model_profile.slug`, so whichever
 * of the two is merged into the site configuration first answers for both of their
 * pages. The same holds for the `/{letter}` route that `List.yaml` and
 * `ListAndDetail.yaml` share.
 *
 * Generation is not affected, because `ExtbasePluginEnhancer::enhanceForGeneration()`
 * returns early when the parameters carry no value for *its* namespace. That asymmetry
 * is the defect: the extension generates a speaking URL that the extension then cannot
 * resolve back into the plugin it was generated for.
 *
 * The site configuration written here therefore reads the shipped files rather than
 * inlining a copy of them, and merges them in the order they are documented in - the
 * order is what decides the outcome, so it is spelled out in `loadShippedRouteEnhancers()`
 * instead of being an accident of a `glob()`.
 */
final class ProfileRouteEnhancerTest extends AbstractAcademicPersonsTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    /**
     * The Extbase namespaces the three enhancers derive from `extension: AcademicPersons`
     * plus their `plugin` value: `ExtbasePluginEnhancer::__construct()` builds
     * `'tx_' . strtolower($extension . '_' . $plugin)`. They have to be the plugin
     * signatures `ext_localconf.php` registers, otherwise generation never enters the
     * enhancer and silently falls back to a query string.
     */
    private const LIST_PLUGIN_NAMESPACE = 'tx_academicpersons_list';
    private const LIST_AND_DETAIL_PLUGIN_NAMESPACE = 'tx_academicpersons_listanddetail';
    private const DETAIL_PLUGIN_NAMESPACE = 'tx_academicpersons_detail';

    /**
     * The page uids of the fixture page tree, one page per plugin, mirroring the site an
     * integrator ends up with when they use all three content elements.
     */
    private const LIST_PAGE = 2;
    private const LIST_AND_DETAIL_PAGE = 3;
    private const DETAIL_PAGE = 4;

    /**
     * A letter that no fixture last name starts with. The alphabet filter reaches the
     * database as `last_name LIKE '<letter>%'`, and `LIKE` is case sensitive on
     * PostgreSQL while it is not on MariaDB, MySQL and SQLite - so a letter that matches
     * a record would assert a different result per DBMS. A letter that matches nothing
     * everywhere still tells the two cases apart: an ignored filter renders the full
     * list, an applied one renders an empty list.
     */
    private const NON_MATCHING_ALPHABET_FILTER = 'x';

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration();
        $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
        $this->addTestExtensionsToLoad('georgringer/numbered-pagination', 'tests/plugin-templates');
        parent::setUp();
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    /**
     * @param bool $limitEnhancersToTheirOwnPage Whether each enhancer is confined to the
     *        page that carries its plugin, which is what an integrator has to add by hand:
     *        the shipped files cannot carry page uids of an installation they do not know.
     */
    private function setUpTestCase(bool $limitEnhancersToTheirOwnPage): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ProfileRouteEnhancer/pluginsOnSeparatePages.csv');
        $this->setUpFrontendRootPage(
            pageId: 1,
            typoScriptFiles: [
                'constants' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript',
                    'EXT:academic_persons/Configuration/TypoScript/Default/constants.typoscript',
                    'EXT:test_plugin_templates/Configuration/TypoScript/constants.typoscript',
                    // Shared with the plugin rendering tests rather than copied: it turns the
                    // alphabetical grouping of the shipped defaults off, which is what makes the
                    // list template render the flat markup the assertions below read.
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

        $routeEnhancers = $this->loadShippedRouteEnhancers();
        if ($limitEnhancersToTheirOwnPage) {
            $routeEnhancers['ProfileListPlugin']['limitToPages'] = [self::LIST_PAGE];
            $routeEnhancers['ProfileListAndDetailPlugin']['limitToPages'] = [self::LIST_AND_DETAIL_PAGE];
            $routeEnhancers['ProfileDetailPlugin']['limitToPages'] = [self::DETAIL_PAGE];
        }

        $this->writeSiteConfiguration(
            identifier: 'acme',
            site: $this->buildSiteConfiguration(
                rootPageId: 1,
                base: self::FRONTEND_PLUGIN_TEST_BASE,
                additionalRootConfiguration: [
                    'routeEnhancers' => $routeEnhancers,
                ],
            ),
            languages: [
                $this->buildDefaultLanguageConfiguration(
                    identifier: 'EN',
                    base: '/',
                ),
            ],
        );
    }

    /**
     * Reads the three shipped files and merges them the way the documentation tells an
     * integrator to import them: `List`, `ListAndDetail`, `Detail`.
     *
     * Parsed with the plain YAML parser rather than through TYPO3's `YamlFileLoader`. That
     * loader adds `imports` resolution and placeholder substitution, neither of which the
     * shipped files use. The point here is that the shipped files themselves are loaded,
     * not which reader loads them.
     *
     * @return array<string, array<string, mixed>>
     */
    private function loadShippedRouteEnhancers(): array
    {
        $routeEnhancers = [];
        foreach (['List', 'ListAndDetail', 'Detail'] as $fileName) {
            $path = GeneralUtility::getFileAbsFileName(
                sprintf('EXT:academic_persons/Configuration/Routes/%s.yaml', $fileName)
            );
            $configuration = Yaml::parseFile($path);
            $this->assertIsArray($configuration, sprintf('The shipped "%s.yaml" is not a YAML mapping.', $fileName));

            $shippedEnhancers = $configuration['routeEnhancers'] ?? null;
            $this->assertIsArray(
                $shippedEnhancers,
                sprintf('The shipped "%s.yaml" declares no routeEnhancers.', $fileName),
            );
            $this->assertNotSame([], $shippedEnhancers);

            // A plain merge, so a duplicate enhancer key would overwrite instead of being
            // appended - and the assertion below states that the three keys are distinct,
            // which is exactly why the routes collide instead of the enhancers doing so.
            $routeEnhancers = array_merge($routeEnhancers, $shippedEnhancers);
        }
        $this->assertCount(3, $routeEnhancers);

        /** @var array<string, array<string, mixed>> $routeEnhancers */
        return $routeEnhancers;
    }

    /**
     * @param array<string, mixed> $parameters
     */
    private function generateSpeakingUri(int $pageId, array $parameters): string
    {
        return (string)$this->get(SiteFinder::class)
            ->getSiteByIdentifier('acme')
            ->getRouter()
            ->generateUri($pageId, $parameters);
    }

    private function generateDetailUriForDetailPlugin(int $profileUid): string
    {
        // No `controller`/`action` needed: `defaultController: 'Profile::detail'` of the
        // shipped file supplies them when the parameters carry neither.
        return $this->generateSpeakingUri(self::DETAIL_PAGE, [
            self::DETAIL_PLUGIN_NAMESPACE => [
                'profile' => $profileUid,
            ],
        ]);
    }

    private function generateDetailUriForListAndDetailPlugin(int $profileUid): string
    {
        // Here the pair is required: the combined plugin defaults to `Profile::list`, so
        // without it the detail route variant is not even offered to the generator.
        return $this->generateSpeakingUri(self::LIST_AND_DETAIL_PAGE, [
            self::LIST_AND_DETAIL_PLUGIN_NAMESPACE => [
                'controller' => 'Profile',
                'action' => 'detail',
                'profile' => $profileUid,
            ],
        ]);
    }

    #[Test]
    public function shippedRouteEnhancersDeclareTheThreePluginsOfTheExtension(): void
    {
        $routeEnhancers = $this->loadShippedRouteEnhancers();

        $this->assertSame(
            ['ProfileListPlugin', 'ProfileListAndDetailPlugin', 'ProfileDetailPlugin'],
            array_keys($routeEnhancers),
        );
        foreach ($routeEnhancers as $identifier => $enhancer) {
            $this->assertSame('Extbase', $enhancer['type'], $identifier);
            $this->assertSame('AcademicPersons', $enhancer['extension'], $identifier);
            // Without `routes` an Extbase enhancer builds no route variant at all: it is
            // loaded, it is valid, and it does nothing.
            $this->assertNotSame([], $enhancer['routes'] ?? [], $identifier);
        }
        $this->assertSame('List', $routeEnhancers['ProfileListPlugin']['plugin']);
        $this->assertSame('ListAndDetail', $routeEnhancers['ProfileListAndDetailPlugin']['plugin']);
        $this->assertSame('Detail', $routeEnhancers['ProfileDetailPlugin']['plugin']);
    }

    /**
     * The mechanical reason for everything below: two enhancers of the same site declare
     * the same route, resolved by the same aspect. Nothing distinguishes them for the
     * matcher, so the one that is merged first wins for every page of the site.
     */
    #[Test]
    public function combinedAndDetailPluginDeclareAnIdenticalProfileNameRoute(): void
    {
        $routeEnhancers = $this->loadShippedRouteEnhancers();

        $detailRouteOfCombinedPlugin = $routeEnhancers['ProfileListAndDetailPlugin']['routes'][0];
        $detailRouteOfDetailPlugin = $routeEnhancers['ProfileDetailPlugin']['routes'][0];

        $this->assertSame('/{profile_name}', $detailRouteOfCombinedPlugin['routePath']);
        $this->assertSame($detailRouteOfCombinedPlugin, $detailRouteOfDetailPlugin);
        $this->assertSame(
            $routeEnhancers['ProfileListAndDetailPlugin']['aspects']['profile_name'],
            $routeEnhancers['ProfileDetailPlugin']['aspects']['profile_name'],
        );
        // The second collision, between the list plugin and the combined plugin.
        $this->assertSame(
            $routeEnhancers['ProfileListPlugin']['routes'][1],
            $routeEnhancers['ProfileListAndDetailPlugin']['routes'][2],
        );
    }

    /**
     * Characterization test: it documents why `limitToPages` is required, it does not
     * describe behaviour anybody wants.
     *
     * The detail page carries the `Detail` plugin and nothing else, and the URL below is
     * the one the extension itself generates for it (see
     * {@see self::speakingDetailUriIsGeneratedForTheDetailPluginWithoutLimitToPages()}).
     * It still does not arrive: `ProfileListAndDetailPlugin` is merged before
     * `ProfileDetailPlugin`, its identical `/{profile_name}` route matches first, and the
     * profile is therefore handed over in the `tx_academicpersons_listanddetail` namespace.
     * The `Detail` plugin reads `tx_academicpersons_detail`, receives nothing, and
     * {@see \FGTCLB\AcademicPersons\Controller\ProfileController::detailAction()} answers
     * `404` for a `null` argument.
     *
     * What the plugin renders instead is the error document of that response, nested into
     * the content element - which is what this asserts, because it is the one half of the
     * outcome both supported core versions agree on. The status code is the other half,
     * and it is not: see the test below.
     *
     * Should the profile ever start rendering here, the limitation is gone and this test
     * is the thing to delete - not the thing to adjust.
     */
    #[Test]
    public function detailPluginPageDoesNotRenderTheProfileWithoutLimitToPages(): void
    {
        $this->setUpTestCase(limitEnhancersToTheirOwnPage: false);

        $body = (string)$this->requestFrontendPage('https://www.acme.com/profile/max-muellermann')->getBody();

        $this->assertStringNotContainsString('<h2>Profiledetailpage</h2>', $body);
        $this->assertStringNotContainsString('#1: [EN] Max Müllermann', $body);
    }

    /**
     * The status code of the same request, which only TYPO3 v14 lets a functional test see.
     *
     * Extbase hands a plugin response with a status of 300 or more back to the frontend
     * differently on the two supported versions. On v13
     * {@see \TYPO3\CMS\Extbase\Core\Bootstrap::handleFrontendRequest()} emits it with a
     * bare `header()` call, guarded by `headers_sent()`, which a functional request never
     * observes - the response object it gets back still says `200`. On v14 the same method
     * writes it into the `frontend.response.data` attribute, and it arrives.
     *
     * The browser behaviour is the same on both: the development instances answered `404`
     * on v13 and on v14 alike before this was fixed. Only the test can see it on one of
     * them, so this half is grouped out rather than asserted loosely.
     */
    #[Test]
    #[Group('not-core-13')]
    public function detailPluginPageAnswersNotFoundWithoutLimitToPages(): void
    {
        $this->setUpTestCase(limitEnhancersToTheirOwnPage: false);

        $response = $this->requestFrontendPage('https://www.acme.com/profile/max-muellermann');

        $this->assertSame(404, $response->getStatusCode());
    }

    #[Test]
    public function detailPluginPageResolvesItsOwnSpeakingUriWithLimitToPages(): void
    {
        $this->setUpTestCase(limitEnhancersToTheirOwnPage: true);

        $content = $this->renderFrontendPage('https://www.acme.com/profile/max-muellermann');

        $this->assertStringContainsString('<h2>Profiledetailpage</h2>', $content);
        $this->assertStringContainsString('#1: [EN] Max Müllermann', $content);
    }

    /**
     * The other half of the same site: confining the enhancers must not cost the combined
     * plugin the detail URL it had before.
     */
    #[Test]
    public function combinedPluginPageStillResolvesItsOwnSpeakingUriWithLimitToPages(): void
    {
        $this->setUpTestCase(limitEnhancersToTheirOwnPage: true);

        $content = $this->renderFrontendPage('https://www.acme.com/team/horst-huber');

        $this->assertStringContainsString('<h2>Profiledetailpage</h2>', $content);
        $this->assertStringContainsString('#2: [EN] Horst Huber', $content);
    }

    /**
     * Characterization test, the `/{letter}` half of the same defect.
     *
     * `ProfileListPlugin` is merged first and its `/{letter}` route is identical to the
     * one of `ProfileListAndDetailPlugin`, so on the combined plugin's page the alphabet
     * filter is delivered in the `tx_academicpersons_list` namespace. The combined plugin
     * never sees it and renders the unfiltered list - with a letter that matches no last
     * name at all.
     */
    #[Test]
    public function alphabetFilterOfTheCombinedPluginIsIgnoredWithoutLimitToPages(): void
    {
        $this->setUpTestCase(limitEnhancersToTheirOwnPage: false);

        $content = $this->renderFrontendPage('https://www.acme.com/team/' . self::NON_MATCHING_ALPHABET_FILTER);

        $this->assertStringContainsString('<h2>Profilelist</h2>', $content);
        $this->assertStringContainsString('[EN] Max Müllermann', $content);
        $this->assertStringContainsString('[EN] Horst Huber', $content);
    }

    #[Test]
    public function alphabetFilterOfTheCombinedPluginIsAppliedWithLimitToPages(): void
    {
        $this->setUpTestCase(limitEnhancersToTheirOwnPage: true);

        $content = $this->renderFrontendPage('https://www.acme.com/team/' . self::NON_MATCHING_ALPHABET_FILTER);

        // The list template renders nothing at all for an empty result, so the absence of
        // its heading is what an applied filter looks like from the outside.
        $this->assertStringNotContainsString('<h2>Profilelist</h2>', $content);
        $this->assertStringNotContainsString('[EN] Max Müllermann', $content);
        $this->assertStringNotContainsString('[EN] Horst Huber', $content);
    }

    /**
     * The list plugin is the one that is merged first, so its own page is the one page the
     * collision cannot hurt - with or without `limitToPages` the filter arrives.
     */
    #[Test]
    public function alphabetFilterOfTheListPluginIsAppliedWithLimitToPages(): void
    {
        $this->setUpTestCase(limitEnhancersToTheirOwnPage: true);

        $content = $this->renderFrontendPage('https://www.acme.com/persons/' . self::NON_MATCHING_ALPHABET_FILTER);

        $this->assertStringNotContainsString('<h2>Profilelist</h2>', $content);
        $this->assertStringNotContainsString('[EN] Max Müllermann', $content);
    }

    /**
     * The counterpart of the two `/{letter}` tests above: the filter URL an integrator sees
     * in the alphabet navigation is a speaking one for both plugins that offer a list, and
     * confining the enhancers does not change that.
     */
    #[Test]
    public function speakingAlphabetFilterUrisAreGeneratedForBothListPlugins(): void
    {
        $this->setUpTestCase(limitEnhancersToTheirOwnPage: true);

        // Neither call passes `controller`/`action`: both enhancers declare
        // `defaultController: 'Profile::list'`, which is the pair these routes belong to.
        $this->assertSame(
            'https://www.acme.com/persons/' . self::NON_MATCHING_ALPHABET_FILTER,
            $this->generateSpeakingUri(self::LIST_PAGE, [
                self::LIST_PLUGIN_NAMESPACE => [
                    'demand' => ['alphabetFilter' => self::NON_MATCHING_ALPHABET_FILTER],
                ],
            ]),
        );
        $this->assertSame(
            'https://www.acme.com/team/' . self::NON_MATCHING_ALPHABET_FILTER,
            $this->generateSpeakingUri(self::LIST_AND_DETAIL_PAGE, [
                self::LIST_AND_DETAIL_PLUGIN_NAMESPACE => [
                    'demand' => ['alphabetFilter' => self::NON_MATCHING_ALPHABET_FILTER],
                ],
            ]),
        );
    }

    #[Test]
    public function speakingDetailUrisAreGeneratedForBothPluginsWithLimitToPages(): void
    {
        $this->setUpTestCase(limitEnhancersToTheirOwnPage: true);

        $detailPluginUri = $this->generateDetailUriForDetailPlugin(1);
        $combinedPluginUri = $this->generateDetailUriForListAndDetailPlugin(2);

        $this->assertSame('https://www.acme.com/profile/max-muellermann', $detailPluginUri);
        $this->assertSame('https://www.acme.com/team/horst-huber', $combinedPluginUri);
        // The arguments went into the path, so nothing of them may be left in a query string.
        $this->assertStringNotContainsString('?', $detailPluginUri);
        $this->assertStringNotContainsString('?', $combinedPluginUri);
    }

    /**
     * Generation is namespace scoped - `ExtbasePluginEnhancer::enhanceForGeneration()`
     * returns immediately when the parameters carry no value for its own namespace - so
     * the collision that breaks resolving leaves generation untouched.
     *
     * That is the asymmetry ACE-470 is about, and it is the reason the defect is not
     * obvious: the link in the list is the correct speaking URL either way, and only
     * following it tells the two configurations apart.
     */
    #[Test]
    public function speakingDetailUriIsGeneratedForTheDetailPluginWithoutLimitToPages(): void
    {
        $this->setUpTestCase(limitEnhancersToTheirOwnPage: false);

        $this->assertSame(
            'https://www.acme.com/profile/max-muellermann',
            $this->generateDetailUriForDetailPlugin(1),
        );
        $this->assertSame(
            'https://www.acme.com/team/horst-huber',
            $this->generateDetailUriForListAndDetailPlugin(2),
        );
    }
}
