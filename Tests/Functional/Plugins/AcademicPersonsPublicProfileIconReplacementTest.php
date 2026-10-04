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
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Package\PackageManager;

/**
 * A site package replaces an icon of the public profile in its own
 * `Configuration/FrontendIcons.php`, and the detail view shows its drawing without a
 * template override. A replacement left in `Configuration/Icons.php` does not reach
 * the detail view, which shows the shipped drawing.
 *
 * The fixture `tests/profile-icon-replacement` is that site package: it replaces
 * `tx-academicbase-info-email` in the file of the frontend and `tx-academicbase-info-phone`
 * in the file of the backend, both with a rectangle. Both are shared icons of
 * academic_base, so the replacement is read after academic_base registers them. A TYPO3
 * v14 test instance orders the packages by their keys, so the first test asserts that the
 * fixture loads after academic_base and academic_persons.
 */
final class AcademicPersonsPublicProfileIconReplacementTest extends AbstractAcademicPersonsTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    /**
     * The rectangle of the fixture, as the serialisers of both core versions and the DOM
     * write it.
     */
    private const REPLACED_DRAWING = 'x="2" y="5" width="12" height="6"';

    protected function setUp(): void
    {
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
        $this->addTestExtensionsToLoad('tests/profile-icon-replacement');
        parent::setUp();
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    #[Test]
    public function theSitePackageLoadsAfterTheExtension(): void
    {
        $packageKeys = array_keys($this->get(PackageManager::class)->getActivePackages());
        $fixturePosition = array_search('test_profile_icon_replacement', $packageKeys, true);

        $this->assertGreaterThan(array_search('academic_base', $packageKeys, true), $fixturePosition);
        $this->assertGreaterThan(array_search('academic_persons', $packageKeys, true), $fixturePosition);
    }

    #[Test]
    public function aFrontendIconOfTheSitePackageReplacesTheShippedOne(): void
    {
        $envelopes = $this->renderedIconMarkups($this->renderShippedProfile(), 'tx-academicbase-info-email');

        $this->assertNotSame([], $envelopes);
        foreach ($envelopes as $markup) {
            $this->assertStringContainsString(self::REPLACED_DRAWING, $markup);
        }
    }

    #[Test]
    public function aReplacementInTheBackendFileDoesNotReachTheDetailView(): void
    {
        $phones = $this->renderedIconMarkups($this->renderShippedProfile(), 'tx-academicbase-info-phone');

        $this->assertNotSame([], $phones);
        foreach ($phones as $markup) {
            $this->assertStringNotContainsString(self::REPLACED_DRAWING, $markup);
            $this->assertStringContainsString('d="M224.2 89C216.3', $markup);
        }
    }

    /**
     * The inner markup of every rendered icon with the identifier, in page order.
     *
     * @return list<string>
     */
    private function renderedIconMarkups(string $content, string $identifier): array
    {
        preg_match_all(
            '@data-identifier="' . preg_quote($identifier, '@') . '" aria-hidden="true">\s*<span class="icon-markup">(.*?)</span>@s',
            $content,
            $matches,
        );

        return $matches[1];
    }

    private function renderShippedProfile(): string
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsPublicProfilePlugin/shippedLayout.csv');
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
        ]);

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
}
