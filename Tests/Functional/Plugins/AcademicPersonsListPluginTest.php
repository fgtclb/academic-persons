<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Plugins;

use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Information\Typo3Version;

final class AcademicPersonsListPluginTest extends AbstractAcademicPersonsTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
        'DE' => ['id' => 1, 'title' => 'Deutsch', 'locale' => 'de_DE.UTF8', 'iso' => 'de', 'hrefLang' => 'de-DE', 'direction' => ''],
        'FR' => ['id' => 2, 'title' => 'French', 'locale' => 'fr_FR.UTF8', 'iso' => 'fr', 'hrefLang' => 'fr-FR', 'direction' => ''],
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
        ]);
        $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
        $this->addTestExtensionsToLoad('georgringer/numbered-pagination', 'tests/plugin-templates');
        parent::setUp();
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    private function setUpFrontendRootPageForTestCase(): void
    {
        $this->setUpFrontendRootPage(
            pageId: 1,
            typoScriptFiles: [
                'constants' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript',
                    'EXT:academic_persons/Configuration/TypoScript/constants.typoscript',
                    'EXT:test_plugin_templates/Configuration/TypoScript/constants.typoscript',
                    'EXT:academic_persons/Tests/Functional/Plugins/Fixtures/TypoScript/Constants/PluginConfiguration.typoscript',
                ],
                'setup' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript',
                    'EXT:academic_persons/Configuration/TypoScript/setup.typoscript',
                    'EXT:test_plugin_templates/Configuration/TypoScript/setup.typoscript',
                    'EXT:academic_persons/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/Rendering.typoscript',
                ],
            ],
        );
    }

    #[Test]
    public function defaultLanguageListDisplaysAllProfiles(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsListPlugin/defaultLanguageOnly.csv');
        $this->setUpFrontendRootPageForTestCase();
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration(
                identifier: 'EN',
                base: '/',
            ),
        ]);

        $content = $this->renderFrontendPage('https://www.acme.com/home');
        $this->assertStringContainsString('<h2>Profilelist</h2>', $content);
        $this->assertStringContainsString('#0(1): Max Müllermann', $content);
        $this->assertStringContainsString('#1(2): Horst Huber', $content);
    }

    #[Test]
    public function defaultLanguageListDisplaySingleSelectedProfile(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsListPlugin/defaultLanguageOnly_oneProfileSelected.csv');
        $this->setUpFrontendRootPageForTestCase();
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration(
                identifier: 'EN',
                base: '/',
            ),
        ]);

        $content = $this->renderFrontendPage('https://www.acme.com/home');
        $this->assertStringContainsString('<h2>Profilelist</h2>', $content);
        $this->assertStringContainsString('#0(2): Horst Huber', $content);
        $this->assertStringNotContainsString('Max Müllermann', $content);
    }

    #[Test]
    public function defaultLanguageListDisplaysSelectedProfilesInSelectedOrder(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsListPlugin/defaultLanguageOnly_selectedProfiles.csv');
        $this->setUpFrontendRootPageForTestCase();
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration(
                identifier: 'EN',
                base: '/'
            ),
        ]);

        $content = $this->renderFrontendPage('https://www.acme.com/home');
        $this->assertStringContainsString('<h2>Profilelist</h2>', $content);
        $this->assertStringContainsString('#0(2): Horst Huber', $content);
        $this->assertStringContainsString('#1(1): Max Müllermann', $content);
    }

    #[Test]
    public function fullyLocalizedListDisplaysDefaultLanguageProfilesForRequestedDefaultLanguage(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsListPlugin/fullyLocalized.csv');
        $this->setUpFrontendRootPageForTestCase();
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration(
                identifier: 'EN',
                base: '/',
            ),
            $this->buildLanguageConfiguration(
                identifier: 'DE',
                base: '/de/',
            ),
        ]);

        $content = $this->renderFrontendPage('https://www.acme.com/home');
        $this->assertStringContainsString('<h2>Profilelist</h2>', $content);
        $this->assertStringContainsString('#0(1): [EN] Max Müllermann', $content);
        $this->assertStringContainsString('#1(3): [EN] Horst Huber', $content);
    }

    #[Test]
    public function fullyLocalizedListDisplaysLocalizedProfilesForRequestedLanguage(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsListPlugin/fullyLocalized.csv');
        $this->setUpFrontendRootPageForTestCase();
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration(
                identifier: 'EN',
                base: '/',
            ),
            $this->buildLanguageConfiguration(
                identifier: 'DE',
                base: '/de/',
            ),
        ]);

        $content = $this->renderFrontendPage('https://www.acme.com/de/home');
        $this->assertStringContainsString('<h2>Profilelist</h2>', $content);
        $this->assertStringContainsString('#0(1): [DE] Max Müllermann', $content);
        $this->assertStringContainsString('#1(3): [DE] Horst Huber', $content);
    }

    #[Test]
    public function fullyLocalizedPagesAndTtContentListDisplaysOnlyLocalizedProfilesForRequestedLanguageWithNotAllProfilesLocalizedInStrictMode(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsListPlugin/fullyLocalizedPagesAndTtContent_notAllProfilesLocalized.csv');
        $this->setUpFrontendRootPageForTestCase();
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration(
                identifier: 'EN',
                base: '/',
            ),
            $this->buildLanguageConfiguration(
                identifier: 'DE',
                base: '/de/',
                fallbackIdentifiers: [],
                fallbackType: 'strict',
            ),
        ]);

        $content = $this->renderFrontendPage('https://www.acme.com/de/home');
        $this->assertStringContainsString('<h2>Profilelist</h2>', $content);
        $this->assertStringContainsString('#0(1): [DE] Max Müllermann', $content);
        $this->assertStringNotContainsString('Horst Huber', $content);
    }

    #[Test]
    public function fullyLocalizedPagesAndTtContentListDisplaysOnlyLocalizedProfilesForRequestedLanguageWithNotAllProfilesLocalizedInStrictModeWithFallbackForNonTranslatedSet(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsListPlugin/fullyLocalizedPagesAndTtContent_notAllProfilesLocalized_fallbackForNonTranslatedSet.csv');
        $this->setUpFrontendRootPageForTestCase();
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration(
                identifier: 'EN',
                base: '/',
            ),
            $this->buildLanguageConfiguration(
                identifier: 'DE',
                base: '/de/',
                fallbackIdentifiers: [],
                fallbackType: 'strict',
            ),
        ]);

        $content = $this->renderFrontendPage('https://www.acme.com/de/home');
        $this->assertStringContainsString('<h2>Profilelist</h2>', $content);
        $this->assertStringContainsString('#0(1): [DE] Max Müllermann', $content);
        $this->assertStringContainsString('#1(3): [EN] Horst Huber', $content);
    }

    #[Test]
    public function fullyLocalizedPagesAndTtContentListDisplaysLocalizedProfileAndDefaultLanguageProfileForRequestedLanguageWithNotAllProfilesLocalizedInFallbackMode(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsListPlugin/fullyLocalizedPagesAndTtContent_notAllProfilesLocalized.csv');
        $this->setUpFrontendRootPageForTestCase();
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration(
                identifier: 'EN',
                base: '/',
            ),
            $this->buildLanguageConfiguration(
                identifier: 'DE',
                base: '/de/',
                fallbackIdentifiers: ['EN'],
                fallbackType: 'fallback',
            ),
        ]);

        $content = $this->renderFrontendPage('https://www.acme.com/de/home');
        $this->assertStringContainsString('<h2>Profilelist</h2>', $content);
        $this->assertStringContainsString('#0(1): [DE] Max Müllermann', $content);
        $this->assertStringContainsString('#1(3): [EN] Horst Huber', $content);
    }

    #[Test]
    public function fullyLocalizedListDisplaysLocalizedSelectedProfilesForRequestedLanguageInSelectedOrder(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsListPlugin/fullyLocalized_selectedProfiles.csv');
        $this->setUpFrontendRootPageForTestCase();
        $this->writeSiteConfiguration(
            identifier: 'acme',
            site: $this->buildSiteConfiguration(
                rootPageId: 1,
                base: 'https://www.acme.com/'
            ),
            languages: [
                $this->buildDefaultLanguageConfiguration(
                    identifier: 'EN',
                    base: '/',
                ),
                $this->buildLanguageConfiguration(
                    identifier: 'DE',
                    base: '/de/',
                ),
            ],
        );

        $content = $this->renderFrontendPage('https://www.acme.com/de/home');
        $this->assertStringContainsString('<h2>Profilelist</h2>', $content);
        $this->assertStringContainsString('#0(3): [DE] Horst Huber', $content);
        $this->assertStringContainsString('#1(1): [DE] Max Müllermann', $content);
        $this->assertStringNotContainsString('[EN] Horst Huber', $content);
        $this->assertStringNotContainsString('[EN] Max Müllermann', $content);
    }

    /**
     * The selected profiles are fetched via Extbase persistence, which did not honour the site language
     * "fallbackType: strict" for untranslated (child) records before TYPO3 v14.3.6: untranslated selected
     * profiles were returned in their default language instead of being removed. This is a long-standing
     * Extbase regression, fixed in core with change 66694 (14.3 backport 94935, forge #88886), released
     * with TYPO3 v14.3.6 and the main line only (no TYPO3 v13.4 backport). The assertions below describe
     * the corrected v14.3.6+ behaviour, so the test only runs there.
     *
     * @todo Verify these assertions once TYPO3 v14 support is added to this extension and the test runs.
     * @see https://review.typo3.org/c/Packages/TYPO3.CMS/+/66694
     * @see https://review.typo3.org/c/Packages/TYPO3.CMS/+/94935
     * @see https://forge.typo3.org/issues/88886
     */
    #[Test]
    public function fullyLocalizedListDisplaysLocalizedSelectedProfilesForRequestedLanguageInSelectedOrderWithFallbackTypeStrictWhenNotAllProfilesAreLocalized(): void
    {
        if (version_compare((new Typo3Version())->getVersion(), '14.3.6', '<')) {
            $this->markTestSkipped(
                'Extbase honours "fallbackType: strict" for untranslated selected profiles only since '
                . 'TYPO3 v14.3.6 (core fix https://review.typo3.org/c/Packages/TYPO3.CMS/+/66694 and its 14.3 '
                . 'backport https://review.typo3.org/c/Packages/TYPO3.CMS/+/94935, forge #88886; not '
                . 'backported to v13.4).'
            );
        }
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsListPlugin/fullyLocalized_selectedProfiles_notAllProfilesLocalized.csv');
        $this->setUpFrontendRootPageForTestCase();
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration(
                identifier: 'EN',
                base: '/',
            ),
            $this->buildLanguageConfiguration(
                identifier: 'DE',
                base: '/de/',
                fallbackIdentifiers: [],
                fallbackType: 'strict',
            ),
        ]);

        $content = $this->renderFrontendPage('https://www.acme.com/de/home');
        $this->assertStringContainsString('<h2>Profilelist</h2>', $content);
        $this->assertStringContainsString('#0(1): [DE] Max Müllermann', $content);
        $this->assertStringNotContainsString('[EN] Horst Huber', $content);
        $this->assertStringNotContainsString('[DE] Horst Huber', $content);
        $this->assertStringNotContainsString('[EN] Max Müllermann', $content);
    }

    #[Test]
    public function fullyLocalizedListDisplaysLocalizedSelectedProfilesForRequestedLanguageInSelectedOrderWithFallbackTypeStrictWhenNotAllProfilesAreLocalizedButPluginFallbackSet(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsListPlugin/fullyLocalized_selectedProfiles_notAllProfilesLocalized_fallbackForNonTranslatedSet.csv');
        $this->setUpFrontendRootPageForTestCase();
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration(
                identifier: 'EN',
                base: '/',
            ),
            $this->buildLanguageConfiguration(
                identifier: 'DE',
                base: '/de/',
            ),
        ]);

        $content = $this->renderFrontendPage('https://www.acme.com/de/home');
        $this->assertStringContainsString('<h2>Profilelist</h2>', $content);
        $this->assertStringContainsString('#0(3): [EN] Horst Huber', $content);
        $this->assertStringContainsString('#1(1): [DE] Max Müllermann', $content);
        $this->assertStringNotContainsString('[DE] Horst Huber', $content);
        $this->assertStringNotContainsString('[EN] Max Müllermann', $content);
    }

    /**
     * The list plugin reaches the same `profileList` branch of
     * `ProfileRepository::applyDemandForQuery()` as the card plugin, and used to render
     * default language profiles on a `fallbackType: free` site for the same reason
     * (ACE-341). Kept here as well so a regression in that shared query cannot pass by
     * only breaking one of the two plugins.
     */
    #[Test]
    public function fullyLocalizedListDisplaysLocalizedSelectedProfilesForRequestedLanguageWithFallbackTypeFree(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsListPlugin/fullyLocalized_selectedProfiles.csv');
        $this->setUpFrontendRootPageForTestCase();
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration(
                identifier: 'EN',
                base: '/',
            ),
            $this->buildLanguageConfiguration(
                identifier: 'DE',
                base: '/de/',
                fallbackIdentifiers: [],
                fallbackType: 'free',
            ),
        ]);

        $content = $this->renderFrontendPage('https://www.acme.com/de/home');
        $this->assertStringContainsString('[DE] Horst Huber', $content);
        $this->assertStringContainsString('[DE] Max Müllermann', $content);
        $this->assertStringNotContainsString('[EN] Horst Huber', $content);
        $this->assertStringNotContainsString('[EN] Max Müllermann', $content);
    }

    #[Test]
    public function fullyLocalizedListDisplaysLocalizedSelectedProfilesForRequestedLanguageInSelectedOrderWithFallbackTypeFallbackWhenNotAllProfilesAreLocalized(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsListPlugin/fullyLocalized_selectedProfiles_notAllProfilesLocalized.csv');
        $this->setUpFrontendRootPageForTestCase();
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration(
                identifier: 'EN',
                base: '/',
            ),
            $this->buildLanguageConfiguration(
                identifier: 'DE',
                base: '/de/',
                fallbackIdentifiers: ['EN'],
                fallbackType: 'fallback',
            ),
        ]);

        $content = $this->renderFrontendPage('https://www.acme.com/de/home');
        $this->assertStringContainsString('<h2>Profilelist</h2>', $content);
        $this->assertStringContainsString('#0(3): [EN] Horst Huber', $content);
        $this->assertStringContainsString('#1(1): [DE] Max Müllermann', $content);
        $this->assertStringNotContainsString('[DE] Horst Huber', $content);
        $this->assertStringNotContainsString('[EN] Max Müllermann', $content);
    }
}
