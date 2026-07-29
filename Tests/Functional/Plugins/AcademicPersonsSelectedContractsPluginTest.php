<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Plugins;

use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;

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
                fallbackType: 'content_fallback',
            ),
        ]);

        $content = $this->renderFrontendPage('https://www.acme.com/de/home');
        $this->assertStringContainsString('<h2>Selected Contracts</h2>', $content);
        $this->assertStringContainsString('#0(3): [DE] Manager', $content);
        $this->assertStringContainsString('#1(1): [DE] Arbeiter', $content);
        $this->assertStringNotContainsString('[EN] Manager', $content);
        $this->assertStringNotContainsString('[EN] Worker', $content);
    }

    #[Test]
    public function fullyLocalized_allContractsSelected_notAllContractsLocalized_strictMode(): void
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
                fallbackType: 'content_fallback',
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
