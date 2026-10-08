<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Plugins;

use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;

/**
 * The plugin option "Show hidden records" of the `academicpersons_list` plugin on a
 * translated page (ACE-857).
 *
 * The fixture holds a visible and a hidden profile, each with a German translation that
 * shares the visibility of its default record, and a list plugin translated to German
 * with the option switched on, once without and once with pagination. The query lifts
 * the hidden flag through its own settings, while the language overlay follows the
 * visibility of the context, so the hidden profile used to appear with its English name
 * on TYPO3 v13. TYPO3 v14.3.7 overlays with the ignored enable fields of the query
 * settings itself.
 */
final class AcademicPersonsShowHiddenRecordsTranslationTest extends AbstractAcademicPersonsTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
        'DE' => ['id' => 1, 'title' => 'Deutsch', 'locale' => 'de_DE.UTF8', 'iso' => 'de', 'hrefLang' => 'de-DE', 'direction' => ''],
    ];

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration();
        $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsShowHiddenRecordsTranslation/listPages.csv');
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    /**
     * @param 'strict'|'fallback' $fallbackType
     */
    private function setUpSite(string $fallbackType): void
    {
        $this->setUpFrontendRootPage(
            pageId: 1,
            typoScriptFiles: [
                'constants' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript',
                    'EXT:academic_persons/Configuration/TypoScript/Default/constants.typoscript',
                    'EXT:academic_persons/Tests/Functional/Plugins/Fixtures/TypoScript/Constants/DetailPage.typoscript',
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
            $this->buildLanguageConfiguration(
                identifier: 'DE',
                base: '/de/',
                fallbackIdentifiers: $fallbackType === 'strict' ? [] : ['EN'],
                fallbackType: $fallbackType,
            ),
        ]);
    }

    /**
     * Each fallback type on the list without pagination (`/home`) and on the paginated
     * list (`/paginated`), whose paginator executes the query of the page on its own.
     *
     * @return array<string, array{'strict'|'fallback', string}>
     */
    public static function fallbackTypesAndPages(): array
    {
        return [
            'fallback type "strict", without pagination' => ['strict', 'home'],
            'fallback type "fallback", without pagination' => ['fallback', 'home'],
            'fallback type "strict", paginated' => ['strict', 'paginated'],
            'fallback type "fallback", paginated' => ['fallback', 'paginated'],
        ];
    }

    /**
     * @param 'strict'|'fallback' $fallbackType
     */
    #[DataProvider('fallbackTypesAndPages')]
    #[Test]
    public function translatedListShowsTheTranslationOfAHiddenProfile(string $fallbackType, string $page): void
    {
        $this->setUpSite($fallbackType);

        $german = $this->renderFrontendPage('https://www.acme.com/de/' . $page);

        $this->assertStringContainsString('Sichtbar-Lopez', $german);
        $this->assertStringContainsString('Verborgen-Moore', $german);
        $this->assertStringNotContainsString('Hidden-Moore', $german);
        $this->assertStringNotContainsString('Visible-Lopez', $german);
    }

    /**
     * The start and end time of a default record keep deciding for its translation, which
     * has none of its own, as they do without the option.
     *
     * @param 'strict'|'fallback' $fallbackType
     */
    #[DataProvider('fallbackTypesAndPages')]
    #[Test]
    public function translatedListLeavesTheTranslationsOfScheduledAndExpiredProfilesOut(string $fallbackType, string $page): void
    {
        $this->setUpSite($fallbackType);

        $german = $this->renderFrontendPage('https://www.acme.com/de/' . $page);

        $this->assertStringContainsString('Verborgen-Moore', $german);
        $this->assertStringNotContainsString('Geplant-Novak', $german);
        $this->assertStringNotContainsString('Scheduled-Novak', $german);
        $this->assertStringNotContainsString('Abgelaufen-Olsen', $german);
        $this->assertStringNotContainsString('Expired-Olsen', $german);
    }

    /**
     * @param 'strict'|'fallback' $fallbackType
     */
    #[DataProvider('fallbackTypesAndPages')]
    #[Test]
    public function defaultLanguageListShowsTheHiddenProfile(string $fallbackType, string $page): void
    {
        $this->setUpSite($fallbackType);

        $english = $this->renderFrontendPage('https://www.acme.com/' . $page);

        $this->assertStringContainsString('Visible-Lopez', $english);
        $this->assertStringContainsString('Hidden-Moore', $english);
        $this->assertStringNotContainsString('Verborgen-Moore', $english);
    }
}
