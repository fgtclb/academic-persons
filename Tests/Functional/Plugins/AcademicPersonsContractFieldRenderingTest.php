<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Plugins;

use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;

/**
 * Renders `Profile/Contract/Field` through the selected contracts plugin, the second of the
 * two plugins that reach the partial with the shipped templates.
 *
 * {@see AcademicPersonsSelectedContractsPluginTest} covers the same plugin with the
 * simplified templates of `EXT:test_plugin_templates`, which carry no contract fields at
 * all - so this class exists rather than a test method there.
 */
final class AcademicPersonsContractFieldRenderingTest extends AbstractAcademicPersonsTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration();
        $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
        parent::setUp();
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    /**
     * @param list<string> $additionalConstantFiles Constants loaded after the shipped ones.
     */
    private function setUpTestCase(string $dataSet, array $additionalConstantFiles = []): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsContractFieldRendering/' . $dataSet . '.csv');
        $this->setUpFrontendRootPage(
            pageId: 1,
            typoScriptFiles: [
                'constants' => array_merge(
                    [
                        'EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript',
                        'EXT:academic_persons/Configuration/TypoScript/Default/constants.typoscript',
                    ],
                    $additionalConstantFiles,
                ),
                'setup' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript',
                    'EXT:academic_persons/Configuration/TypoScript/Default/setup.typoscript',
                    'EXT:academic_persons/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/Rendering.typoscript',
                ],
            ],
        );
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration(
                identifier: 'EN',
                base: '/',
            ),
        ]);
    }

    private function renderHomePage(): string
    {
        return $this->renderFrontendPage('https://www.acme.com/home');
    }

    #[Test]
    public function selectedContractsPluginRendersTheOrganisationalUnitOfAContract(): void
    {
        $this->setUpTestCase('selectedContractsPage');

        $content = $this->renderHomePage();
        $this->assertStringContainsString('Organisational Unit', $content);
        $this->assertStringContainsString('Institute of Applied Physics', $content);
    }

    #[Test]
    public function selectedContractsPluginRendersADialablePhoneNumberTarget(): void
    {
        $this->setUpTestCase('selectedContractsPage');

        $content = $this->renderHomePage();
        $this->assertStringContainsString('href="tel:+496241509123"', $content);
        $this->assertStringContainsString('>+49 6241 509 123</a>', $content);
    }

    /**
     * The prefix is what makes a stored extension dialable. It reaches the link target only;
     * the visible text stays the number an editor entered.
     */
    #[Test]
    public function configuredPrefixReachesTheLinkTargetAndNotTheLinkText(): void
    {
        $this->setUpTestCase(
            'selectedContractsPage_extensionNumber',
            ['EXT:academic_persons/Tests/Functional/Plugins/Fixtures/TypoScript/Constants/PhoneLinkPrefix.typoscript'],
        );

        $content = $this->renderHomePage();
        $this->assertStringContainsString('href="tel:+496241509123"', $content);
        $this->assertStringContainsString('>123</a>', $content);
    }

    /**
     * Without the setting the target is the stored number without its spaces - the behaviour
     * the two tests above pin, asserted here for the number the prefix test stores.
     */
    #[Test]
    public function withoutAPrefixTheTargetIsTheStoredNumber(): void
    {
        $this->setUpTestCase('selectedContractsPage_extensionNumber');

        $this->assertStringContainsString('href="tel:123"', $this->renderHomePage());
    }
}
