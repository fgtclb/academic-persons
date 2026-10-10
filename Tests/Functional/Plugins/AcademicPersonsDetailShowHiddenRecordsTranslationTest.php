<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Plugins;

use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Frontend\Page\CacheHashCalculator;

/**
 * The plugin option "Show hidden records" of the `academicpersons_detail` plugin on a
 * translated page (ACE-884).
 *
 * The detail link carries the uid of the default record. With the option the plugin
 * resolves the profile itself, and that lookup has to select the default record in a
 * translated language and overlay it with its translation, as the argument mapping of
 * Extbase does for a visible profile.
 *
 * Profile 31 and its translation are visible. The hidden profile 37 has no translation.
 * The hidden profile 38 has a visible translation. `/profile` shows hidden records,
 * `/profile-without-hidden` does not.
 */
final class AcademicPersonsDetailShowHiddenRecordsTranslationTest extends AbstractAcademicPersonsTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
        'DE' => ['id' => 1, 'title' => 'Deutsch', 'locale' => 'de_DE.UTF8', 'iso' => 'de', 'hrefLang' => 'de-DE', 'direction' => ''],
    ];

    private const PAGE_WITH_HIDDEN_RECORDS = 2;

    private const PAGE_WITHOUT_HIDDEN_RECORDS = 3;

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration();
        $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsDetailShowHiddenRecordsTranslation/detailPages.csv');
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
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    /**
     * @return array<string, array{'strict'|'fallback'|'free'}>
     */
    public static function fallbackTypes(): array
    {
        return [
            'fallback type "strict"' => ['strict'],
            'fallback type "fallback"' => ['fallback'],
            'fallback type "free"' => ['free'],
        ];
    }

    /**
     * The fallback types that show an untranslated record in the default language. `strict`
     * is left out: there the result differs by core version for a visible profile as well,
     * see `AcademicPersonsDetailPluginTest`.
     *
     * @return array<string, array{'fallback'|'free'}>
     */
    public static function fallbackTypesKeepingTheDefaultLanguage(): array
    {
        return [
            'fallback type "fallback"' => ['fallback'],
            'fallback type "free"' => ['free'],
        ];
    }

    /**
     * @param 'strict'|'fallback'|'free' $fallbackType
     */
    private function writeSite(string $fallbackType): void
    {
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
     * Request the detail of a profile by the uid of its default record, as every link to it
     * carries it, with a valid cache hash.
     */
    private function requestProfileDetail(string $pageUrl, int $pageId, int $profileUid): ResponseInterface
    {
        $arguments = [
            'tx_academicpersons_detail' => [
                'action' => 'detail',
                'controller' => 'Profile',
                'profile' => $profileUid,
            ],
        ];
        $cacheHash = GeneralUtility::makeInstance(CacheHashCalculator::class)->generateForParameters(
            HttpUtility::buildQueryString(['id' => $pageId] + $arguments)
        );

        return $this->requestFrontendPage(
            $pageUrl . '?' . HttpUtility::buildQueryString($arguments + ['cHash' => $cacheHash])
        );
    }

    /**
     * @param 'strict'|'fallback'|'free' $fallbackType
     */
    #[DataProvider('fallbackTypes')]
    #[Test]
    public function translatedDetailPageShowsTheTranslationOfAVisibleProfile(string $fallbackType): void
    {
        $this->writeSite($fallbackType);

        $response = $this->requestProfileDetail('https://www.acme.com/de/profil', self::PAGE_WITH_HIDDEN_RECORDS, 31);

        $this->assertSame(200, $response->getStatusCode());
        $content = (string)$response->getBody();
        $this->assertStringContainsString('Sichtbar-Lopez', $content);
        $this->assertStringNotContainsString('Visible-Lopez', $content);
    }

    /**
     * @param 'strict'|'fallback'|'free' $fallbackType
     */
    #[DataProvider('fallbackTypes')]
    #[Test]
    public function translatedDetailPageShowsTheVisibleTranslationOfAHiddenProfile(string $fallbackType): void
    {
        $this->writeSite($fallbackType);

        $response = $this->requestProfileDetail('https://www.acme.com/de/profil', self::PAGE_WITH_HIDDEN_RECORDS, 38);

        $this->assertSame(200, $response->getStatusCode());
        $content = (string)$response->getBody();
        $this->assertStringContainsString('Zurueckgezogen-Petrov', $content);
        $this->assertStringNotContainsString('Withdrawn-Petrov', $content);
    }

    /**
     * An untranslated hidden profile is shown in the default language, as the argument
     * mapping of Extbase shows an untranslated visible one.
     *
     * @param 'fallback'|'free' $fallbackType
     */
    #[DataProvider('fallbackTypesKeepingTheDefaultLanguage')]
    #[Test]
    public function translatedDetailPageShowsAnUntranslatedHiddenProfileInTheDefaultLanguage(string $fallbackType): void
    {
        $this->writeSite($fallbackType);

        $response = $this->requestProfileDetail('https://www.acme.com/de/profil', self::PAGE_WITH_HIDDEN_RECORDS, 37);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('Untranslated-Olsen', (string)$response->getBody());
    }

    #[Test]
    public function defaultLanguageDetailPageShowsTheHiddenProfile(): void
    {
        $this->writeSite('strict');

        $response = $this->requestProfileDetail('https://www.acme.com/profile', self::PAGE_WITH_HIDDEN_RECORDS, 38);

        $this->assertSame(200, $response->getStatusCode());
        $content = (string)$response->getBody();
        $this->assertStringContainsString('Withdrawn-Petrov', $content);
        $this->assertStringNotContainsString('Zurueckgezogen-Petrov', $content);
    }

    /**
     * Without the option a hidden profile stays not found, its visible translation as well.
     *
     * @param 'strict'|'fallback'|'free' $fallbackType
     */
    #[DataProvider('fallbackTypes')]
    #[Test]
    public function translatedDetailPageWithoutTheOptionAnswersNotFoundForAHiddenProfile(string $fallbackType): void
    {
        $this->writeSite($fallbackType);

        $response = $this->requestProfileDetail('https://www.acme.com/de/profil-ohne-versteckte', self::PAGE_WITHOUT_HIDDEN_RECORDS, 38);

        $this->assertSame(404, $response->getStatusCode());
        $content = (string)$response->getBody();
        $this->assertStringNotContainsString('Zurueckgezogen-Petrov', $content);
        $this->assertStringNotContainsString('Withdrawn-Petrov', $content);
    }

    /**
     * Without the option the argument mapping of Extbase resolves a visible profile.
     *
     * @param 'strict'|'fallback'|'free' $fallbackType
     */
    #[DataProvider('fallbackTypes')]
    #[Test]
    public function translatedDetailPageWithoutTheOptionShowsTheTranslationOfAVisibleProfile(string $fallbackType): void
    {
        $this->writeSite($fallbackType);

        $response = $this->requestProfileDetail('https://www.acme.com/de/profil-ohne-versteckte', self::PAGE_WITHOUT_HIDDEN_RECORDS, 31);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('Sichtbar-Lopez', (string)$response->getBody());
    }
}
