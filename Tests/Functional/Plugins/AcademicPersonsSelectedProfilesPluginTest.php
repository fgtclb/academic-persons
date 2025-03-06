<?php

declare(strict_types=1);

namespace Fgtclb\AcademicPersons\Tests\Functional\Plugins;

use Fgtclb\AcademicPersons\Tests\Functional\Fixtures\Trait\SiteBasedTestTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequestContext;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class AcademicPersonsSelectedProfilesPluginTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected array $coreExtensionsToLoad = [
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
                    'FunctionalTest' => \TYPO3\TestingFramework\Core\Functional\Framework\Frontend\Hook\TypoScriptInstructionModifier::class . '->apply',
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

    /**
     * @test
     */
    public function defaultLanguageOnly_allProfilesSelected(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsSelectedProfilesPlugin/defaultLanguageOnly_allProfilesSelected.csv');
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
        static::assertSame(200, $response->getStatusCode());

        $content = (string)$response->getBody();
        static::assertStringContainsString('<h2>Selected Profiles</h2>', $content);
        static::assertStringContainsString('#0(2): Horst Huber', $content);
        static::assertStringContainsString('#1(1): Max Müllermann', $content);
    }

    /**
     * @test
     */
    public function defaultLanguageOnly_oneProfileSelected(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsSelectedProfilesPlugin/defaultLanguageOnly_oneProfileSelected.csv');
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
        static::assertSame(200, $response->getStatusCode());

        $content = (string)$response->getBody();
        static::assertStringContainsString('<h2>Selected Profiles</h2>', $content);
        static::assertStringContainsString('#0(2): Horst Huber', $content);
        static::assertStringNotContainsString('Max Müllermann', $content);
    }

    /**
     * @test
     */
    public function fullyLocalized_allProfilesSelected_allProfilesLocalized(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsSelectedProfilesPlugin/fullyLocalized_allProfilesSelected_allProfilesLocalized.csv');
        $this->setUpFrontendRootPageForTestCase();
        $this->writeSiteConfiguration(
            'acme',
            $this->buildSiteConfiguration(1, 'https://www.acme.com/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                //$this->buildLanguageConfiguration('DE', '/de/'),
                $this->buildLanguageConfiguration('DE', '/de/', ['EN'], 'content_fallback'),
            ]
        );

        $requestContext = new InternalRequestContext();
        $request = new InternalRequest('https://www.acme.com/de/home');
        $response = $this->executeFrontendSubRequest($request, $requestContext);
        static::assertSame(200, $response->getStatusCode());

        $content = (string)$response->getBody();
        static::assertStringContainsString('<h2>Selected Profiles</h2>', $content);
        static::assertStringContainsString('#0(3): [DE] Horst Huber', $content);
        static::assertStringContainsString('#1(1): [DE] Max Müllermann', $content);
        static::assertStringNotContainsString('[EN] Horst Huber', $content);
        static::assertStringNotContainsString('[EN] Max Müllermann', $content);
    }

    /**
     * @test
     */
    public function fullyLocalized_allProfilesSelected_notAllProfilesLocalized_strictMode(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsSelectedProfilesPlugin/fullyLocalized_allProfilesSelected_notAllProfilesLocalized.csv');
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
        static::assertSame(200, $response->getStatusCode());

        $content = (string)$response->getBody();
        static::assertStringContainsString('<h2>Selected Profiles</h2>', $content);
        static::assertStringContainsString('#0(3): [EN] Horst Huber', $content);
        static::assertStringContainsString('#1(1): [DE] Max Müllermann', $content);
        static::assertStringNotContainsString('[DE] Horst Huber', $content);
        static::assertStringNotContainsString('[EN] Max Müllermann', $content);
    }

    /**
     * @test
     */
    public function fullyLocalized_selectedProfiles_notAllProfilesLocalized_fallbackMode(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsSelectedProfilesPlugin/fullyLocalized_allProfilesSelected_notAllProfilesLocalized.csv');
        $this->setUpFrontendRootPageForTestCase();
        $this->writeSiteConfiguration(
            'acme',
            $this->buildSiteConfiguration(1, 'https://www.acme.com/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildLanguageConfiguration('DE', '/de/', ['EN'], 'content_fallback'),
            ]
        );

        $requestContext = new InternalRequestContext();
        $request = new InternalRequest('https://www.acme.com/de/home');
        $response = $this->executeFrontendSubRequest($request, $requestContext);
        static::assertSame(200, $response->getStatusCode());

        $content = (string)$response->getBody();
        static::assertStringContainsString('<h2>Selected Profiles</h2>', $content);
        static::assertStringContainsString('#0(3): [EN] Horst Huber', $content);
        static::assertStringContainsString('#1(1): [DE] Max Müllermann', $content);
        static::assertStringNotContainsString('[DE] Horst Huber', $content);
        static::assertStringNotContainsString('[EN] Max Müllermann', $content);
    }
}
