<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\SiteSet;

use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Site\Set\SetDefinition;
use TYPO3\CMS\Core\Site\Set\SetRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Proves that the site sets of this extension deliver what their `config.yaml` claims.
 *
 * Both keys of a set are strings that the core resolves at runtime, and both fail
 * silently when they are wrong: `SysTemplateTreeBuilder::handleSetInclude()` and
 * `TsConfigTreeBuilder::getSitePageTsConfigTree()` `file_exists()`-guard the files they
 * read and simply continue when one is missing. A typo in `typoscript:` or in `pagets:`
 * therefore produces no error anywhere, only a site that is configured differently than
 * the integrator expects - which is the whole reason this restructuring exists.
 *
 * This extension adds one failure mode the reference implementation does not have. Its
 * six content elements share one `plugin.tx_academicpersons` block, so a component
 * folder holds nothing but an `include_static_file.txt` naming the shared folder. That
 * file is comma separated and is read by the very same code path for a set as for a
 * `sys_template` record, so a component set that delivers nothing at all is a plausible
 * outcome of getting it wrong - and an invisible one.
 *
 * The probe TypoScript renders one constant of the shared block, one value its setup
 * assigns, and one value the file that setup imports assigns, so a delivery that did not
 * happen shows up as a wrong value rather than as an exception. The `sys_template`
 * record it is imported from carries `clear = 0` on purpose: the backend button
 * "Create a root TypoScript record" writes `clear = 3`, which discards everything the
 * site sets contributed, and so does `FunctionalTestCase::setUpFrontendRootPage()`.
 */
