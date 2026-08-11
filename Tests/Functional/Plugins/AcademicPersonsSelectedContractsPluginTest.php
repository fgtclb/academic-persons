<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Plugins;

use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequestContext;

final class AcademicPersonsSelectedContractsPluginTest extends AbstractAcademicPersonsTestCase
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
        'DE' => ['id' => 1, 'title' => 'Deutsch', 'locale' => 'de_DE.UTF8', 'iso' => 'de', 'hrefLang' => 'de-DE', 'direction' => ''],
        'FR' => ['id' => 2, 'title' => 'French', 'locale' => 'fr_FR.UTF8', 'iso' => 'fr', 'hrefLang' => 'fr-FR', 'direction' => ''],
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
            ]),
        ]);
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

        $requestContext = new InternalRequestContext();
        $request = new InternalRequest('https://www.acme.com/home');
        $response = $this->executeFrontendSubRequest($request, $requestContext);
        $this->assertSame(200, $response->getStatusCode());

        $content = (string)$response->getBody();
        $this->assertStringContainsString('<h2>Selected Contracts</h2>', $content);
        $this->assertStringContainsString('#0(2): Manager', $content);
        $this->assertStringContainsString('#1(1): Worker', $content);
    }

    #[Test]
    public function defaultLanguageOnly_oneContractSelected(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsSelectedContractsPlugin/defaultLanguageOnly_oneContractSelected.csv');
        $this->setUpFrontendRootPageForTestCase();
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

        $requestContext = new InternalRequestContext();
        $request = new InternalRequest('https://www.acme.com/home');
        $response = $this->executeFrontendSubRequest($request, $requestContext);
        $this->assertSame(200, $response->getStatusCode());

        $content = (string)$response->getBody();
        $this->assertStringContainsString('<h2>Selected Contracts</h2>', $content);
        $this->assertStringContainsString('#0(2): Manager', $content);
        $this->assertStringNotContainsString('Worker', $content);
    }

    #[Test]
    public function fullyLocalized_allContractsSelected_allContractsLocalized(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsSelectedContractsPlugin/fullyLocalized_allContractsSelected_allContractsLocalized.csv');
        $this->setUpFrontendRootPageForTestCase();
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
                $this->buildLanguageConfiguration(
                    identifier: 'DE',
                    base: '/de/',
                    fallbackIdentifiers: ['EN'],
                    fallbackType: 'free',
                ),
            ],
        );

        $requestContext = new InternalRequestContext();
        $request = new InternalRequest('https://www.acme.com/de/home');
        $response = $this->executeFrontendSubRequest($request, $requestContext);
        $this->assertSame(200, $response->getStatusCode());

        $content = (string)$response->getBody();
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
                $this->buildLanguageConfiguration(
                    identifier: 'DE',
                    base: '/de/',
                    fallbackIdentifiers: [],
                    fallbackType: 'strict',
                ),
            ],
        );

        $requestContext = new InternalRequestContext();
        $request = new InternalRequest('https://www.acme.com/de/home');
        $response = $this->executeFrontendSubRequest($request, $requestContext);
        $this->assertSame(200, $response->getStatusCode());

        $content = (string)$response->getBody();
        $this->assertStringContainsString('<h2>Selected Contracts</h2>', $content);
        $this->assertStringContainsString('#0(3): [EN] Manager', $content);
        $this->assertStringContainsString('#1(1): [DE] Arbeiter', $content);
        $this->assertStringNotContainsString('[DE] Manager', $content);
        $this->assertStringNotContainsString('[EN] Worker', $content);
    }

    /**
     * "free" maps to `OVERLAYS_OFF`, which
     * {@see \FGTCLB\AcademicPersons\Domain\Repository\ContractRepository::findByUids()} lifts to
     * `OVERLAYS_ON_WITH_FLOATING` (ACE-341), so this exercises the same overlay decision as the
     * strict test above, reached from a different site configuration.
     *
     * On TYPO3 v12 and v13 the rendered result is the same either way: Extbase persistence does not
     * honour the requested overlay type for untranslated selected records there (forge #88886, fixed
     * in v14.3.6 only, never backported to v13.4). The paths differ, the outcome coincides - do not
     * collapse these tests into one.
     *
     * @see https://forge.typo3.org/issues/88886
     */
    #[Test]
    public function fullyLocalized_SelectedContracts_notAllContractsLocalized_freeMode(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsSelectedContractsPlugin/fullyLocalized_allContractsSelected_notAllContractsLocalized.csv');
        $this->setUpFrontendRootPageForTestCase();
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
                $this->buildLanguageConfiguration(
                    identifier: 'DE',
                    base: '/de/',
                    fallbackIdentifiers: ['EN'],
                    fallbackType: 'free',
                ),
            ],
        );

        $requestContext = new InternalRequestContext();
        $request = new InternalRequest('https://www.acme.com/de/home');
        $response = $this->executeFrontendSubRequest($request, $requestContext);
        $this->assertSame(200, $response->getStatusCode());

        $content = (string)$response->getBody();
        $this->assertStringContainsString('<h2>Selected Contracts</h2>', $content);
        $this->assertStringContainsString('#0(3): [EN] Manager', $content);
        $this->assertStringContainsString('#1(1): [DE] Arbeiter', $content);
        $this->assertStringNotContainsString('[DE] Manager', $content);
        $this->assertStringNotContainsString('[EN] Worker', $content);
    }

    /**
     * Genuine fallback mode, which no test covered before: "fallback" maps to `OVERLAYS_MIXED`, and
     * unlike `OVERLAYS_OFF` the repositories leave that untouched - {@see \FGTCLB\AcademicPersons\Domain\Repository\ContractRepository::findByUids()}
     * only lifts `OVERLAYS_OFF`. The untranslated selected contract is kept and rendered in the
     * default language.
     *
     * On this branch that is the same output as the free- and strict-mode tests above, because no
     * TYPO3 version it supports honours the requested overlay type for untranslated selected records
     * (forge #88886 landed in v14.3.6 and was never backported to v13.4). The query path is a
     * different one all the same, and on the 3.x line the three tests diverge.
     *
     * @see https://forge.typo3.org/issues/88886
     */
    #[Test]
    public function fullyLocalized_SelectedContracts_notAllContractsLocalized_fallbackMode(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsSelectedContractsPlugin/fullyLocalized_allContractsSelected_notAllContractsLocalized.csv');
        $this->setUpFrontendRootPageForTestCase();
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
                $this->buildLanguageConfiguration(
                    identifier: 'DE',
                    base: '/de/',
                    fallbackIdentifiers: ['EN'],
                    fallbackType: 'fallback',
                ),
            ],
        );

        $requestContext = new InternalRequestContext();
        $request = new InternalRequest('https://www.acme.com/de/home');
        $response = $this->executeFrontendSubRequest($request, $requestContext);
        $this->assertSame(200, $response->getStatusCode());

        $content = (string)$response->getBody();
        $this->assertStringContainsString('<h2>Selected Contracts</h2>', $content);
        $this->assertStringContainsString('#0(3): [EN] Manager', $content);
        $this->assertStringContainsString('#1(1): [DE] Arbeiter', $content);
        $this->assertStringNotContainsString('[DE] Manager', $content);
        $this->assertStringNotContainsString('[EN] Worker', $content);
    }
}
