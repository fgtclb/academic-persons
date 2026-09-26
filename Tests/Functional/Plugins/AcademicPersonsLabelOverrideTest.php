<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Plugins;

use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;

/**
 * Label overrides through `_LOCAL_LANG` reach every kind of translation the list, the detail
 * and the selected contracts plugin render, on TYPO3 v12 and v13: a label comes from
 * `locallang.xlf`, an override of the extension (`plugin.tx_academicpersons`) replaces it,
 * and an override of the plugin (`plugin.tx_academicpersons_<plugin>`) replaces both.
 *
 * TYPO3 v12 and v13 build the TypoScript path from the extension name a translation passes,
 * only lowercased, so a name with underscores read `plugin.tx_academic_persons` instead.
 *
 * They also keep the labels of a language file, overrides included, for the rest of the
 * request: once one translation has read the right path, the ones after it show its
 * overrides whatever name they pass.
 *
 * Before the change no translation of these files had the right name, so every override
 * case failed on its own. Since then, a case that is not the first translation of its file
 * in the request would pass even if its own call lost the name again; the check of the
 * extension names of all translations holds those calls.
 *
 * The shipped templates are rendered, not the simplified ones of `EXT:test_plugin_templates`
 * the other plugin tests load. The detail plugin renders Max Müllermann, whose contract has a
 * room and a business address; the list has no profile at all; the selected contracts show
 * the organisational unit of each contract.
 */
final class AcademicPersonsLabelOverrideTest extends AbstractAcademicPersonsTestCase
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
                // The configuration the cHash of the detail link below was calculated with.
                'cacheHash' => [
                    'requireCacheHashPresenceParameters' => ['value', 'testing[value]', 'tx_testing_link[value]'],
                    'excludedParameters' => ['L', 'tx_testing_link[excludedValue]'],
                    'enforceValidation' => true,
                ],
            ],
        ]);
        $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
        parent::setUp();
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    private function setUpSite(string $dataSet, string $setup): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/' . $dataSet . '.csv');
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
        $connection = $this->getConnectionPool()->getConnectionForTable('sys_template');
        $template = $connection->select(['uid', 'config'], 'sys_template', ['pid' => 1])->fetchAssociative();
        $this->assertIsArray($template);
        $connection->update('sys_template', ['config' => $template['config'] . LF . $setup], ['uid' => $template['uid']]);
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
        ]);
    }

    private function render(string $plugin, string $setup): string
    {
        switch ($plugin) {
            case 'list':
                $this->setUpSite('AcademicPersonsLabelOverride/emptyList', $setup);
                $url = 'https://www.acme.com/home';
                break;
            case 'selectedcontracts':
                $this->setUpSite('AcademicPersonsContractFieldRendering/selectedContractsPage', $setup);
                $url = 'https://www.acme.com/home';
                break;
            default:
                $this->setUpSite('AcademicPersonsLabelOverride/detail', $setup);
                $url = 'https://www.acme.com/home?' . http_build_query([
                    'tx_academicpersons_detail' => [
                        'controller' => 'Profile',
                        'action' => 'detail',
                        'profile' => 1,
                    ],
                    'cHash' => '13c8ec3ab2a317651a40bd164df8a366',
                ]);
        }

        return (string)preg_replace('/\s+/', ' ', $this->renderFrontendPage($url));
    }

    /**
     * @param array<string, string> $overrides TypoScript path => label
     */
    private function localLang(string $key, array $overrides): string
    {
        $setup = '';
        foreach ($overrides as $path => $label) {
            $setup .= $path . '._LOCAL_LANG.default.' . $key . ' = ' . $label . LF;
        }
        return $setup;
    }

    /**
     * Every kind of translation the plugins render: an inline call, one inside the argument of
     * a partial with its quotes escaped, an inline call over several lines whose key is built
     * from a variable, and one in a partial whose key is built from the configured fields.
     *
     * @return \Generator<string, array{0: string, 1: string, 2: string, 3: string}>
     */
    public static function translationDataProvider(): \Generator
    {
        yield 'list, inline' => [
            'list', 'list.noProfilesFound', 'No profiles found.',
            '<p>%s</p>',
        ];
        yield 'detail, inline' => [
            'detail', 'detail.room', 'Room',
            '<b>%s:</b> B 1.23',
        ];
        yield 'detail, in the argument of a partial' => [
            'detail', 'detail.contracts', 'Contracts',
            '<h2 class=""> %s </h2>',
        ];
        yield 'detail, over several lines, key from a variable' => [
            'detail', 'detail.physicalAddress.business', 'Business',
            '<b> %s: </b>',
        ];
        yield 'selected contracts, key from a variable' => [
            'selectedcontracts', 'contracts.organisationalUnit', 'Organisational Unit',
            '<b>%s:</b>',
        ];
    }

    #[DataProvider('translationDataProvider')]
    #[Test]
    public function aPluginRendersTheLabelOfTheLanguageFile(string $plugin, string $key, string $label, string $markup): void
    {
        $this->assertStringContainsString(sprintf($markup, $label), $this->render($plugin, ''));
    }

    #[DataProvider('translationDataProvider')]
    #[Test]
    public function aPluginRendersTheLabelOfTheExtension(string $plugin, string $key, string $label, string $markup): void
    {
        $content = $this->render($plugin, $this->localLang($key, [
            'plugin.tx_academicpersons' => 'Extension label',
        ]));

        $this->assertStringContainsString(sprintf($markup, 'Extension label'), $content);
    }

    #[DataProvider('translationDataProvider')]
    #[Test]
    public function aPluginRendersTheLabelOfThePlugin(string $plugin, string $key, string $label, string $markup): void
    {
        $content = $this->render($plugin, $this->localLang($key, [
            'plugin.tx_academicpersons_' . $plugin => 'Plugin label',
        ]));

        $this->assertStringContainsString(sprintf($markup, 'Plugin label'), $content);
    }

    #[DataProvider('translationDataProvider')]
    #[Test]
    public function theLabelOfThePluginWinsOverTheOneOfTheExtension(string $plugin, string $key, string $label, string $markup): void
    {
        $content = $this->render($plugin, $this->localLang($key, [
            'plugin.tx_academicpersons' => 'Extension label',
            'plugin.tx_academicpersons_' . $plugin => 'Plugin label',
        ]));

        $this->assertStringContainsString(sprintf($markup, 'Plugin label'), $content);
        $this->assertStringNotContainsString('Extension label', $content);
    }
}
