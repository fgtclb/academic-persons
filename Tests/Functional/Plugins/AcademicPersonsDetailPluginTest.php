<?php

declare(strict_types=1);

namespace Fgtclb\AcademicPersons\Tests\Functional\Plugins;

use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\Hook\TypoScriptInstructionModifier;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequestContext;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class AcademicPersonsDetailPluginTest extends FunctionalTestCase
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
    public function defaultLanguageDisplayProfile(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsDetailPlugin/defaultLanguageOnly.csv');
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
                'tx_academicpersons_detail' => [
                    'controller' => 'Profile',
                    'action' => 'detail',
                    'profile' => 1,
                ],
                'cHash' => '13c8ec3ab2a317651a40bd164df8a366',
            ])
        );
        $response = $this->executeFrontendSubRequest($request, $requestContext);
        $this->assertSame(200, $response->getStatusCode());

        $content = (string)$response->getBody();
        $this->assertStringContainsString('#1: [EN] Max Müllermann', $content);
    }

    #[Test]
    public function fullyLocalizedDisplaysLocalizedProfile(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsDetailPlugin/fullyLocalized.csv');
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
                'tx_academicpersons_detail' => [
                    'controller' => 'Profile',
                    'action' => 'detail',
                    'profile' => 1,
                ],
                'cHash' => '008c1ca1df782f9191ecb45d4a4123e3',
            ])
        );
        $response = $this->executeFrontendSubRequest($request, $requestContext);
        $this->assertSame(200, $response->getStatusCode());

        $content = (string)$response->getBody();
        $this->assertStringContainsString('#1: [DE] Max Müllermann', $content);
    }

    #[Test]
    public function localizedPagesAndTtContentWithNotLocalizedProfileDisplayDefaultLanguageWhenLanguageFallback(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsDetailPlugin/localizedPagesAndTtContent_notLocalizedProfile.csv');
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
                'tx_academicpersons_detail' => [
                    'controller' => 'Profile',
                    'action' => 'detail',
                    'profile' => 1,
                ],
                'cHash' => '008c1ca1df782f9191ecb45d4a4123e3',
            ])
        );
        $response = $this->executeFrontendSubRequest($request, $requestContext);
        $this->assertSame(200, $response->getStatusCode());

        $content = (string)$response->getBody();
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
        $this->writeSiteConfiguration(
            'acme',
            $this->buildSiteConfiguration(1, 'https://www.acme.com/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildLanguageConfiguration('DE', '/de/', [], 'strict'),
            ]
        );

        $requestContext = new InternalRequestContext();
        $request = new InternalRequest(
            'https://www.acme.com/de/home?' . http_build_query([
                'tx_academicpersons_detail' => [
                    'controller' => 'Profile',
                    'action' => 'detail',
                    'profile' => 1,
                ],
                'cHash' => '008c1ca1df782f9191ecb45d4a4123e3',
            ])
        );
        $response = $this->executeFrontendSubRequest($request, $requestContext);
        $this->assertSame(200, $response->getStatusCode());

        $content = (string)$response->getBody();
        $this->assertStringContainsString('<h2>Profiledetailpage</h2>', $content);
        $this->assertStringContainsString('#1: [EN] Max Müllermann', $content);
    }
}
