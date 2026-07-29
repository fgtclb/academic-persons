<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Plugins;

use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;

final class AcademicPersonsDetailPluginTest extends AbstractAcademicPersonsTestCase
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
    public function defaultLanguageDisplayProfile(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsDetailPlugin/defaultLanguageOnly.csv');
        $this->setUpFrontendRootPageForTestCase();
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration('EN', '/'),
        ]);

        $content = $this->renderFrontendPage(
            'https://www.acme.com/home?' . http_build_query([
                'tx_academicpersons_detail' => [
                    'controller' => 'Profile',
                    'action' => 'detail',
                    'profile' => 1,
                ],
                'cHash' => '13c8ec3ab2a317651a40bd164df8a366',
            ])
        );
        $this->assertStringContainsString('#1: [EN] Max Müllermann', $content);
    }

    #[Test]
    public function fullyLocalizedDisplaysLocalizedProfile(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsDetailPlugin/fullyLocalized.csv');
        $this->setUpFrontendRootPageForTestCase();
        $this->writeSiteConfiguration(
            identifier: 'acme',
            site: $this->buildSiteConfiguration(1, 'https://www.acme.com/'),
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

        $content = $this->renderFrontendPage(
            'https://www.acme.com/de/home?' . http_build_query([
                'tx_academicpersons_detail' => [
                    'controller' => 'Profile',
                    'action' => 'detail',
                    'profile' => 1,
                ],
                'cHash' => '008c1ca1df782f9191ecb45d4a4123e3',
            ])
        );
        $this->assertStringContainsString('#1: [DE] Max Müllermann', $content);
    }

    #[Test]
    public function localizedPagesAndTtContentWithNotLocalizedProfileDisplayDefaultLanguageWhenLanguageFallback(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsDetailPlugin/localizedPagesAndTtContent_notLocalizedProfile.csv');
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

        $content = $this->renderFrontendPage(
            'https://www.acme.com/de/home?' . http_build_query([
                'tx_academicpersons_detail' => [
                    'controller' => 'Profile',
                    'action' => 'detail',
                    'profile' => 1,
                ],
                'cHash' => '008c1ca1df782f9191ecb45d4a4123e3',
            ])
        );
        $this->assertStringContainsString('<h2>Profiledetailpage</h2>', $content);
        $this->assertStringContainsString('#1: [EN] Max Müllermann', $content);
    }

    /**
     * @todo Really ?
     */
    #[Test]
    public function localizedPagesAndTtContentWithNotLocalizedProfileDisplayDefaultLanguageWhenLanguageStrict(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsDetailPlugin/localizedPagesAndTtContent_notLocalizedProfile.csv');
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

        $content = $this->renderFrontendPage(
            'https://www.acme.com/de/home?' . http_build_query([
                'tx_academicpersons_detail' => [
                    'controller' => 'Profile',
                    'action' => 'detail',
                    'profile' => 1,
                ],
                'cHash' => '008c1ca1df782f9191ecb45d4a4123e3',
            ])
        );
        $this->assertStringContainsString('<h2>Profiledetailpage</h2>', $content);
        $this->assertStringContainsString('#1: [EN] Max Müllermann', $content);
    }
}
