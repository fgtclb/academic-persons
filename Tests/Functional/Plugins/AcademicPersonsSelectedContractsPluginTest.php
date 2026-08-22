<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Plugins;

use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Information\Typo3Version;

final class AcademicPersonsSelectedContractsPluginTest extends AbstractAcademicPersonsTestCase
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
    }

    #[Test]
    public function defaultLanguageOnly_allContractsSelected(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsSelectedContractsPlugin/defaultLanguageOnly_allContractsSelected.csv');
        $this->setUpFrontendRootPageForTestCase();
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration(
                identifier: 'EN',
                base: '/',
            ),
        ]);

        $content = $this->renderFrontendPage('https://www.acme.com/home');
        $this->assertStringContainsString('<h2>Selected Contracts</h2>', $content);
        $this->assertStringContainsString('#0(2): Manager', $content);
        $this->assertStringContainsString('#1(1): Worker', $content);
    }

    #[Test]
    public function defaultLanguageOnly_oneContractSelected(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsSelectedContractsPlugin/defaultLanguageOnly_oneContractSelected.csv');
        $this->setUpFrontendRootPageForTestCase();
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration(
                identifier: 'EN',
                base: '/',
            ),
        ]);

        $content = $this->renderFrontendPage('https://www.acme.com/home');
        $this->assertStringContainsString('<h2>Selected Contracts</h2>', $content);
        $this->assertStringContainsString('#0(2): Manager', $content);
        $this->assertStringNotContainsString('Worker', $content);
    }

    #[Test]
    public function fullyLocalized_allContractsSelected_allContractsLocalized(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsSelectedContractsPlugin/fullyLocalized_allContractsSelected_allContractsLocalized.csv');
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
                fallbackType: 'free',
            ),
        ]);

        $content = $this->renderFrontendPage('https://www.acme.com/de/home');
        $this->assertStringContainsString('<h2>Selected Contracts</h2>', $content);
        $this->assertStringContainsString('#0(3): [DE] Manager', $content);
        $this->assertStringContainsString('#1(1): [DE] Arbeiter', $content);
        $this->assertStringNotContainsString('[EN] Manager', $content);
        $this->assertStringNotContainsString('[EN] Worker', $content);
    }

    /**
     * The selected contracts are fetched via Extbase persistence, which did not honour the site language
     * "fallbackType: strict" for untranslated (child) records before TYPO3 v14.3.6: untranslated selected
     * contracts were returned in their default language instead of being removed. This is a long-standing
     * Extbase regression, fixed in core with change 66694 (14.3 backport 94935, forge #88886), released
     * with TYPO3 v14.3.6 and the main line only (no TYPO3 v13.4 backport). The assertions below describe
     * the corrected v14.3.6+ behaviour, so the test only runs there; the test below states what happens
     * before it, so the plugin is covered on every supported core either way.
     *
     * @see https://review.typo3.org/c/Packages/TYPO3.CMS/+/66694
     * @see https://review.typo3.org/c/Packages/TYPO3.CMS/+/94935
     * @see https://forge.typo3.org/issues/88886
     */
    #[Test]
    public function fullyLocalized_allContractsSelected_notAllContractsLocalized_strictMode(): void
    {
        if (version_compare((new Typo3Version())->getVersion(), '14.3.6', '<')) {
            $this->markTestSkipped(
                'Extbase honours "fallbackType: strict" for untranslated selected contracts only since '
                . 'TYPO3 v14.3.6 (core fix https://review.typo3.org/c/Packages/TYPO3.CMS/+/66694 and its 14.3 '
                . 'backport https://review.typo3.org/c/Packages/TYPO3.CMS/+/94935, forge #88886; not '
                . 'backported to v13.4).'
            );
        }
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsSelectedContractsPlugin/fullyLocalized_allContractsSelected_notAllContractsLocalized.csv');
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
        $this->assertStringContainsString('<h2>Selected Contracts</h2>', $content);
        $this->assertStringContainsString('#0(1): [DE] Arbeiter', $content);
        $this->assertStringNotContainsString('[EN] Manager', $content);
        $this->assertStringNotContainsString('[DE] Manager', $content);
        $this->assertStringNotContainsString('[EN] Worker', $content);
    }

    /**
     * The inverse of the test above: what TYPO3 v13.4 does, and what v14 did up to and including
     * v14.3.5 - the untranslated selected contract is kept and rendered in the default language.
     *
     * @see https://forge.typo3.org/issues/88886
     */
    #[Test]
    public function fullyLocalized_allContractsSelected_notAllContractsLocalized_strictMode_beforeCoreFix(): void
    {
        if (version_compare((new Typo3Version())->getVersion(), '14.3.6', '>=')) {
            $this->markTestSkipped(
                'Core fix for forge #88886 is present, see the test above for the corrected behaviour.'
            );
        }
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsSelectedContractsPlugin/fullyLocalized_allContractsSelected_notAllContractsLocalized.csv');
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
        $this->assertStringContainsString('<h2>Selected Contracts</h2>', $content);
        $this->assertStringContainsString('#0(3): [EN] Manager', $content);
        $this->assertStringContainsString('#1(1): [DE] Arbeiter', $content);
        $this->assertStringNotContainsString('[DE] Manager', $content);
        $this->assertStringNotContainsString('[EN] Worker', $content);
    }

    /**
     * Same core defect as the strict-mode test above, reached over a different route.
     *
     * "free" maps to `OVERLAYS_OFF`, which
     * {@see \FGTCLB\AcademicPersons\Domain\Repository\ContractRepository::findByUids()} lifts to
     * `OVERLAYS_ON_WITH_FLOATING` (ACE-341) - so this exercises the same overlay decision as the
     * strict test above, reached from a different site configuration. Genuine fallback mode is
     * covered separately below and behaves differently: it keeps the untranslated contracts.
     *
     * @see https://review.typo3.org/c/Packages/TYPO3.CMS/+/66694
     * @see https://review.typo3.org/c/Packages/TYPO3.CMS/+/94935
     * @see https://forge.typo3.org/issues/88886
     */
    #[Test]
    public function fullyLocalized_SelectedContracts_notAllContractsLocalized_freeMode(): void
    {
        if (version_compare((new Typo3Version())->getVersion(), '14.3.6', '<')) {
            $this->markTestSkipped(
                'Extbase honours the site language overlay type for untranslated selected contracts only '
                . 'since TYPO3 v14.3.6 (core fix https://review.typo3.org/c/Packages/TYPO3.CMS/+/66694 and '
                . 'its 14.3 backport https://review.typo3.org/c/Packages/TYPO3.CMS/+/94935, forge #88886; '
                . 'not backported to v13.4).'
            );
        }
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsSelectedContractsPlugin/fullyLocalized_allContractsSelected_notAllContractsLocalized.csv');
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
                fallbackType: 'free',
            ),
        ]);

        $content = $this->renderFrontendPage('https://www.acme.com/de/home');
        $this->assertStringContainsString('<h2>Selected Contracts</h2>', $content);
        $this->assertStringContainsString('#0(1): [DE] Arbeiter', $content);
        $this->assertStringNotContainsString('[EN] Manager', $content);
        $this->assertStringNotContainsString('[DE] Manager', $content);
        $this->assertStringNotContainsString('[EN] Worker', $content);
    }

    /**
     * The inverse of the test above: what TYPO3 v13.4 does, and what v14 did up to and including
     * v14.3.5 - the untranslated selected contract is kept and rendered in the default language.
     *
     * @see https://forge.typo3.org/issues/88886
     */
    #[Test]
    public function fullyLocalized_SelectedContracts_notAllContractsLocalized_freeMode_beforeCoreFix(): void
    {
        if (version_compare((new Typo3Version())->getVersion(), '14.3.6', '>=')) {
            $this->markTestSkipped(
                'Core fix for forge #88886 is present, see the test above for the corrected behaviour.'
            );
        }
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsSelectedContractsPlugin/fullyLocalized_allContractsSelected_notAllContractsLocalized.csv');
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
                fallbackType: 'free',
            ),
        ]);

        $content = $this->renderFrontendPage('https://www.acme.com/de/home');
        $this->assertStringContainsString('<h2>Selected Contracts</h2>', $content);
        $this->assertStringContainsString('#0(3): [EN] Manager', $content);
        $this->assertStringContainsString('#1(1): [DE] Arbeiter', $content);
        $this->assertStringNotContainsString('[DE] Manager', $content);
        $this->assertStringNotContainsString('[EN] Worker', $content);
    }

    /**
     * Genuine fallback mode, which no test covered before: "fallback" maps to `OVERLAYS_MIXED`, and
     * unlike `OVERLAYS_OFF` the repositories leave that untouched - {@see \FGTCLB\AcademicPersons\Domain\Repository\ContractRepository::findByUids()}
     * only lifts `OVERLAYS_OFF`. The untranslated selected contract is therefore *kept* and rendered in
     * the default language.
     *
     * This holds before and after the core fix for forge #88886: that change made the overlay honour
     * the requested type, and the requested type here is already `OVERLAYS_MIXED`. So unlike the
     * free-mode tests above, this one needs no core version guard.
     *
     * @see https://forge.typo3.org/issues/88886
     */
    #[Test]
    public function fullyLocalized_SelectedContracts_notAllContractsLocalized_fallbackMode(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsSelectedContractsPlugin/fullyLocalized_allContractsSelected_notAllContractsLocalized.csv');
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
        $this->assertStringContainsString('<h2>Selected Contracts</h2>', $content);
        $this->assertStringContainsString('#0(3): [EN] Manager', $content);
        $this->assertStringContainsString('#1(1): [DE] Arbeiter', $content);
        $this->assertStringNotContainsString('[DE] Manager', $content);
        $this->assertStringNotContainsString('[EN] Worker', $content);
    }
}
