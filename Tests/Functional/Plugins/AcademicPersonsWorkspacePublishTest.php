<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Plugins;

use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use FGTCLB\TestingHelper\FunctionalTestCase\WorkspacePublishingTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Page\CacheHashCalculator;

/**
 * A profile and a contract edited in a workspace and published still show their
 * contract, its addresses and the profile information on the detail page (ACE-907).
 *
 * Published from live, as the scheduled publishing of a workspace does, every inline
 * child used to keep a pointer to the deleted version row of its parent, so the detail
 * page showed the profile without any of them. Published from the workspace module, core
 * repaired the pointers after the swap.
 */
final class AcademicPersonsWorkspacePublishTest extends AbstractAcademicPersonsTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;
    use WorkspacePublishingTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration();
        $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
        $this->addCoreExtensionsToLoad('typo3/cms-workspaces');
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsWorkspacePublish/detailPage.csv');
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
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['BE_USER'], $GLOBALS['LANG']);
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    /**
     * @return array<string, array{bool}>
     */
    public static function publishingPlaces(): array
    {
        return [
            'published from the workspace' => [true],
            'published from live' => [false],
        ];
    }

    #[Test]
    #[DataProvider('publishingPlaces')]
    public function theDetailPageShowsTheChildrenOfAProfileAndAContractPublishedFromAWorkspace(bool $fromTheWorkspace): void
    {
        $this->runDataHandlerInWorkspace(1, [
            'tx_academicpersons_domain_model_profile' => [1 => ['first_name' => 'Annabel']],
            'tx_academicpersons_domain_model_contract' => [1 => ['position' => 'Dean']],
        ]);
        $this->publishWorkspace(1, $fromTheWorkspace);
        unset($GLOBALS['BE_USER']);

        $content = $this->renderFrontendPage($this->getDetailUrl(1));

        $this->assertStringContainsString('Annabel', $content);
        $this->assertStringContainsString('Dean', $content);
        $this->assertStringContainsString('anna@example.org', $content);
        $this->assertStringContainsString('+49 6241 509 123', $content);
        $this->assertStringContainsString('Annastreet', $content);
        $this->assertStringContainsString('Studied physics', $content);
    }

    private function getDetailUrl(int $profileUid): string
    {
        $query = http_build_query([
            'tx_academicpersons_detail' => [
                'controller' => 'Profile',
                'action' => 'detail',
                'profile' => $profileUid,
            ],
        ]);
        $cacheHash = GeneralUtility::makeInstance(CacheHashCalculator::class)->generateForParameters('id=2&' . $query);

        return 'https://www.acme.com/home?' . $query . '&cHash=' . $cacheHash;
    }
}
