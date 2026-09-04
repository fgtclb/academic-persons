<?php

declare(strict_types=1);

/*
 * This file is part of the fgtclb/academic extension collection.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace FGTCLB\AcademicPersons\Tests\Functional\Plugins;

use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend;

/**
 * Renders the shipped public profile - the `Detail.html` template with the `profile` map of
 * the shipped `Settings.yaml` - through the detail plugin, and pins what reaches the page.
 *
 * Unlike {@see AcademicPersonsDetailPluginTest} this class loads no template override, so it is
 * the one place the shipped template, its partials and the registered icons are rendered.
 */
final class AcademicPersonsPublicProfilePluginTest extends AbstractAcademicPersonsTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    /**
     * The identifiers the partials below `Profile/PublicProfile/` render, all registered in
     * `Configuration/Icons.php`.
     */
    private const ICON_IDENTIFIERS = [
        'academic-persons-envelope',
        'academic-persons-phone',
        'academic-persons-address',
        'academic-persons-room',
        'academic-persons-detail-plus',
        'academic-persons-detail-minus',
    ];

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration([
            'FE' => [
                'cacheHash' => [
                    'requireCacheHashPresenceParameters' => ['value', 'testing[value]', 'tx_testing_link[value]'],
                    'excludedParameters' => ['L', 'tx_testing_link[excludedValue]'],
                    'enforceValidation' => true,
                ],
            ],
            'SYS' => [
                'caching' => [
                    'cacheConfigurations' => [
                        // The testing framework replaces the page cache by a NullBackend. The
                        // database backend is restored here so that what the frontend writes to
                        // it can be read back: a non-cacheable plugin leaves an `INT_SCRIPT`
                        // marker in the cached page, a cacheable one does not.
                        'pages' => [
                            'backend' => Typo3DatabaseBackend::class,
                            // Uncompressed, so the row can be searched as text.
                            'options' => [
                                'compression' => false,
                            ],
                        ],
                    ],
                ],
            ],
        ]);
        $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
        parent::setUp();
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    private function renderShippedProfile(): string
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsPublicProfilePlugin/shippedLayout.csv');
        $this->setUpFrontendRootPage(
            pageId: 1,
            typoScriptFiles: [
                'constants' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript',
                    'EXT:academic_persons/Configuration/TypoScript/Default/constants.typoscript',
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

        return $this->renderFrontendPage(
            'https://www.acme.com/home?' . http_build_query([
                'tx_academicpersons_detail' => [
                    'controller' => 'Profile',
                    'action' => 'detail',
                    'profile' => 1,
                ],
                'cHash' => '13c8ec3ab2a317651a40bd164df8a366',
            ])
        );
    }

    /**
     * @return list<int> the positions of the needles in the haystack, each asserted to exist
     */
    private function positionsOf(string $haystack, string ...$needles): array
    {
        $positions = [];
        foreach ($needles as $needle) {
            $position = strpos($haystack, $needle);
            $this->assertNotFalse($position, sprintf('"%s" is not part of the rendered page.', $needle));
            $positions[] = $position;
        }
        return $positions;
    }

    /**
     * The `right` column of the shipped `profile.structure`, in its order: headline, position,
     * image, contact, subline, profile entries, timeline sections - and the navigation from the
     * `left` column in the aside before all of them.
     */
    #[Test]
    public function shippedStructureRendersItsElementsInTheConfiguredOrder(): void
    {
        $content = $this->renderShippedProfile();

        $positions = $this->positionsOf(
            $content,
            'academic-persons-detail__navigation"',
            'academic-persons-detail__headline"',
            'academic-persons-detail__positions"',
            'academic-persons-detail__contact"',
            'academic-persons-detail__subline"',
            'academic-persons-detail__profile-entries',
            'academic-persons-detail__links"',
            'academic-persons-detail__menu-section-data"',
        );
        $sorted = $positions;
        sort($sorted);
        $this->assertSame($sorted, $positions, 'The elements are not rendered in the order of profile.structure.');
    }

    /**
     * `details.headline` lists title, first, middle and last name; the empty middle name renders
     * no part. `position` and `contact` read the contract, `profileEntries` the rich text fields
     * of the profile.
     */
    #[Test]
    public function shippedDetailsRenderTheConfiguredProperties(): void
    {
        $content = $this->renderShippedProfile();

        $this->assertStringContainsString('academic-persons-detail__headline-part">Prof. Dr.</span>', $content);
        // The plugin is a content element: no page landmark, no page heading.
        $this->assertStringNotContainsString('<main', $content);
        $this->assertStringNotContainsString('<aside', $content);
        $this->assertStringNotContainsString('<h1', $content);
        $this->assertStringContainsString('<h2 class="academic-persons-detail__headline"', $content);
        $this->assertStringContainsString('academic-persons-detail__headline-part">[EN] Max</span>', $content);
        $this->assertStringContainsString('academic-persons-detail__headline-part">Müllermann</span>', $content);
        $this->assertSame(3, substr_count($content, 'academic-persons-detail__headline-part"'));
        $this->assertStringContainsString('academic-persons-detail__position">Professor of Applied Physics</p>', $content);
        $this->assertStringContainsString('max.muellermann@example.com', $content);
        $this->assertStringContainsString('>+49 30 123456</a>', $content);
        $this->assertStringContainsString('Main Street 1', $content);
        $this->assertStringContainsString('Main Campus', $content);
        $this->assertStringContainsString('Room: A 1.23', (string)preg_replace('/\s+/', ' ', $content));
        $this->assertStringContainsString('Academic activities and publications', $content);
        $this->assertStringContainsString('<p>Chairs the faculty council.</p>', $content);
        $this->assertStringContainsString('<p>Applied physics.</p>', $content);
        $this->assertStringContainsString('id="academic-persons-profile-entry-1-1-miscellaneous"', $content);
    }

    /**
     * The `links` element renders the configured link properties with their companion title
     * property as the link text, and falls back to the URL where that title is empty. Both are
     * properties the static template rendered and the settings graph would otherwise have no
     * element for.
     */
    #[Test]
    public function linksElementRendersTheConfiguredLinkProperties(): void
    {
        $content = $this->renderShippedProfile();
        $normalized = (string)preg_replace('/\s+/', ' ', $content);

        $this->assertStringContainsString('>Personal page</a>', $normalized);
        $this->assertStringContainsString('>https://www.acme.com/home?publications=1</a>', $normalized);
        $this->assertStringContainsString('Website', $normalized);
        $this->assertStringContainsString('Link to publications', $normalized);
    }

    /**
     * The contact rows name the type of every record again - the label of the address type, the
     * raw type of an email address and of a phone number. The `tel:` target drops the spaces of
     * the stored number, which are not valid in the URI, while the link text keeps them.
     */
    #[Test]
    public function contactRowsCarryTheRecordTypesAndACleanTelUri(): void
    {
        $content = $this->renderShippedProfile();
        $normalized = (string)preg_replace('/\s+/', ' ', $content);

        $this->assertStringContainsString('academic-persons-detail__contact-type"> Business ', $normalized);
        $this->assertStringContainsString('academic-persons-detail__contact-type">(work)</span>', $normalized);
        $this->assertStringContainsString('href="tel:+4930123456"', $normalized);
    }

    /**
     * `menuSections` and `menuSectionsDatas` agree: the navigation links the relations that
     * have records - vita, publications, lectures - and skips the ones that have none, and every
     * link has its section.
     */
    #[Test]
    public function navigationLinksExactlyTheSectionsWithRecords(): void
    {
        $content = $this->renderShippedProfile();

        foreach (['academicCareer', 'publications', 'lectures'] as $menuSection) {
            $this->assertStringContainsString('href="#academic-persons-detail-section-1-' . $menuSection . '"', $content);
            $this->assertStringContainsString('id="academic-persons-detail-section-1-' . $menuSection . '"', $content);
        }
        foreach (['researchProjects', 'membershipsCommitteeActivities', 'networkCooperation'] as $menuSection) {
            $this->assertStringNotContainsString('academic-persons-detail-section-1-' . $menuSection, $content);
        }
    }

    /**
     * The years are printed as they are stored: a closed range, a single year, and the two open
     * ranges with their "since" and "till" prefix. Nothing about them is locale dependent, so
     * no formatting view helper is involved.
     */
    #[Test]
    public function timelineYearsRenderAsStored(): void
    {
        $content = $this->renderShippedProfile();
        $normalized = (string)preg_replace('/\s+/', ' ', $content);

        $this->assertStringContainsString('2015 – 2018', $normalized);
        $this->assertStringContainsString('__timeline-date"> 2024 </p>', $normalized);
        $this->assertStringContainsString('Since 2020', $normalized);
        // The entry that carries an end year only takes the other branch.
        $this->assertStringContainsString('Till 2019', $normalized);
    }

    /**
     * `<core:icon>` never fails on an unknown identifier: `IconFactory` answers with the
     * `default-not-found` placeholder and the identifier asked for is gone from the markup. Both
     * mistakes are caught here - an identifier that no longer resolves, and a registration a
     * partial did not follow.
     */
    #[Test]
    public function profileRendersOnlyResolvableIcons(): void
    {
        $content = $this->renderShippedProfile();

        $this->assertStringNotContainsString('default-not-found', $content);
        foreach (self::ICON_IDENTIFIERS as $identifier) {
            $this->assertStringContainsString('data-identifier="' . $identifier . '"', $content);
        }
        // Inlined, not an `<img>`: the markup is the SVG itself, so it follows the text colour.
        $this->assertStringNotContainsString('Resources/Public/Icons/envelope.svg', $content);
        $this->assertStringContainsString('<svg', $content);
    }

    /**
     * The detail action is cacheable: the page is cached as a whole, without an `INT_SCRIPT`
     * marker where the plugin sits. Otherwise the most requested page of the extension would be
     * rendered on every hit and the `profile_detail_view_*` cache tags the controller adds would
     * tag nothing.
     */
    #[Test]
    public function detailPageIsCachedWithoutANonCacheableMarker(): void
    {
        $content = $this->renderShippedProfile();
        $this->assertStringContainsString('academic-persons-detail', $content);

        $rows = $this->getConnectionPool()
            ->getConnectionForTable('cache_pages')
            ->select(['content'], 'cache_pages')
            ->fetchAllAssociative();
        $this->assertCount(1, $rows, 'The rendered page did not reach the page cache.');
        $cached = $rows[0]['content'];
        $cached = is_resource($cached) ? (string)stream_get_contents($cached) : (string)$cached;
        $this->assertStringContainsString('academic-persons-detail', $cached);
        $this->assertStringNotContainsString('INT_SCRIPT.', $cached);
    }
}
