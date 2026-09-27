<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Plugins;

use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TESTS\TestPluginActionContext\EventListener\RecordPluginActionContextListener;

/**
 * What a listener finds in the context an event carries while a persons plugin renders.
 *
 * `EXT:test_plugin_action_context` records the content element each context names. It declares
 * the `academic_base` interface for the profile query event, which gets the persons context,
 * and for the plugin view event, and both interfaces for the title placeholder event, the one
 * persons event that still declares the deprecated persons interface - the two kinds of
 * listener a project has in 3.x, and both have to be called.
 */
final class AcademicPersonsPluginActionContextTest extends AbstractAcademicPersonsTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    protected function setUp(): void
    {
        // The detail request below carries the cHash the detail plugin test uses, so the cHash
        // configuration and the page it is calculated for are the same as there.
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
        $this->addTestExtensionsToLoad(
            'georgringer/numbered-pagination',
            'tests/plugin-templates',
            'tests/test-plugin-action-context',
        );
        parent::setUp();
        RecordPluginActionContextListener::$contentElements = [];

        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsPluginActionContext/listAndDetailPlugin.csv');
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
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration('EN', '/'),
        ]);
    }

    protected function tearDown(): void
    {
        RecordPluginActionContextListener::$contentElements = [];
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    #[Test]
    public function aListenerTypedAgainstTheAcademicBaseContextReadsTheListPluginContentElement(): void
    {
        $content = $this->renderFrontendPage('https://www.acme.com/list');

        $this->assertStringContainsString('Müllermann', $content);
        $this->assertSame([7], RecordPluginActionContextListener::$contentElements['view'] ?? null);
        $query = RecordPluginActionContextListener::$contentElements['query'] ?? [];
        $this->assertNotSame([], $query);
        $this->assertSame([7], array_values(array_unique($query)));
    }

    #[Test]
    public function listenersOfTheDetailPluginReadItsContentElementWhicheverContextTheyDeclare(): void
    {
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
        $this->assertSame([1], RecordPluginActionContextListener::$contentElements['view'] ?? null);
        // The page title of the detail view is built from the context of the detail action, one
        // event per placeholder of the title format.
        foreach (['title', 'title (persons context)'] as $event) {
            $title = RecordPluginActionContextListener::$contentElements[$event] ?? [];
            $this->assertNotSame([], $title, $event);
            $this->assertSame([1], array_values(array_unique($title)), $event);
        }
    }
}
