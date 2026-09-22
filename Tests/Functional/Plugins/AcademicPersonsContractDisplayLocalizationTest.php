<?php

declare(strict_types=1);

/*
 * This file is part of the fgtclb/academic extension collection.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace FGTCLB\AcademicPersons\Tests\Functional\Plugins;

use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;

/**
 * "Only contracts of the selected organisational units" on a translated page. The content
 * element stores the default-language uid of a unit, while the German rendering reads
 * translated contracts, whose unit Extbase resolves to the German translation of that
 * unit; the match has to be made on the default-language uid, in both language fallback
 * modes.
 *
 * The fixture's German list is restricted to "Computer Science" (uid 2, German "Informatik",
 * uid 4); Ada Lovelace's contracts are "Dekanin" in Mathematics and "Professorin" in
 * Computer Science.
 */
final class AcademicPersonsContractDisplayLocalizationTest extends AbstractAcademicPersonsTestCase
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
     * @return \Generator<string, array{0: non-empty-string}>
     */
    public static function fallbackTypeDataProvider(): \Generator
    {
        yield 'strict' => ['strict'];
        yield 'free' => ['free'];
    }

    /**
     * @param non-empty-string $fallbackType
     */
    #[Test]
    #[DataProvider('fallbackTypeDataProvider')]
    public function theContractsOfTheRestrictedUnitAreShownOnATranslatedPage(string $fallbackType): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsContractDisplayPolicy/localized.csv');
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
            $this->buildDefaultLanguageConfiguration('EN', '/'),
            $this->buildLanguageConfiguration(
                identifier: 'DE',
                base: '/de/',
                fallbackIdentifiers: [],
                fallbackType: $fallbackType,
            ),
        ]);

        $content = $this->renderFrontendPage('https://www.acme.com/de/home');

        $this->assertStringContainsString('[DE] Professorin', $content);
        $this->assertStringNotContainsString('[DE] Dekanin', $content);
        $this->assertStringNotContainsString('[EN]', $content);
    }
}
