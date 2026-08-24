<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\SiteSet;

use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Shared scaffolding of the two delivery test classes: the site, the probe TypoScript
 * and the `sys_template` record they read it from.
 *
 * The classes are split by core version rather than by mechanism because only one of
 * the two mechanisms exists on both: site sets arrived in TYPO3 v13.1
 * (Feature: #103437), while the static template and the page field `Page TSconfig`
 * work identically on v12 and v13. `StaticTemplateDeliveryTest` therefore runs on both
 * versions and `Core13\SiteSet\SiteSetDeliveryTest` on v13 only.
 *
 * This extension adds one failure mode the reference implementation does not have. Its
 * six content elements share one `plugin.tx_academicpersons` block, so a component
 * folder holds nothing but an `include_static_file.txt` naming the shared folder. That
 * file is comma separated and is read by the very same code path for a set as for a
 * `sys_template` record, so a component that delivers nothing at all is a plausible
 * outcome of getting it wrong - and an invisible one.
 */
abstract class AbstractDeliveryTestCase extends AbstractAcademicPersonsTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    protected const AGGREGATE_SET = 'fgtclb/academic-persons';
    protected const COMPATIBILITY_SET = 'fgtclb/academic-persons-default';
    protected const STANDALONE_SET = 'fgtclb/academic-persons-standalone';

    /**
     * The constant the probe renders, assigned by
     * `Configuration/TypoScript/Default/constants.typoscript` and by nothing else. A
     * constant that `settings.definitions.yaml` also declares would prove nothing here:
     * a site set contributes its settings as constants after the constants of its
     * `typoscript` folder, so such a value renders even when `constants.typoscript` was
     * never read.
     */
    protected const SHARED_CONSTANT = '<div id="constant">EXT:academic_persons/Resources/Private/Partials/</div>';

    /**
     * A value the probe copies out of the setup of the shared block, assigned by
     * `Configuration/TypoScript/Default/setup.typoscript`.
     */
    protected const SHARED_SETUP = '<div id="setup">EXT:academic_persons/Resources/Private/Templates/</div>';

    /**
     * A value assigned by the file the setup of the shared block imports. It is built
     * with `addToList`, so every additional parse of the shared block appends the same
     * three entries again - which is why this is asserted with a "contains".
     */
    protected const SHARED_IMPORT = '<div id="import">detailPid,pageTitleFormat,showFields';

    /**
     * @return \Generator<string, array{0: string, 1: string, 2: string, 3: string}>
     */
    public static function componentDataProvider(): \Generator
    {
        yield 'profile list' => [
            'fgtclb/academic-persons-list',
            'academicpersons_list',
            'EXT:academic_persons/Configuration/TypoScript/List/',
            'EXT:academic_persons/Configuration/TSconfig/List/page.tsconfig',
        ];
        yield 'profile list and detail' => [
            'fgtclb/academic-persons-list-and-detail',
            'academicpersons_listanddetail',
            'EXT:academic_persons/Configuration/TypoScript/ListAndDetail/',
            'EXT:academic_persons/Configuration/TSconfig/ListAndDetail/page.tsconfig',
        ];
        yield 'profile detail' => [
            'fgtclb/academic-persons-detail',
            'academicpersons_detail',
            'EXT:academic_persons/Configuration/TypoScript/Detail/',
            'EXT:academic_persons/Configuration/TSconfig/Detail/page.tsconfig',
        ];
        yield 'profile card' => [
            'fgtclb/academic-persons-card',
            'academicpersons_card',
            'EXT:academic_persons/Configuration/TypoScript/Card/',
            'EXT:academic_persons/Configuration/TSconfig/Card/page.tsconfig',
        ];
        yield 'selected profiles' => [
            'fgtclb/academic-persons-selected-profiles',
            'academicpersons_selectedprofiles',
            'EXT:academic_persons/Configuration/TypoScript/SelectedProfiles/',
            'EXT:academic_persons/Configuration/TSconfig/SelectedProfiles/page.tsconfig',
        ];
        yield 'selected contracts' => [
            'fgtclb/academic-persons-selected-contracts',
            'academicpersons_selectedcontracts',
            'EXT:academic_persons/Configuration/TypoScript/SelectedContracts/',
            'EXT:academic_persons/Configuration/TSconfig/SelectedContracts/page.tsconfig',
        ];
    }

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration();
        parent::setUp();
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    /**
     * The site identifier is derived from what the site is configured with, and that is
     * not cosmetic. `TsConfigTreeBuilder::getSitePageTsConfigTree()` caches the page
     * TSconfig a site's sets deliver under the site identifier alone, and the test
     * instance keeps that cache for the whole class. Reusing one identifier for
     * differently configured sites therefore answers the second test with the result of
     * the first - which looks exactly like a set that delivers too much.
     *
     * @param list<string> $dependencies Site sets the site configuration names. Ignored
     *        by TYPO3 v12, which has no set API and stores the key verbatim.
     * @param string $includeStaticFile Static template the `sys_template` record selects.
     * @param string $pageTsConfigFile Page TSconfig file the root page selects in
     *        `tsconfig_includes` - the only way to a component file on TYPO3 v12.
     */
    protected function setUpSite(
        array $dependencies = [],
        string $includeStaticFile = '',
        string $pageTsConfigFile = '',
    ): void {
        $identifier = 'acme-' . substr(
            md5(implode(',', $dependencies) . '|' . $includeStaticFile . '|' . $pageTsConfigFile),
            0,
            10,
        );

        $this->importCSVDataSet(__DIR__ . '/Fixtures/SiteSetDelivery/pages.csv');
        if ($pageTsConfigFile !== '') {
            $this->getConnectionPool()->getConnectionForTable('pages')->update(
                'pages',
                ['tsconfig_includes' => $pageTsConfigFile],
                ['uid' => 1],
            );
        }
        $this->getConnectionPool()->getConnectionForTable('sys_template')->insert(
            'sys_template',
            [
                'pid' => 1,
                'root' => 1,
                // Not "3": a clear flag discards everything the site sets contributed,
                // and that is what "setUpFrontendRootPage()" writes.
                'clear' => 0,
                'title' => 'Probe',
                'constants' => '',
                'config' => '@import \'EXT:academic_persons/Tests/Functional/SiteSet/Fixtures/TypoScript/Probe.typoscript\'',
                'include_static_file' => $includeStaticFile,
            ],
        );
        $this->writeSiteConfiguration(
            identifier: $identifier,
            site: $this->buildSiteConfiguration(
                rootPageId: 1,
                base: $this->frontendPluginTestBase(),
                additionalRootConfiguration: $dependencies === [] ? [] : ['dependencies' => $dependencies],
            ),
            languages: [
                $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
            ],
        );
    }

    /**
     * @param array<string, mixed> $pageTsConfig
     * @return list<string>
     */
    protected function removedContentElementTypes(array $pageTsConfig): array
    {
        return $this->trimList($pageTsConfig['TCEFORM.']['tt_content.']['CType.']['removeItems'] ?? '');
    }

    /**
     * @return list<string>
     */
    protected function trimList(mixed $value): array
    {
        return GeneralUtility::trimExplode(',', (string)$value, true);
    }
}
