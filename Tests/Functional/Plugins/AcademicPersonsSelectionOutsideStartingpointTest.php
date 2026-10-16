<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Plugins;

use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;

/**
 * Profiles picked by hand in "List of profiles" are shown wherever they are stored, as the
 * card and the selected profiles element show theirs (ACE-923). The Startingpoint of the
 * element still decides which profiles a list without a selection shows, and the
 * visibility of a picked profile still counts.
 *
 * The fixture: the folder "Staff" (100) holds Max Müllermann (1) and Horst Huber (2), the
 * folder "Guests" (101) holds Erika Musterfrau (3), the hidden Hanna Hidden (4) and the
 * deleted Dora Deleted (5). Every element has "Staff" as its Startingpoint.
 */
final class AcademicPersonsSelectionOutsideStartingpointTest extends AbstractAcademicPersonsTestCase
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
        $this->addTestExtensionsToLoad('georgringer/numbered-pagination', 'tests/plugin-templates');
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsSelectionOutsideStartingpoint/records.csv');
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
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function listElementsDataProvider(): \Generator
    {
        yield 'list' => ['academicpersons_list'];
        yield 'list and detail' => ['academicpersons_listanddetail'];
    }

    /**
     * @param array<string, string> $settings FlexForm fields below `settings.`
     */
    private function addContentElement(string $cType, array $settings): void
    {
        $fields = '';
        foreach (array_merge(['fallbackForNonTranslated' => '0'], $settings) as $name => $value) {
            $fields .= sprintf(
                '<field index="settings.%s"><value index="vDEF">%s</value></field>',
                $name,
                htmlspecialchars($value),
            );
        }
        $this->getConnectionPool()->getConnectionForTable('tt_content')->insert('tt_content', [
            'uid' => 1,
            'pid' => 2,
            'CType' => $cType,
            'header' => '',
            'pages' => '100',
            'pi_flexform' => '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>'
                . '<T3FlexForms><data><sheet index="sDEF"><language index="lDEF">'
                . $fields
                . '</language></sheet></data></T3FlexForms>',
        ]);
    }

    /**
     * The profile items the list renders, `#<position>(<uid>): <name>` each, in order.
     *
     * @return list<string>
     */
    private function renderedProfiles(): array
    {
        $content = $this->renderFrontendPage('https://www.acme.com/home');
        preg_match_all('/#\d+\(\d+\): .+?(?= LINK:)/u', $content, $matches);

        return $matches[0];
    }

    #[DataProvider('listElementsDataProvider')]
    #[Test]
    public function aPickedProfileOutsideTheStartingpointIsShownInTheOrderOfTheSelection(string $cType): void
    {
        $this->addContentElement($cType, ['demand.profileList' => '3,1']);

        $this->assertSame(
            ['#0(3): Erika Musterfrau', '#1(1): Max Müllermann'],
            $this->renderedProfiles(),
        );
    }

    #[DataProvider('listElementsDataProvider')]
    #[Test]
    public function aHiddenOrDeletedPickedProfileOutsideTheStartingpointStaysHidden(string $cType): void
    {
        $this->addContentElement($cType, ['demand.profileList' => '4,3,5,1']);

        $this->assertSame(
            ['#0(3): Erika Musterfrau', '#1(1): Max Müllermann'],
            $this->renderedProfiles(),
        );
    }

    #[DataProvider('listElementsDataProvider')]
    #[Test]
    public function showHiddenRecordsShowsAHiddenPickedProfileOutsideTheStartingpoint(string $cType): void
    {
        $this->addContentElement($cType, ['demand.profileList' => '4,3,5,1', 'showHiddenRecords' => '1']);

        $this->assertSame(
            ['#0(4): Hanna Hidden', '#1(3): Erika Musterfrau', '#2(1): Max Müllermann'],
            $this->renderedProfiles(),
        );
    }

    #[DataProvider('listElementsDataProvider')]
    #[Test]
    public function aPaginatedSelectionCutsItsPagesFromTheWholeSelection(string $cType): void
    {
        $this->addContentElement($cType, [
            'demand.profileList' => '3,2,1',
            'paginationEnabled' => '1',
            'pagination.resultsPerPage' => '2',
        ]);

        $this->assertSame(
            ['#0(3): Erika Musterfrau', '#1(2): Horst Huber'],
            $this->renderedProfiles(),
        );
    }

    /**
     * A list without a selection still shows the profiles of its Startingpoint only.
     */
    #[DataProvider('listElementsDataProvider')]
    #[Test]
    public function aListWithoutASelectionShowsTheProfilesOfTheStartingpointOnly(string $cType): void
    {
        $this->addContentElement($cType, []);

        $this->assertSame(
            ['#0(1): Max Müllermann', '#1(2): Horst Huber'],
            $this->renderedProfiles(),
        );
    }
}
