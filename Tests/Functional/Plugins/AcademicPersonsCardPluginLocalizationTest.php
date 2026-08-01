<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Plugins;

use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Information\Typo3Version;

/**
 * Renders the `academicpersons_card` plugin in a second site language.
 *
 * `cardAction()` carried a standing `@todo` claiming the action "is literally broken in
 * multi language sites". These tests were written to reproduce that, and **it does not
 * reproduce**: across the matrix below the card renders what the list plugin renders
 * from the same selection, and the list behaviour is the covered and accepted one (see
 * `AcademicPersonsListPluginTest`). The `@todo` was removed on the strength of these
 * tests rather than acted on.
 *
 * That is not the same as calling every result here desirable. Two were not, and neither
 * belonged to this action - both came out of the shared `profileList` path in
 * `ProfileRepository::applyDemandForQuery()`, so they hit the list plugin just as hard:
 *
 * - a site language with `fallbackType: free` rendered **default language** profiles,
 *   because dropping the language restriction for a selection of default language uids
 *   left `OVERLAYS_OFF` in place and nothing overlaid the rows. Fixed under ACE-341;
 *   the free mode test below asserts the corrected output.
 * - under `fallbackType: strict` an untranslated profile is not dropped. That one is a
 *   core Extbase defect (forge #88886) fixed in TYPO3 v14.3.6, and the two tests below
 *   split on that version exactly as the list plugin tests do.
 */
