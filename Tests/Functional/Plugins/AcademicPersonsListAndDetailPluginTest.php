<?php

declare(strict_types=1);

namespace Fgtclb\AcademicPersons\Tests\Functional\Plugins;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\Hook\TypoScriptInstructionModifier;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequestContext;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class AcademicPersonsListAndDetailPluginTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected array $coreExtensionsToLoad = [
        'typo3/cms-rte-ckeditor',
        'typo3/cms-fluid-styled-content',
    ];

    protected array $testExtensionsToLoad = [
        'georgringer/numbered-pagination',
        'fgtclb/academic-persons',
        'tests/plugin-templates',
    ];

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
        'SC_OPTIONS' => [
            'Core/TypoScript/TemplateService' => [
                'runThroughTemplatesPostProcessing' => [
                    'FunctionalTest' => TypoScriptInstructionModifier::class . '->apply',
                ],
            ],
        ],
    ];

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
        'DE' => ['id' => 1, 'title' => 'Deutsch', 'locale' => 'de_DE.UTF8', 'iso' => 'de', 'hrefLang' => 'de-DE', 'direction' => ''],
        'FR' => ['id' => 2, 'title' => 'French', 'locale' => 'fr_FR.UTF8', 'iso' => 'fr', 'hrefLang' => 'fr-FR', 'direction' => ''],
    ];

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        GeneralUtility::rmdir($this->instancePath . '/typo3conf/sites', true);
        parent::tearDown();
    }

    private function setUpFrontendRootPageForTestCase(): void
    {
        $this->setUpFrontendRootPage(
            1,
            [
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
            ]
        );
    }

    #[Test]
    public function defaultLanguageListDisplaysAllProfiles(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsListAndDetailPlugin/defaultLanguageOnly.csv');
        $this->setUpFrontendRootPageForTestCase();
        $this->writeSiteConfiguration(
            'acme',
            $this->buildSiteConfiguration(1, 'https://www.acme.com/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
            ]
        );

        $requestContext = new InternalRequestContext();
        $request = new InternalRequest('https://www.acme.com/home');
        $response = $this->executeFrontendSubRequest($request, $requestContext);
        $this->assertSame(200, $response->getStatusCode());

        $content = (string)$response->getBody();
        $this->assertStringContainsString('<h2>Profilelist</h2>', $content);
        $this->assertStringContainsString('#0(1): [EN] Max Müllermann', $content);
        $this->assertStringContainsString('#1(2): [EN] Horst Huber', $content);
    }

    #[Test]
    public function defaultLanguageDisplayProfile(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsListAndDetailPlugin/defaultLanguageOnly.csv');
        $this->setUpFrontendRootPageForTestCase();
        $this->writeSiteConfiguration(
            'acme',
            $this->buildSiteConfiguration(1, 'https://www.acme.com/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
            ]
        );

        $requestContext = new InternalRequestContext();
        $request = new InternalRequest(
            'https://www.acme.com/home?' . http_build_query([
                'tx_academicpersons_listanddetail' => [
                    'controller' => 'Profile',
                    'action' => 'detail',
                    'profile' => 1,
                ],
                'cHash' => 'fee6f7f7bcd9035d23f9d6062fc6b7c5',
            ])
        );
        $response = $this->executeFrontendSubRequest($request, $requestContext);
        $this->assertSame(200, $response->getStatusCode());

        $content = (string)$response->getBody();
        $this->assertStringContainsString('<h2>Profiledetailpage</h2>', $content);
        $this->assertStringContainsString('#1: [EN] Max Müllermann', $content);
    }

    #[Test]
    public function defaultLanguageListDisplaySingleSelectedProfile(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsListAndDetailPlugin/defaultLanguageOnly_oneProfileSelected.csv');
        $this->setUpFrontendRootPageForTestCase();
        $this->writeSiteConfiguration(
            'acme',
            $this->buildSiteConfiguration(1, 'https://www.acme.com/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
            ]
        );

        $requestContext = new InternalRequestContext();
        $request = new InternalRequest('https://www.acme.com/home');
        $response = $this->executeFrontendSubRequest($request, $requestContext);
        $this->assertSame(200, $response->getStatusCode());

        $content = (string)$response->getBody();
        $this->assertStringContainsString('<h2>Profilelist</h2>', $content);
        $this->assertStringContainsString('#0(2): Horst Huber', $content);
        $this->assertStringNotContainsString('Max Müllermann', $content);
    }

    #[Test]
    public function defaultLanguageListDisplaysSelectedProfilesInSelectedOrder(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsListAndDetailPlugin/defaultLanguageOnly_selectedProfiles.csv');
        $this->setUpFrontendRootPageForTestCase();
        $this->writeSiteConfiguration(
            'acme',
            $this->buildSiteConfiguration(1, 'https://www.acme.com/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
            ]
        );

        $requestContext = new InternalRequestContext();
        $request = new InternalRequest('https://www.acme.com/home');
        $response = $this->executeFrontendSubRequest($request, $requestContext);
        $this->assertSame(200, $response->getStatusCode());

        $content = (string)$response->getBody();
        $this->assertStringContainsString('<h2>Profilelist</h2>', $content);
        $this->assertStringContainsString('#0(2): Horst Huber', $content);
        $this->assertStringContainsString('#1(1): Max Müllermann', $content);
    }

    #[Test]
    public function fullyLocalizedDisplaysLocalizedProfile(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsListAndDetailPlugin/fullyLocalized.csv');
        $this->setUpFrontendRootPageForTestCase();
        $this->writeSiteConfiguration(
            'acme',
            $this->buildSiteConfiguration(1, 'https://www.acme.com/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildDefaultLanguageConfiguration('DE', '/de/'),
            ]
        );

        $requestContext = new InternalRequestContext();
        $request = new InternalRequest(
            'https://www.acme.com/de/home?' . http_build_query([
                'tx_academicpersons_listanddetail' => [
                    'controller' => 'Profile',
                    'action' => 'detail',
                    'profile' => 1,
                ],
                'cHash' => 'f497a1175d10a3c77454c5958cc1fc5c',
            ])
        );
        $response = $this->executeFrontendSubRequest($request, $requestContext);
        $this->assertSame(200, $response->getStatusCode());

        $content = (string)$response->getBody();
        $this->assertStringContainsString('<h2>Profiledetailpage</h2>', $content);
        $this->assertStringContainsString('#1: [DE] Max Müllermann', $content);
    }

    #[Test]
    public function localizedPagesAndTtContentWithNotLocalizedProfileDisplayDefaultLanguageWhenLanguageFallback(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsListAndDetailPlugin/localizedPagesAndTtContent_notLocalizedProfile.csv');
        $this->setUpFrontendRootPageForTestCase();
        $this->writeSiteConfiguration(
            'acme',
            $this->buildSiteConfiguration(1, 'https://www.acme.com/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildLanguageConfiguration('DE', '/de/', ['EN'], 'fallback'),
            ]
        );

        $requestContext = new InternalRequestContext();
        $request = new InternalRequest(
            'https://www.acme.com/de/home?' . http_build_query([
                'tx_academicpersons_listanddetail' => [
                    'controller' => 'Profile',
                    'action' => 'detail',
                    'profile' => 1,
                ],
                'cHash' => 'f497a1175d10a3c77454c5958cc1fc5c',
            ])
        );
        $response = $this->executeFrontendSubRequest($request, $requestContext);
        $this->assertSame(200, $response->getStatusCode());

        $content = (string)$response->getBody();
        $this->assertStringContainsString('<h2>Profiledetailpage</h2>', $content);
        $this->assertStringContainsString('#1: [EN] Max Müllermann', $content);
    }

    #[Test]
    public function fullyLocalizedListDisplaysDefaultLanguageProfilesForRequestedDefaultLanguage(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsListAndDetailPlugin/fullyLocalized.csv');
        $this->setUpFrontendRootPageForTestCase();
        $this->writeSiteConfiguration(
            'acme',
            $this->buildSiteConfiguration(1, 'https://www.acme.com/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildLanguageConfiguration('DE', '/de/'),
            ]
        );

        $requestContext = new InternalRequestContext();
        $request = new InternalRequest('https://www.acme.com/home');
        $response = $this->executeFrontendSubRequest($request, $requestContext);
        $this->assertSame(200, $response->getStatusCode());

        $content = (string)$response->getBody();
        $this->assertStringContainsString('<h2>Profilelist</h2>', $content);
        $this->assertStringContainsString('#0(1): [EN] Max Müllermann', $content);
        $this->assertStringContainsString('#1(3): [EN] Horst Huber', $content);
    }

    #[Test]
    public function fullyLocalizedListDisplaysLocalizedProfilesForRequestedLanguage(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsListAndDetailPlugin/fullyLocalized.csv');
        $this->setUpFrontendRootPageForTestCase();
        $this->writeSiteConfiguration(
            'acme',
            $this->buildSiteConfiguration(1, 'https://www.acme.com/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildLanguageConfiguration('DE', '/de/'),
            ]
        );

        $requestContext = new InternalRequestContext();
        $request = new InternalRequest('https://www.acme.com/de/home');
        $response = $this->executeFrontendSubRequest($request, $requestContext);
        $this->assertSame(200, $response->getStatusCode());

        $content = (string)$response->getBody();
        $this->assertStringContainsString('<h2>Profilelist</h2>', $content);
        $this->assertStringContainsString('#0(1): [DE] Max Müllermann', $content);
        $this->assertStringContainsString('#1(3): [DE] Horst Huber', $content);
    }

    #[Test]
    public function fullyLocalizedPagesAndTtContentListDisplaysOnlyLocalizedProfilesForRequestedLanguageWithNotAllProfilesLocalizedInStrictMode(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsListAndDetailPlugin/fullyLocalizedPagesAndTtContent_notAllProfilesLocalized.csv');
        $this->setUpFrontendRootPageForTestCase();
        $this->writeSiteConfiguration(
            'acme',
            $this->buildSiteConfiguration(1, 'https://www.acme.com/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildLanguageConfiguration('DE', '/de/', [], 'strict'),
            ]
        );

        $requestContext = new InternalRequestContext();
        $request = new InternalRequest('https://www.acme.com/de/home');
        $response = $this->executeFrontendSubRequest($request, $requestContext);
        $this->assertSame(200, $response->getStatusCode());

        $content = (string)$response->getBody();
        $this->assertStringContainsString('<h2>Profilelist</h2>', $content);
        $this->assertStringContainsString('#0(1): [DE] Max Müllermann', $content);
        $this->assertStringNotContainsString('Horst Huber', $content);
    }

    #[Test]
    public function fullyLocalizedPagesAndTtContentListDisplaysOnlyLocalizedProfilesForRequestedLanguageWithNotAllProfilesLocalizedInStrictModeWithFallbackForNonTranslatedSet(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsListAndDetailPlugin/fullyLocalizedPagesAndTtContent_notAllProfilesLocalized_fallbackForNonTranslatedSet.csv');
        $this->setUpFrontendRootPageForTestCase();
        $this->writeSiteConfiguration(
            'acme',
            $this->buildSiteConfiguration(1, 'https://www.acme.com/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildLanguageConfiguration('DE', '/de/', [], 'strict'),
            ]
        );

        $requestContext = new InternalRequestContext();
        $request = new InternalRequest('https://www.acme.com/de/home');
        $response = $this->executeFrontendSubRequest($request, $requestContext);
        $this->assertSame(200, $response->getStatusCode());

        $content = (string)$response->getBody();
        $this->assertStringContainsString('<h2>Profilelist</h2>', $content);
        $this->assertStringContainsString('#0(1): [DE] Max Müllermann', $content);
        $this->assertStringContainsString('#1(3): [EN] Horst Huber', $content);
    }

    #[Test]
    public function fullyLocalizedPagesAndTtContentListDisplaysLocalizedProfileAndDefaultLanguageProfileForRequestedLanguageWithNotAllProfilesLocalizedInFallbackMode(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsListAndDetailPlugin/fullyLocalizedPagesAndTtContent_notAllProfilesLocalized.csv');
        $this->setUpFrontendRootPageForTestCase();
        $this->writeSiteConfiguration(
            'acme',
            $this->buildSiteConfiguration(1, 'https://www.acme.com/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildLanguageConfiguration('DE', '/de/', ['EN'], 'fallback'),
            ]
        );

        $requestContext = new InternalRequestContext();
        $request = new InternalRequest('https://www.acme.com/de/home');
        $response = $this->executeFrontendSubRequest($request, $requestContext);
        $this->assertSame(200, $response->getStatusCode());

        $content = (string)$response->getBody();
        $this->assertStringContainsString('<h2>Profilelist</h2>', $content);
        $this->assertStringContainsString('#0(1): [DE] Max Müllermann', $content);
        $this->assertStringContainsString('#1(3): [EN] Horst Huber', $content);
    }

    #[Test]
    public function fullyLocalizedListDisplaysLocalizedSelectedProfilesForRequestedLanguageInSelectedOrder(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsListAndDetailPlugin/fullyLocalized_selectedProfiles.csv');
        $this->setUpFrontendRootPageForTestCase();
        $this->writeSiteConfiguration(
            'acme',
            $this->buildSiteConfiguration(1, 'https://www.acme.com/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildLanguageConfiguration('DE', '/de/'),
            ]
        );

        $requestContext = new InternalRequestContext();
        $request = new InternalRequest('https://www.acme.com/de/home');
        $response = $this->executeFrontendSubRequest($request, $requestContext);
        $this->assertSame(200, $response->getStatusCode());

        $content = (string)$response->getBody();
        $this->assertStringContainsString('<h2>Profilelist</h2>', $content);
        $this->assertStringContainsString('#0(3): [DE] Horst Huber', $content);
        $this->assertStringContainsString('#1(1): [DE] Max Müllermann', $content);
        $this->assertStringNotContainsString('[EN] Horst Huber', $content);
        $this->assertStringNotContainsString('[EN] Max Müllermann', $content);
    }

    /**
     * @todo Investgate change TYPO3 core/extbase behaviour since v12 in core and either fix implementation or adjust
     *       test for v12 when enabling it again.
     */
    #[Test]
    public function fullyLocalizedListDisplaysLocalizedSelectedProfilesForRequestedLanguageInSelectedOrderWithFallbackTypeStrictWhenNotAllProfilesAreLocalized(): void
    {
        if ((new Typo3Version())->getMajorVersion() >= 12) {
            $this->markTestSkipped('Different behaviour since TYPO3 v12 - needs investigation in core first if this was intended.');
        }
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsListAndDetailPlugin/fullyLocalized_selectedProfiles_notAllProfilesLocalized.csv');
        $this->setUpFrontendRootPageForTestCase();
        $this->writeSiteConfiguration(
            'acme',
            $this->buildSiteConfiguration(1, 'https://www.acme.com/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildLanguageConfiguration('DE', '/de/', [], 'strict'),
            ]
        );

        $requestContext = new InternalRequestContext();
        $request = new InternalRequest('https://www.acme.com/de/home');
        $response = $this->executeFrontendSubRequest($request, $requestContext);
        $this->assertSame(200, $response->getStatusCode());

        $content = (string)$response->getBody();
        $this->assertStringContainsString('<h2>Profilelist</h2>', $content);
        $this->assertStringContainsString('#0(1): [DE] Max Müllermann', $content);
        $this->assertStringNotContainsString('[EN] Horst Huber', $content);
        $this->assertStringNotContainsString('[DE] Horst Huber', $content);
        $this->assertStringNotContainsString('[EN] Max Müllermann', $content);
    }

    #[Test]
    public function fullyLocalizedListDisplaysLocalizedSelectedProfilesForRequestedLanguageInSelectedOrderWithFallbackTypeStrictWhenNotAllProfilesAreLocalizedButPluginFallbackSet(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsListAndDetailPlugin/fullyLocalized_selectedProfiles_notAllProfilesLocalized_fallbackForNonTranslatedSet.csv');
        $this->setUpFrontendRootPageForTestCase();
        $this->writeSiteConfiguration(
            'acme',
            $this->buildSiteConfiguration(1, 'https://www.acme.com/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildLanguageConfiguration('DE', '/de/'),
            ]
        );

        $requestContext = new InternalRequestContext();
        $request = new InternalRequest('https://www.acme.com/de/home');
        $response = $this->executeFrontendSubRequest($request, $requestContext);
        $this->assertSame(200, $response->getStatusCode());

        $content = (string)$response->getBody();
        $this->assertStringContainsString('<h2>Profilelist</h2>', $content);
        $this->assertStringContainsString('#0(3): [EN] Horst Huber', $content);
        $this->assertStringContainsString('#1(1): [DE] Max Müllermann', $content);
        $this->assertStringNotContainsString('[DE] Horst Huber', $content);
        $this->assertStringNotContainsString('[EN] Max Müllermann', $content);
    }

    #[Test]
    public function fullyLocalizedListDisplaysLocalizedSelectedProfilesForRequestedLanguageInSelectedOrderWithFallbackTypeFallbackWhenNotAllProfilesAreLocalized(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsListAndDetailPlugin/fullyLocalized_selectedProfiles_notAllProfilesLocalized.csv');
        $this->setUpFrontendRootPageForTestCase();
        $this->writeSiteConfiguration(
            'acme',
            $this->buildSiteConfiguration(1, 'https://www.acme.com/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildLanguageConfiguration('DE', '/de/', ['EN'], 'fallback'),
            ]
        );

        $requestContext = new InternalRequestContext();
        $request = new InternalRequest('https://www.acme.com/de/home');
        $response = $this->executeFrontendSubRequest($request, $requestContext);
        $this->assertSame(200, $response->getStatusCode());

        $content = (string)$response->getBody();
        $this->assertStringContainsString('<h2>Profilelist</h2>', $content);
        $this->assertStringContainsString('#0(3): [EN] Horst Huber', $content);
        $this->assertStringContainsString('#1(1): [DE] Max Müllermann', $content);
        $this->assertStringNotContainsString('[DE] Horst Huber', $content);
        $this->assertStringNotContainsString('[EN] Max Müllermann', $content);
    }
}
