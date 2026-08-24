<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Core13\SiteSet;

use FGTCLB\AcademicPersons\Tests\Functional\SiteSet\AbstractDeliveryTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
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
 * The class lives in `Tests/Functional/Core13/` rather than carrying nothing but its
 * `not-core-12` group, because `SetRegistry` and `SetDefinition` do not exist on TYPO3
 * v12 at all. The group keeps PHPUnit from running the class there; it does nothing for
 * PHPStan, which analyses `packages/` wholesale against the installed core - and
 * `Build/phpstan/Core12/phpstan.neon` excludes the `Core12` test folder through a glob
 * that allows exactly one directory level between `Tests` and `Core12`.
 *
 * The static half of the same contract is asserted by `SiteSet\StaticTemplateDeliveryTest`,
 * which runs on both core versions.
 */
#[Group('not-core-12')]
final class SiteSetDeliveryTest extends AbstractDeliveryTestCase
{
    #[Test]
    public function siteSetDeliversTheSharedTypoScript(): void
    {
        $this->setUpSite(dependencies: [self::AGGREGATE_SET]);

        $body = $this->renderFrontendPage($this->frontendPluginTestBase());

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

        $body = $this->renderFrontendPage($this->frontendPluginTestBase());

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
     * site configurations name, and because a dependency that does not resolve is silent.
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
     * The name this extension published before the split. Site configurations depend on
     * it by that exact string, and a set that is not found is not an error - the site
     * simply gets nothing.
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
}