final class SiteSetDeliveryTest extends AbstractAcademicPersonsTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    private const AGGREGATE_SET = 'fgtclb/academic-persons';
    private const COMPATIBILITY_SET = 'fgtclb/academic-persons-default';
    private const STANDALONE_SET = 'fgtclb/academic-persons-standalone';

    /**
     * The constant the probe renders, assigned by
     * `Configuration/TypoScript/Default/constants.typoscript` and by nothing else. A
     * constant that `settings.definitions.yaml` also declares would prove nothing here:
     * a site set contributes its settings as constants after the constants of its
     * `typoscript` folder, so such a value renders even when `constants.typoscript` was
     * never read.
     */
    private const SHARED_CONSTANT = '<div id="constant">EXT:academic_persons/Resources/Private/Partials/</div>';

    /**
     * A value the probe copies out of the setup of the shared block, assigned by
     * `Configuration/TypoScript/Default/setup.typoscript`.
     */
    private const SHARED_SETUP = '<div id="setup">EXT:academic_persons/Resources/Private/Templates/</div>';

    /**
     * A value assigned by the file the setup of the shared block imports. It is built
     * with `addToList`, so every additional parse of the shared block appends the same
     * three entries again - which is why this is asserted with a "contains".
     */
    private const SHARED_IMPORT = '<div id="import">detailPid,pageTitleFormat,showFields';

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

    #[Test]
    public function siteSetDeliversTheSharedTypoScript(): void
    {
        $this->setUpSite(dependencies: [self::AGGREGATE_SET]);

        $body = $this->renderFrontendPage(self::FRONTEND_PLUGIN_TEST_BASE);

        $this->assertStringContainsString(
            self::SHARED_CONSTANT,
            $body,
            'The site set did not deliver "constants.typoscript" of the shared block.',
        );
        $this->assertStringContainsString(
            self::SHARED_SETUP,
            $body,
            'The site set did not deliver "setup.typoscript" of the shared block.',
        );
    }

    /**
     * The point of the layout here: a component folder holds nothing but an
     * `include_static_file.txt` naming the shared folder, and that is what has to arrive
     * when a site depends on a single component. It is the file this extension would
     * lose its whole plugin configuration to, because an `include_static_file.txt` that
     * does not resolve includes nothing and says nothing.
     */
    #[Test]
    #[DataProvider('componentDataProvider')]
    public function componentSetDeliversTheSharedTypoScriptThroughItsIncludeStaticFile(string $set): void
    {
        $this->setUpSite(dependencies: [$set]);

        $body = $this->renderFrontendPage(self::FRONTEND_PLUGIN_TEST_BASE);

        $this->assertStringContainsString(
            self::SHARED_CONSTANT,
            $body,
            sprintf('The set "%s" did not deliver the constants of the shared block.', $set),
        );
        $this->assertStringContainsString(
            self::SHARED_SETUP,
            $body,
            sprintf('The set "%s" did not deliver the setup of the shared block.', $set),
        );
        $this->assertStringContainsString(
            self::SHARED_IMPORT,
            $body,
            sprintf('The set "%s" did not deliver what the setup of the shared block imports.', $set),
        );
    }

    /**
     * The counterpart of the two tests above for an installation without site sets. It
     * also covers `Configuration/TypoScript/Full/include_static_file.txt`, whose entries
     * are comma separated and reach nothing at all when they are written any other way.
     */
    #[Test]
    public function aggregateStaticTemplateDeliversTheSharedTypoScript(): void
    {
        $this->setUpSite(includeStaticFile: 'EXT:academic_persons/Configuration/TypoScript/Full');

        $body = $this->renderFrontendPage(self::FRONTEND_PLUGIN_TEST_BASE);

        $this->assertStringContainsString(
            self::SHARED_CONSTANT,
            $body,
            'The aggregate static template did not deliver the constants of the shared block.',
        );
        $this->assertStringContainsString(
            self::SHARED_SETUP,
            $body,
            'The aggregate static template did not deliver the setup of the shared block.',
        );
    }

    /**
     * The "standalone" flavour is the one place where a set of this extension ships
     * TypoScript of its own, and the static entry that carries the same page object has
     * to carry the plugin configuration with it - it is the only static entry that does
     * not name the shared folder directly.
     */
    #[Test]
    public function standaloneStaticTemplateDeliversTheSharedTypoScriptAndThePageObject(): void
    {
        $this->setUpSite(includeStaticFile: 'EXT:academic_persons/Configuration/TypoScript/Standalone');

        $body = $this->renderFrontendPage(self::FRONTEND_PLUGIN_TEST_BASE);

        $this->assertStringContainsString(
            self::SHARED_CONSTANT,
            $body,
            'The standalone static template did not deliver the constants of the shared block.',
        );
        $this->assertStringContainsString(
            'bootstrap.min.css',
            $body,
            'The standalone static template did not deliver the page object.',
        );
    }

    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function everythingDeliveringSetDataProvider(): \Generator
    {
        yield 'aggregate' => [self::AGGREGATE_SET];
        yield 'compatibility' => [self::COMPATIBILITY_SET];
        yield 'standalone' => [self::STANDALONE_SET];
    }

    /**
     * The other half of the delivery: the content elements are hidden for the whole
     * installation, and naming a set in the site configuration is one of the two ways to
     * bring one back. No page carries a `tsconfig_includes` entry here, so the set is the
     * only thing that can do it.
     *
     * The two compatibility sets are in the data provider because they are what existing
     * site configurations name - the dev instances of this repository among them - and
     * because a dependency that does not resolve is silent.
     */
    #[Test]
    #[DataProvider('everythingDeliveringSetDataProvider')]
    public function siteSetDeliversThePageTsConfigOfEveryComponent(string $set): void
    {
        $this->setUpSite(dependencies: [$set]);

        $pageTsConfig = BackendUtility::getPagesTSconfig(1);
        $removeItems = $this->removedContentElementTypes($pageTsConfig);
        $wizardElements = $pageTsConfig['mod.']['wizards.']['newContentElement.']['wizardItems.']['academic.']['elements.'] ?? [];

        foreach (self::componentDataProvider() as $component) {
            $this->assertNotContains(
                $component[1],
                $removeItems,
                sprintf('The set "%s" did not re-enable the content element "%s".', $set, $component[1]),
            );
            $this->assertArrayHasKey(
                $component[1] . '.',
                $wizardElements,
                sprintf('The set "%s" did not deliver the wizard entry of "%s".', $set, $component[1]),
            );
        }
    }

    /**
     * Hiding is what the always-included `Configuration/page.tsconfig` does, and it is
     * the half that is easy to get wrong in the other direction: a component whose CType
     * is missing from that file is offered everywhere and nobody notices.
     */
    #[Test]
    public function everyContentElementIsHiddenWithoutASiteSet(): void
    {
        $this->setUpSite();

        $removeItems = $this->removedContentElementTypes(BackendUtility::getPagesTSconfig(1));

        foreach (self::componentDataProvider() as $component) {
            $this->assertContains(
                $component[1],
                $removeItems,
                sprintf('The content element "%s" is offered without any site set.', $component[1]),
            );
        }
    }

    /**
     * A component set re-enables its own content element and nothing else. Without this
     * the whole per-component split is decoration: one page TSconfig file that
     * re-enabled all six would pass every other assertion here.
     */
    #[Test]
    #[DataProvider('componentDataProvider')]
    public function componentSetReEnablesItsOwnContentElementOnly(string $set, string $contentElementType): void
    {
        $this->setUpSite(dependencies: [$set]);

        $removeItems = $this->removedContentElementTypes(BackendUtility::getPagesTSconfig(1));

        $this->assertNotContains(
            $contentElementType,
            $removeItems,
            sprintf('The set "%s" did not re-enable "%s".', $set, $contentElementType),
        );
        foreach (self::componentDataProvider() as $component) {
            if ($component[1] === $contentElementType) {
                continue;
            }
            $this->assertContains(
                $component[1],
                $removeItems,
                sprintf('The set "%s" also re-enabled "%s".', $set, $component[1]),
            );
        }
    }

    /**
     * Pins the two strings the tests above depend on, and the files they point at.
     */
    #[Test]
    #[DataProvider('componentDataProvider')]
    public function componentSetPointsAtTheFilesTheStaticRegistrationUses(
        string $set,
        string $contentElementType,
        string $typoScriptPath,
        string $pageTsConfigPath,
    ): void {
        $component = $this->setRegistry()->getSet($set);

        $this->assertNotNull($component, sprintf('The set "%s" is not registered.', $set));
        $this->assertSame($typoScriptPath, $component->typoscript);
        $this->assertSame($pageTsConfigPath, $component->pagets);
        $this->assertDirectoryExists(GeneralUtility::getFileAbsFileName((string)$component->typoscript));
        $this->assertFileExists(GeneralUtility::getFileAbsFileName((string)$component->pagets));
    }

    /**
     * The aggregate carries no payload of its own on purpose: it delivers through the
     * component sets, and a `typoscript:` of its own would parse the same files twice.
     */
    #[Test]
    public function aggregateSetDependsOnEveryComponentAndCarriesNoPayload(): void
    {
        $aggregate = $this->setRegistry()->getSet(self::AGGREGATE_SET);

        $this->assertNotNull($aggregate, sprintf('The set "%s" is not registered.', self::AGGREGATE_SET));
        foreach (self::componentDataProvider() as $component) {
            $this->assertContains($component[0], $aggregate->dependencies);
        }
        $this->assertSetCarriesNoPayload($aggregate);
    }

    /**
     * The name this extension published before the split. Two site configurations in this
     * repository depend on it by that exact string, and a set that is not found is not an
     * error - the site simply gets nothing.
     */
    #[Test]
    public function compatibilitySetDelegatesToTheAggregate(): void
    {
        $compatibility = $this->setRegistry()->getSet(self::COMPATIBILITY_SET);

        $this->assertNotNull($compatibility, sprintf('The set "%s" is not registered.', self::COMPATIBILITY_SET));
        $this->assertSame([self::AGGREGATE_SET], $compatibility->dependencies);
        $this->assertSetCarriesNoPayload($compatibility);
    }

    /**
     * The standalone set is the one exception to "an aggregate carries no payload": it
     * ships the page object, and only the page object.
     */
    #[Test]
    public function standaloneSetAddsThePageObjectToTheAggregate(): void
    {
        $standalone = $this->setRegistry()->getSet(self::STANDALONE_SET);

        $this->assertNotNull($standalone, sprintf('The set "%s" is not registered.', self::STANDALONE_SET));
        $this->assertSame([self::AGGREGATE_SET], $standalone->dependencies);
        $this->assertSame(
            'EXT:academic_persons/Configuration/TypoScript/StandalonePage/',
            $standalone->typoscript,
        );
        $this->assertFileExists(
            GeneralUtility::getFileAbsFileName((string)$standalone->typoscript) . 'setup.typoscript',
        );
        $this->assertFileDoesNotExist(
            GeneralUtility::getFileAbsFileName((string)$standalone->typoscript) . 'include_static_file.txt',
            'The standalone set includes the shared block a second time.',
        );
    }

    /**
     * The settings of this extension belong to the shared block and are declared with the
     * aggregate set, once. They used to exist twice, byte for byte, and every default has
     * to stay identical to what `constants.typoscript` assigns for the same path.
     */
    #[Test]
    public function settingsAreDeclaredWithTheAggregateSetOnly(): void
    {
        $aggregate = $this->setRegistry()->getSet(self::AGGREGATE_SET);
        $this->assertNotNull($aggregate);

        $definitions = [];
        foreach ($aggregate->settingsDefinitions as $definition) {
            $definitions[$definition->key] = $definition->default;
        }

        $this->assertSame(
            [
                'plugin.tx_academicpersons.detailPid' => 0,
                'plugin.tx_academicpersons.demand.groupBy' => 'lastNameAlpha',
                'plugin.tx_academicpersons.demand.sortBy' => 'lastName',
                'plugin.tx_academicpersons.demand.sortByDirection' => 'asc',
                'plugin.tx_academicpersons.pagination.resultsPerPage' => 1,
                'plugin.tx_academicpersons.pagination.numberOfLinks' => 5,
            ],
            $definitions,
        );

        foreach ([self::COMPATIBILITY_SET, self::STANDALONE_SET, 'fgtclb/academic-persons-list'] as $setName) {
            $set = $this->setRegistry()->getSet($setName);
            $this->assertNotNull($set);
            $this->assertSame(
                [],
                $set->settingsDefinitions,
                sprintf('The set "%s" declares settings of its own.', $setName),
            );
        }
    }

    private function setRegistry(): SetRegistry
    {
        $setRegistry = $this->get(SetRegistry::class);
        $this->assertInstanceOf(SetRegistry::class, $setRegistry);

        return $setRegistry;
    }

    /**
     * @param array<string, mixed> $pageTsConfig
     * @return list<string>
     */
    private function removedContentElementTypes(array $pageTsConfig): array
    {
        return GeneralUtility::trimExplode(
            ',',
            (string)($pageTsConfig['TCEFORM.']['tt_content.']['CType.']['removeItems'] ?? ''),
            true,
        );
    }

    /**
     * A set that declares neither key does not get `null`: the core defaults both to the
     * set folder itself (`YamlSetDefinitionProvider::createDefinition()`), and reads
     * whatever it finds there. "Carries no payload" therefore means the set folder holds
     * none of the four files the two mechanisms look for.
     */
    private function assertSetCarriesNoPayload(SetDefinition $set): void
    {
        $typoScriptPath = rtrim(GeneralUtility::getFileAbsFileName((string)$set->typoscript), '/') . '/';
        foreach (['constants.typoscript', 'setup.typoscript', 'include_static_file.txt'] as $fileName) {
            $this->assertFileDoesNotExist(
                $typoScriptPath . $fileName,
                sprintf('The set "%s" carries a payload of its own: %s', $set->name, $fileName),
            );
        }
        $this->assertFileDoesNotExist(
            GeneralUtility::getFileAbsFileName((string)$set->pagets),
            sprintf('The set "%s" carries a page TSconfig of its own.', $set->name),
        );
    }

    /**
     * The site identifier is derived from what the site is configured with, and that is
     * not cosmetic. `TsConfigTreeBuilder::getSitePageTsConfigTree()` caches the page
     * TSconfig a site's sets deliver under the site identifier alone, and the test
     * instance keeps that cache for the whole class. Reusing one identifier for
     * differently configured sites therefore answers the second test with the result of
     * the first - which looks exactly like a set that delivers too much.
     *
     * @param list<string> $dependencies Site sets the site configuration names.
     * @param string $includeStaticFile Static template the `sys_template` record selects.
     */
    private function setUpSite(array $dependencies = [], string $includeStaticFile = ''): void
    {
        $identifier = 'acme-' . substr(md5(implode(',', $dependencies) . '|' . $includeStaticFile), 0, 10);

        $this->importCSVDataSet(__DIR__ . '/Fixtures/SiteSetDelivery/pages.csv');
        $this->getConnectionPool()->getConnectionForTable('sys_template')->insert(
            'sys_template',
            [
                'pid' => 1,
                'root' => 1,
                // Not "3": a clear flag discards everything the site sets contributed.
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
                base: self::FRONTEND_PLUGIN_TEST_BASE,
                additionalRootConfiguration: $dependencies === [] ? [] : ['dependencies' => $dependencies],
            ),
            languages: [
                $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
            ],
        );
    }
}
