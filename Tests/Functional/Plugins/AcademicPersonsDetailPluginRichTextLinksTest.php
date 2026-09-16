<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Plugins;

use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequestContext;

/**
 * What the profile detail view does with a link stored in one of the five rich
 * text fields of a profile.
 *
 * The frontend editor of `academic_persons_edit` offers a link button, and this
 * branch has no server side sanitiser for the values it stores - the editor
 * writes what was submitted. What protects a visitor is therefore the rendering
 * side alone: `Detail.html` puts every rich text field through
 * `f:format.html()`, whose `parseFunc` decides which protocols survive.
 *
 * That decision is pinned here rather than assumed, for both supported core
 * versions, so that offering the button in the editor cannot quietly become a
 * way to publish a `javascript:` or `data:` URI.
 *
 * `AcademicPersonsDetailPluginTest` renders the same plugin through the
 * template of the `tests/plugin-templates` fixture extension. This test
 * deliberately does not load it, so the shipped `Detail.html` is the one under
 * test - a project sees that file, not the fixture.
 */
final class AcademicPersonsDetailPluginRichTextLinksTest extends AbstractAcademicPersonsTestCase
{
    use SiteBasedTestTrait;

    private const HARMLESS_LINK = '<p><a href="https://example.org">Example</a></p>';
    private const JAVASCRIPT_LINK = '<p><a href="javascript:alert(1)">Click</a></p>';
    private const DATA_LINK = '<p><a href="data:text/html;base64,PHN2Zy9vbmxvYWQ9YWxlcnQoMSk+">Click</a></p>';

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
    ];

    protected function setUp(): void
    {
        $this->coreExtensionsToLoad = array_unique([
            ...array_values($this->coreExtensionsToLoad),
            'typo3/cms-fluid-styled-content',
        ]);
        parent::setUp();
    }

    protected function tearDown(): void
    {
        GeneralUtility::rmdir($this->instancePath . '/typo3conf/sites', true);
        parent::tearDown();
    }

    /**
     * Stores the three links in three of the five rich text fields, so that one
     * rendered page answers for all of them.
     */
    private function setUpDetailPageWithRichTextLinks(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsDetailPlugin/defaultLanguageOnly.csv');
        $this->getConnectionPool()
            ->getConnectionForTable('tx_academicpersons_domain_model_profile')
            ->update(
                'tx_academicpersons_domain_model_profile',
                [
                    'teaching_area' => self::HARMLESS_LINK,
                    'core_competences' => self::JAVASCRIPT_LINK,
                    'supervised_thesis' => self::DATA_LINK,
                ],
                ['uid' => 1],
            );
        $this->setUpFrontendRootPage(
            pageId: 1,
            typoScriptFiles: [
                'constants' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript',
                    'EXT:academic_persons/Configuration/TypoScript/Default/constants.typoscript',
                    'EXT:academic_persons/Tests/Functional/Plugins/Fixtures/TypoScript/Constants/PluginConfiguration.typoscript',
                ],
                'setup' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript',
                    'EXT:academic_persons/Configuration/TypoScript/Default/setup.typoscript',
                    'EXT:academic_persons/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/Rendering.typoscript',
                ],
            ],
        );
        $this->writeSiteConfiguration(
            identifier: 'acme',
            site: $this->buildSiteConfiguration(
                rootPageId: 1,
                base: 'https://www.acme.com/',
            ),
            languages: [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
            ],
        );
    }

    /**
     * The `cHash` is the one `AcademicPersonsDetailPluginTest` uses: it is
     * computed from these query parameters and the encryption key above, both
     * of which are identical here.
     */
    private function renderDetailPage(): string
    {
        $response = $this->executeFrontendSubRequest(
            new InternalRequest(
                'https://www.acme.com/home?' . http_build_query([
                    'tx_academicpersons_detail' => [
                        'controller' => 'Profile',
                        'action' => 'detail',
                        'profile' => 1,
                    ],
                    'cHash' => '13c8ec3ab2a317651a40bd164df8a366',
                ])
            ),
            new InternalRequestContext(),
        );
        $this->assertSame(200, $response->getStatusCode());

        return (string)$response->getBody();
    }

    #[Test]
    public function anHttpsLinkStoredInARichTextFieldIsRendered(): void
    {
        $this->setUpDetailPageWithRichTextLinks();

        $content = $this->renderDetailPage();

        $this->assertStringContainsString('href="https://example.org"', $content);
        $this->assertStringContainsString('Example', $content);
    }

    #[Test]
    public function aJavascriptUriStoredInARichTextFieldIsNotRendered(): void
    {
        $this->setUpDetailPageWithRichTextLinks();

        $content = $this->renderDetailPage();

        $this->assertStringNotContainsString('javascript:', $content);
    }

    #[Test]
    public function aDataUriStoredInARichTextFieldIsNotRendered(): void
    {
        $this->setUpDetailPageWithRichTextLinks();

        $content = $this->renderDetailPage();

        $this->assertStringNotContainsString('href="data:', $content);
    }
}
