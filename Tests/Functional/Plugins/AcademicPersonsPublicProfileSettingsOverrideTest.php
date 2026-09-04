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

/**
 * A site package overriding the `profile` map of `Settings.yaml` changes what the shipped
 * `Detail.html` renders, without a template override. The fixture extension
 * `test_public_profile_settings` ships such a file, and it is loaded for this class only.
 */
final class AcademicPersonsPublicProfileSettingsOverrideTest extends AbstractAcademicPersonsTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

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
        $this->addTestExtensionsToLoad('tests/test-public-profile-settings');
        parent::setUp();
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    /**
     * The override lists `profileEntries` before `headline` and names the last name before the
     * first name; it has no `left` column and no `subline`. What is not configured is not
     * rendered - the navigation and the subline are absent, not empty.
     */
    #[Test]
    public function overriddenProfileMapControlsElementAndFieldOrder(): void
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

        $profileEntriesPosition = strpos($content, 'academic-persons-detail__profile-entries');
        $lastNamePosition = strpos($content, 'academic-persons-detail__headline-part">Müllermann</span>');
        $firstNamePosition = strpos($content, 'academic-persons-detail__headline-part">[EN] Max</span>');
        $this->assertNotFalse($profileEntriesPosition);
        $this->assertNotFalse($lastNamePosition);
        $this->assertNotFalse($firstNamePosition);
        $this->assertLessThan($lastNamePosition, $profileEntriesPosition);
        $this->assertLessThan($firstNamePosition, $lastNamePosition);
        // The title is not part of the overridden headline.
        $this->assertStringNotContainsString('academic-persons-detail__headline-part">Prof. Dr.</span>', $content);
        // Only `miscellaneous` is listed under `profileEntries`; `teachingArea` has content and is not rendered.
        $this->assertStringContainsString('id="academic-persons-profile-entry-1-1-miscellaneous"', $content);
        $this->assertStringNotContainsString('academic-persons-profile-entry-1-1-teachingArea', $content);
        $this->assertStringNotContainsString('academic-persons-detail__navigation', $content);
        $this->assertStringNotContainsString('academic-persons-detail__subline', $content);
        $this->assertStringNotContainsString('academic-persons-detail__contact"', $content);
    }
}
