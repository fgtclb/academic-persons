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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Page\CacheHashCalculator;

/**
 * The position line of the shipped detail view with `profile.details.position.fields` set to
 * every value it accepts, `position`, `functionType` and `organisationalUnit`. The fixture
 * extension `test_position_fields` ships that list, and it is loaded for this class only. The
 * shipped default, the position alone, is covered by {@see AcademicPersonsPublicProfilePluginTest}.
 */
final class AcademicPersonsDetailPositionFieldsTest extends AbstractAcademicPersonsTestCase
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
                    'enforceValidation' => true,
                ],
            ],
        ]);
        $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
        $this->addTestExtensionsToLoad('tests/test-position-fields');
        parent::setUp();
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    private function renderProfile(int $profileUid): string
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsDetailPositionFields/positionFields.csv');
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

        $query = http_build_query([
            'tx_academicpersons_detail' => [
                'controller' => 'Profile',
                'action' => 'detail',
                'profile' => $profileUid,
            ],
        ]);
        $cacheHash = GeneralUtility::makeInstance(CacheHashCalculator::class)
            ->generateForParameters('id=2&' . $query);

        return $this->renderFrontendPage('https://www.acme.com/home?' . $query . '&cHash=' . $cacheHash);
    }

    /**
     * The contract of profile 5 has a function type and no position. Before the position line
     * could be configured it rendered nothing for such a contract.
     */
    #[Test]
    public function functionTypeIsShownForAContractWithoutAPosition(): void
    {
        $content = $this->renderProfile(5);

        $this->assertStringContainsString(
            '<p class="academic-persons-detail__position"><span class="academic-persons-detail__position-part academic-persons-detail__position-part--functionType">Student Advisor</span></p>',
            $content,
        );
        $this->assertStringNotContainsString('academic-persons-detail__position-part--position', $content);
    }

    /**
     * @return \Generator<string, array{0: int, 1: string}>
     */
    public static function functionTypeNameDataProvider(): \Generator
    {
        yield 'female profile, female name maintained' => [1, 'Head of Department (f)'];
        yield 'male profile, male name maintained' => [2, 'Head of Department (m)'];
        yield 'profile without gender' => [3, 'Head of Department'];
        yield 'diverse profile' => [4, 'Head of Department'];
        yield 'female profile, no female name maintained' => [5, 'Student Advisor'];
    }

    /**
     * The name follows the gender of the profile where a name is maintained for it, and is the
     * general name otherwise - also for `diverse`, for which a function type has no name.
     */
    #[Test]
    #[DataProvider('functionTypeNameDataProvider')]
    public function functionTypeNameFollowsTheGenderOfTheProfile(int $profileUid, string $expectedName): void
    {
        $content = $this->renderProfile($profileUid);

        $this->assertStringContainsString(
            'academic-persons-detail__position-part--functionType">' . $expectedName . '</span>',
            $content,
        );
    }

    /**
     * Each configured value is an element of its own inside the line, in the configured order.
     * The organisational unit renders its display text. The values are escaped once: the line
     * is put together from the rendered parts and printed as it is.
     */
    #[Test]
    public function configuredValuesAreRenderedInTheConfiguredOrder(): void
    {
        $content = $this->renderProfile(6);

        $this->assertStringContainsString(
            '<p class="academic-persons-detail__position">'
            . '<span class="academic-persons-detail__position-part academic-persons-detail__position-part--position">Professor of Physics &amp; Optics &lt;Lab&gt;</span>'
            . '<span class="academic-persons-detail__position-part academic-persons-detail__position-part--functionType">Head of Department (m)</span>'
            . '<span class="academic-persons-detail__position-part academic-persons-detail__position-part--organisationalUnit">Institute of Applied Physics</span>'
            . '</p>',
            $content,
        );
    }

    /**
     * The contract of profile 7 has no position, no function type and no unit. It gets no
     * position line, not an empty paragraph.
     */
    #[Test]
    public function contractWithoutAnyConfiguredValueGetsNoLine(): void
    {
        $content = $this->renderProfile(7);

        $this->assertStringContainsString('academic-persons-detail__positions"', $content);
        $this->assertStringNotContainsString('class="academic-persons-detail__position"', $content);
    }
}
