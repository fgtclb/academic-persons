<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Plugins;

use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Site\Set\SetRegistry;

/**
 * The switch `plugin.tx_academicpersons.assets.js`, which decides whether the public
 * profile loads its script: the fold-out entries, the sticky navigation and the scroll
 * spy.
 *
 * It exists twice under one name, as a site setting of the aggregate set and as a
 * TypoScript constant for a site configured through `sys_template` records, so both
 * delivery mechanisms are covered here.
 */
final class AcademicPersonsDetailScriptSettingTest extends AbstractAcademicPersonsTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    private const AGGREGATE_SET = 'fgtclb/academic-persons';
    private const SETTING = 'plugin.tx_academicpersons.assets.js';

    /**
     * The bare specifier of the module. It reaches the page in the statement that imports
     * it, and nowhere when no module was registered.
     */
    private const MODULE = '@fgtclb/academic-persons/frontend/profile.js';

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration();
        $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsPublicProfilePlugin/shippedLayout.csv');
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    #[Test]
    public function theAggregateSetDeclaresTheSettingSwitchedOn(): void
    {
        $definitions = [];
        foreach ($this->get(SetRegistry::class)->getSet(self::AGGREGATE_SET)?->settingsDefinitions ?? [] as $definition) {
            $definitions[$definition->key] = $definition;
        }

        $this->assertArrayHasKey(self::SETTING, $definitions);
        $this->assertSame('bool', $definitions[self::SETTING]->type);
        $this->assertTrue($definitions[self::SETTING]->default);
    }

    #[Test]
    #[DataProvider('deliveryMechanisms')]
    public function theProfileLoadsItsScriptWhenTheSiteConfiguresNothing(bool $siteSet): void
    {
        $siteSet ? $this->setUpSiteSetSite() : $this->setUpStaticTemplateSite();

        $this->assertStringContainsString(self::MODULE, $this->renderProfile());
    }

    public static function deliveryMechanisms(): \Generator
    {
        yield 'site set' => [true];
        yield 'static template' => [false];
    }

    #[Test]
    public function theSiteSettingSwitchesTheScriptOff(): void
    {
        $this->setUpSiteSetSite([self::SETTING => false]);

        $this->assertProfileWithoutScript($this->renderProfile());
    }

    #[Test]
    public function theTypoScriptConstantSwitchesTheScriptOff(): void
    {
        $this->setUpStaticTemplateSite(['EXT:academic_persons/Tests/Functional/Plugins/Fixtures/TypoScript/Constants/NoScript.typoscript']);

        $this->assertProfileWithoutScript($this->renderProfile());
    }

    /**
     * No module, and the markup a script of the site addresses unchanged: the root it
     * starts from, and the fold-out entries, still folded.
     */
    private function assertProfileWithoutScript(string $content): void
    {
        $this->assertStringNotContainsString(self::MODULE, $content);
        $this->assertStringNotContainsString('frontend/profile.js', $content);
        $this->assertStringContainsString('data-academic-persons-detail', $content);
        $this->assertStringContainsString('data-academic-persons-accordion-trigger="true"', $content);
        $this->assertStringContainsString('Chairs the faculty council.', $content);
    }

    private function renderProfile(): string
    {
        return $this->renderFrontendPage(
            'https://www.acme.com/home?' . http_build_query([
                'tx_academicpersons_detail' => [
                    'controller' => 'Profile',
                    'action' => 'detail',
                    'profile' => 1,
                ],
                'cHash' => '13c8ec3ab2a317651a40bd164df8a366',
            ])
        );
    }

    /**
     * @param list<string> $additionalConstants Read after the shipped constants, which is
     *        where an integrator overrides one.
     */
    private function setUpStaticTemplateSite(array $additionalConstants = []): void
    {
        $this->setUpFrontendRootPage(
            pageId: 1,
            typoScriptFiles: [
                'constants' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript',
                    'EXT:academic_persons/Configuration/TypoScript/Default/constants.typoscript',
                    ...$additionalConstants,
                ],
                'setup' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript',
                    'EXT:academic_persons/Configuration/TypoScript/Default/setup.typoscript',
                    'EXT:academic_persons/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/Rendering.typoscript',
                ],
            ],
        );
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
        ]);
    }

    /**
     * @param array<string, bool> $settings
     */
    private function setUpSiteSetSite(array $settings = []): void
    {
        // The page object only. "clear = 0" keeps what the sets contribute.
        $this->getConnectionPool()->getConnectionForTable('sys_template')->insert(
            'sys_template',
            [
                'pid' => 1,
                'root' => 1,
                'clear' => 0,
                'title' => 'Site package',
                'constants' => '',
                'config' => '@import \'EXT:academic_persons/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/Rendering.typoscript\'',
            ],
        );
        $this->writeSiteConfiguration(
            // The site identifier is part of several caches the test instance keeps for
            // the whole class, so differently configured sites need different ones.
            identifier: 'acme-' . substr(md5(json_encode($settings, JSON_THROW_ON_ERROR)), 0, 10),
            site: $this->buildSiteConfiguration(
                rootPageId: 1,
                base: self::FRONTEND_PLUGIN_TEST_BASE,
                additionalRootConfiguration: [
                    'dependencies' => ['typo3/fluid-styled-content', self::AGGREGATE_SET],
                    'settings' => $settings,
                ],
            ),
            languages: [
                $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
            ],
        );
    }
}