final class AcademicPersonsCardPluginLocalizationTest extends AbstractAcademicPersonsTestCase
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
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    /**
     * @param 'strict'|'fallback'|'free' $fallbackType
     */
    private function setUpTestCase(string $dataSet, string $fallbackType = 'strict'): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsCardPluginLocalization/' . $dataSet . '.csv');
        $this->setUpFrontendRootPage(
            pageId: 1,
            typoScriptFiles: [
                'constants' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript',
                    'EXT:academic_persons/Configuration/TypoScript/Default/constants.typoscript',
                ],
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
            $this->buildLanguageConfiguration(
                identifier: 'DE',
                base: '/de/',
                fallbackIdentifiers: $fallbackType === 'fallback' ? ['EN'] : [],
                fallbackType: $fallbackType,
            ),
        ]);
    }

    private function renderEnglishPage(): string
    {
        return $this->renderFrontendPage('https://www.acme.com/home');
    }

    private function renderGermanPage(): string
    {
        return $this->renderFrontendPage('https://www.acme.com/de/home');
    }

    /**
     * The item partial composes the heading from first, middle and last name, so an empty
     * middle name leaves two spaces in the markup.
     */
    private function assertRendersProfileName(string $content, string $first, string $last): void
    {
        $this->assertMatchesRegularExpression(
            sprintf('#%s\s+%s#u', preg_quote($first, '#'), preg_quote($last, '#')),
            $content,
        );
    }

    #[Test]
    public function fullyLocalizedCardRendersTranslatedProfilesForRequestedLanguage(): void
    {
        $this->setUpTestCase('cardPage_fullyLocalized');

        $content = $this->renderGermanPage();
        $this->assertRendersProfileName($content, '[DE] Max', 'Müllermann');
        $this->assertRendersProfileName($content, '[DE] Horst', 'Huber');
        $this->assertStringNotContainsString('[EN] Max', $content);
        $this->assertStringNotContainsString('[EN] Horst', $content);
    }

    #[Test]
    public function fullyLocalizedCardRendersTranslatedContractsAndLocations(): void
    {
        // The card renders contract data through the item partial, and a contract carries
        // a location relation - three record types have to be overlaid, not just the
        // profile itself.
        $this->setUpTestCase('cardPage_fullyLocalized');

        $content = $this->renderGermanPage();
        $this->assertStringContainsString('[DE] Professor', $content);
        $this->assertStringContainsString('[DE] Dozent', $content);
        $this->assertStringContainsString('[DE] Hauptgelaende', $content);
        $this->assertStringNotContainsString('[EN] Main Campus', $content);
    }

    #[Test]
    public function fullyLocalizedCardRendersDefaultLanguageProfilesForRequestedDefaultLanguage(): void
    {
        $this->setUpTestCase('cardPage_fullyLocalized');

        $content = $this->renderEnglishPage();
        $this->assertRendersProfileName($content, '[EN] Max', 'Müllermann');
        $this->assertRendersProfileName($content, '[EN] Horst', 'Huber');
        $this->assertStringNotContainsString('[DE]', $content);
    }

    #[Test]
    public function fullyLocalizedCardWithFallbackTypeFallbackRendersTranslatedProfiles(): void
    {
        $this->setUpTestCase('cardPage_fullyLocalized', 'fallback');

        $content = $this->renderGermanPage();
        $this->assertRendersProfileName($content, '[DE] Max', 'Müllermann');
        $this->assertRendersProfileName($content, '[DE] Horst', 'Huber');
        $this->assertStringNotContainsString('[EN] Max', $content);
    }

    #[Test]
    public function fullyLocalizedCardWithFallbackTypeFreeRendersTranslatedProfiles(): void
    {
        // Free mode used to render English profiles under a German heading (ACE-341):
        // a manual selection holds default language uids, so the language restriction
        // has to come off - but with `OVERLAYS_OFF` nothing then overlaid the rows that
        // came back. `matchSelectedUidsAcrossLanguages()` lifts the aspect first.
        $this->setUpTestCase('cardPage_fullyLocalized', 'free');

        $content = $this->renderGermanPage();
        $this->assertStringContainsString('[DE] Unser Team', $content);
        $this->assertRendersProfileName($content, '[DE] Max', 'Müllermann');
        $this->assertRendersProfileName($content, '[DE] Horst', 'Huber');
        $this->assertStringNotContainsString('[EN] Max', $content);
        $this->assertStringNotContainsString('[EN] Horst', $content);
    }

    /**
     * Untranslated selected profiles are fetched through Extbase persistence, which did
     * not honour "fallbackType: strict" before TYPO3 v14.3.6 (forge #88886, core change
     * 66694 with 14.3 backport 94935, no v13.4 backport). This test states the corrected
     * behaviour and therefore only runs from v14.3.6 on; the test below states what
     * happens before it, so the action is covered on every supported core either way.
     *
     * @todo Verify this assertion once TYPO3 v14.3.6 or newer is installable here - it
     *       mirrors `AcademicPersonsListPluginTest`, which carries the same open `@todo`.
     * @see https://forge.typo3.org/issues/88886
     */
    #[Test]
    public function partiallyLocalizedCardWithFallbackTypeStrictDropsUntranslatedProfiles(): void
    {
        if (version_compare((new Typo3Version())->getVersion(), '14.3.6', '<')) {
            $this->markTestSkipped(
                'Extbase honours "fallbackType: strict" for untranslated selected profiles only since '
                . 'TYPO3 v14.3.6 (forge #88886).'
            );
        }
        $this->setUpTestCase('cardPage_notAllProfilesLocalized');

        $content = $this->renderGermanPage();
        $this->assertRendersProfileName($content, '[DE] Max', 'Müllermann');
        $this->assertStringNotContainsString('Horst', $content);
    }

    /**
     * The inverse of the test above: what every core this extension currently supports
     * actually does. Measured on v13.4 and on v14.3.5, and identical to the list plugin
     * under the same fixture - which is what settles that this action is not at fault.
     *
     * @see https://forge.typo3.org/issues/88886
     */
    #[Test]
    public function partiallyLocalizedCardWithFallbackTypeStrictKeepsUntranslatedProfilesBeforeCoreFix(): void
    {
        if (version_compare((new Typo3Version())->getVersion(), '14.3.6', '>=')) {
            $this->markTestSkipped(
                'Core fix for forge #88886 is present, see the test above for the corrected behaviour.'
            );
        }
        $this->setUpTestCase('cardPage_notAllProfilesLocalized');

        $content = $this->renderGermanPage();
        $this->assertRendersProfileName($content, '[DE] Max', 'Müllermann');
        $this->assertRendersProfileName($content, '[EN] Horst', 'Huber');
    }

    #[Test]
    public function partiallyLocalizedCardWithPluginFallbackFlagRendersUntranslatedProfiles(): void
    {
        // `settings.fallbackForNonTranslated` is the one language option this action does
        // honour, and the reason it exists: show a profile that has no translation even
        // where the site language is strict.
        $this->setUpTestCase('cardPage_notAllProfilesLocalized_fallbackForNonTranslatedSet');

        $content = $this->renderGermanPage();
        $this->assertRendersProfileName($content, '[DE] Max', 'Müllermann');
        $this->assertRendersProfileName($content, '[EN] Horst', 'Huber');
    }

    #[Test]
    public function partiallyLocalizedCardWithFallbackTypeFallbackRendersUntranslatedProfiles(): void
    {
        $this->setUpTestCase('cardPage_notAllProfilesLocalized', 'fallback');

        $content = $this->renderGermanPage();
        $this->assertRendersProfileName($content, '[DE] Max', 'Müllermann');
        $this->assertRendersProfileName($content, '[EN] Horst', 'Huber');
    }
}
