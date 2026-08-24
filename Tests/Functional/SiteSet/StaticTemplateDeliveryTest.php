<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\SiteSet;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * The half of the delivery contract that holds on both supported core versions.
 *
 * Site sets do not exist on TYPO3 v12 - they arrived in v13.1 (Feature: #103437) - so
 * on that version the static template, the auto-included
 * `Configuration/page.tsconfig` and the page field `Page TSconfig` are the *only*
 * mechanisms there are. The site-set half of the same contract is asserted by
 * `Core13\SiteSet\SiteSetDeliveryTest`, which runs on v13 alone.
 *
 * The probe TypoScript renders one constant of the shared block, one value its setup
 * assigns and one value the file that setup imports assigns, so a delivery that did not
 * happen shows up as a wrong value rather than as an exception. The `sys_template`
 * record it is imported from carries `clear = 0` on purpose: the backend button
 * "Create a root TypoScript record" writes `clear = 3`, which discards everything the
 * site sets contributed, and so does `FunctionalTestCase::setUpFrontendRootPage()`.
 */
final class StaticTemplateDeliveryTest extends AbstractDeliveryTestCase
{
    /**
     * Covers `Configuration/TypoScript/Full/include_static_file.txt`, whose entries are
     * comma separated and reach nothing at all when they are written any other way.
     */
    #[Test]
    public function aggregateStaticTemplateDeliversTheSharedTypoScript(): void
    {
        $this->setUpSite(includeStaticFile: 'EXT:academic_persons/Configuration/TypoScript/Full');

        $body = $this->renderFrontendPage($this->frontendPluginTestBase());

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
        $this->assertStringContainsString(
            self::SHARED_IMPORT,
            $body,
            'The aggregate static template did not deliver what the setup of the shared block imports.',
        );
    }

    /**
     * The point of the layout here: a component folder holds nothing but an
     * `include_static_file.txt` naming the shared folder, and that is what has to arrive
     * when an installation selects a single component. It is the file this extension
     * would lose its whole plugin configuration to, because an `include_static_file.txt`
     * that does not resolve includes nothing and says nothing.
     *
     * @param string $typoScriptPath The trailing slash is what the set key carries; a
     *        `sys_template` record stores the folder without it.
     */
    #[Test]
    #[DataProvider('componentDataProvider')]
    public function componentStaticTemplateDeliversTheSharedTypoScript(
        string $set,
        string $contentElementType,
        string $typoScriptPath,
    ): void {
        $this->setUpSite(includeStaticFile: rtrim($typoScriptPath, '/'));

        $body = $this->renderFrontendPage($this->frontendPluginTestBase());

        $this->assertStringContainsString(
            self::SHARED_CONSTANT,
            $body,
            sprintf('The static template "%s" did not deliver the constants of the shared block.', $typoScriptPath),
        );
        $this->assertStringContainsString(
            self::SHARED_SETUP,
            $body,
            sprintf('The static template "%s" did not deliver the setup of the shared block.', $typoScriptPath),
        );
    }

    /**
     * The "standalone" flavour is the one place where this extension ships a page object
     * of its own, and the static entry that carries it has to carry the plugin
     * configuration with it - it is the only static entry that does not name the shared
     * folder directly.
     */
    #[Test]
    public function standaloneStaticTemplateDeliversTheSharedTypoScriptAndThePageObject(): void
    {
        $this->setUpSite(includeStaticFile: 'EXT:academic_persons/Configuration/TypoScript/Standalone');

        $body = $this->renderFrontendPage($this->frontendPluginTestBase());

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
     * Hiding is what the always-included `Configuration/page.tsconfig` does, and it is
     * the half that is easy to get wrong in the other direction: a component whose CType
     * is missing from that file is offered everywhere and nobody notices. It is also
     * what makes the re-enable assertions below able to fail at all - they check that a
     * content element is absent from `removeItems`, and an empty list satisfies that
     * just as well as a correct one.
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
                sprintf('The content element "%s" is offered without any configuration enabling it.', $component[1]),
            );
        }
    }

    /**
     * The aggregate page TSconfig file is what the migration instruction of the Breaking
     * changelog entry tells integrators to select, and on TYPO3 v12 it is the only way
     * to get the content elements back at all.
     */
    #[Test]
    public function aggregatePageTsConfigFileReEnablesEveryComponent(): void
    {
        $this->setUpSite(pageTsConfigFile: 'EXT:academic_persons/Configuration/TSconfig/Full/page.tsconfig');

        $pageTsConfig = BackendUtility::getPagesTSconfig(1);
        $removeItems = $this->removedContentElementTypes($pageTsConfig);
        $wizardElements = $pageTsConfig['mod.']['wizards.']['newContentElement.']['wizardItems.']['academic.']['elements.'] ?? [];

        foreach (self::componentDataProvider() as $component) {
            $this->assertNotContains(
                $component[1],
                $removeItems,
                sprintf('The aggregate page TSconfig did not re-enable "%s".', $component[1]),
            );
            $this->assertArrayHasKey(
                $component[1] . '.',
                $wizardElements,
                sprintf('The aggregate page TSconfig did not deliver the wizard entry of "%s".', $component[1]),
            );
        }
    }

    /**
     * A component file re-enables its own content element and nothing else. Without this
     * the whole per-component split is decoration: one page TSconfig file that
     * re-enabled all six would pass every other assertion here.
     */
    #[Test]
    #[DataProvider('componentDataProvider')]
    public function componentPageTsConfigFileReEnablesItsOwnContentElementOnly(
        string $set,
        string $contentElementType,
        string $typoScriptPath,
        string $pageTsConfigPath,
    ): void {
        $this->setUpSite(pageTsConfigFile: $pageTsConfigPath);

        $removeItems = $this->removedContentElementTypes(BackendUtility::getPagesTSconfig(1));

        $this->assertNotContains(
            $contentElementType,
            $removeItems,
            sprintf('The file "%s" did not re-enable "%s".', $pageTsConfigPath, $contentElementType),
        );
        foreach (self::componentDataProvider() as $component) {
            if ($component[1] === $contentElementType) {
                continue;
            }
            $this->assertContains(
                $component[1],
                $removeItems,
                sprintf('The file "%s" also re-enabled "%s".', $pageTsConfigPath, $component[1]),
            );
        }
    }

    /**
     * `mod.wizards.newContentElement.wizardItems.<group>.show` is what TYPO3 v12 gates
     * every wizard element on (`NewContentElementController::getWizards()`), so an
     * element definition that arrives without its `show` entry is defined and never
     * offered. TYPO3 v13 builds the wizard from TCA and ignores the key - which is
     * exactly why it is easy to drop while it is still load-bearing on the older
     * version this branch supports.
     */
    #[Test]
    #[DataProvider('componentDataProvider')]
    public function componentPageTsConfigFileShowsItsOwnWizardElementOnly(
        string $set,
        string $contentElementType,
        string $typoScriptPath,
        string $pageTsConfigPath,
    ): void {
        $this->setUpSite(pageTsConfigFile: $pageTsConfigPath);

        $group = BackendUtility::getPagesTSconfig(1)['mod.']['wizards.']['newContentElement.']['wizardItems.']['academic.'] ?? [];
        $shown = $this->trimList($group['show'] ?? '');

        $this->assertSame(
            [$contentElementType],
            $shown,
            sprintf('The file "%s" does not show exactly its own wizard element.', $pageTsConfigPath),
        );
        $this->assertArrayHasKey(
            $contentElementType . '.',
            $group['elements.'] ?? [],
            sprintf('The file "%s" did not deliver the wizard element definition.', $pageTsConfigPath),
        );
    }
}
