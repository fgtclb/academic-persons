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

/**
 * Label overrides through `_LOCAL_LANG` reach every kind of translation the list and the
 * detail plugin render, on every supported core version: a label comes from `locallang.xlf`,
 * an override of the extension (`plugin.tx_academicpersons`) replaces it, and an override of
 * the plugin (`plugin.tx_academicpersons_<plugin>`) replaces both.
 *
 * Up to TYPO3 v13 the core builds the TypoScript path from the extension name a translation
 * passes, only lowercased, so a name with underscores read `plugin.tx_academic_persons`
 * instead. TYPO3 v14 strips the underscores itself.
 *
 * Up to TYPO3 v13 the core also keeps the labels of a language file, overrides included,
 * for the rest of the request: once one translation has read the right path, the ones after
 * it show its overrides whatever name they pass.
 *
 * Before the change no translation of these files had the right name, so every override
 * case failed on its own. Since then, a case that is not the first translation of its file
 * in the request would pass on v13 even if its own call lost the name again, and TYPO3 v14
 * strips the underscores and falls back to the name of the plugin request, so a lost or
 * underscored name reads the same paths there. The check of the extension names of all
 * translations holds those calls.
 *
 * The list is on `/home`: the view modes fixture with the switch enabled, or a list without
 * any profile for its empty state. The detail plugin renders the shipped public profile of
 * Max Müllermann, whose contract has a business address.
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
                    'EXT:academic_persons/Tests/Functional/Plugins/Fixtures/TypoScript/Constants/DetailPage.typoscript',
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

    /**
     * Save the view mode switch into the FlexForm of the list element, as the backend does.
     */
    private function enableViewModeSwitch(): void
    {
        $connection = $this->getConnectionPool()->getConnectionForTable('tt_content');
        $flexForm = $connection->select(['pi_flexform'], 'tt_content', ['uid' => 1])->fetchOne();
        $this->assertIsString($flexForm);
        $connection->update(
            'tt_content',
            ['pi_flexform' => str_replace(
                '</language>',
                '<field index="settings.viewMode.enabled"><value index="vDEF">1</value></field>'
                . '<field index="settings.viewMode.default"><value index="vDEF">list</value></field></language>',
                $flexForm,
            )],
            ['uid' => 1],
        );
    }

    private function render(string $page, string $setup): string
    {
        switch ($page) {
            case 'list':
                $this->setUpSite('AcademicPersonsListViewModes/records', $setup);
                $this->enableViewModeSwitch();
                $url = 'https://www.acme.com/home';
                break;
            case 'empty list':
                $this->setUpSite('AcademicPersonsLabelOverride/emptyList', $setup);
                $url = 'https://www.acme.com/home';
                break;
            default:
                $this->setUpSite('AcademicPersonsPublicProfilePlugin/shippedLayout', $setup);
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
     * Every kind of translation the two plugins render: an inline call in an attribute, one
     * whose key is a variable, one with a `default`, a tag, and a tag over several lines
     * whose key is built from a variable.
     *
     * @return \Generator<string, array{0: string, 1: string, 2: string, 3: string, 4: string}>
     */
    public static function translationDataProvider(): \Generator
    {
        yield 'list, inline in an attribute' => [
            'list', 'list', 'list.alphabetFilter.navigation', 'Filter by initial of the last name',
            'alphabetical-pagination mb-4" aria-label="%s" >',
        ];
        yield 'list, inline with a default' => [
            'list', 'list', 'list.viewMode.table', 'Table',
            'class="nav-link" rel="nofollow">%s</a>',
        ];
        yield 'list, inline, key from a variable' => [
            'empty list', 'list', 'list.noProfilesFound', 'No profiles found.',
            '<p class="academic-persons-empty-state">%s</p>',
        ];
        yield 'detail, tag' => [
            'detail', 'detail', 'detail.contact', 'Contact',
            '<h3 class="academic-persons-detail__contact-heading"> %s </h3>',
        ];
        yield 'detail, tag over several lines, key from a variable' => [
            'detail', 'detail', 'detail.physicalAddress.business', 'Business',
            '<span class="academic-persons-detail__contact-type"> %s </span>',
        ];
    }

    #[DataProvider('translationDataProvider')]
    #[Test]
    public function aPluginRendersTheLabelOfTheLanguageFile(string $page, string $plugin, string $key, string $label, string $markup): void
    {
        $this->assertStringContainsString(sprintf($markup, $label), $this->render($page, ''));
    }

    #[DataProvider('translationDataProvider')]
    #[Test]
    public function aPluginRendersTheLabelOfTheExtension(string $page, string $plugin, string $key, string $label, string $markup): void
    {
        $content = $this->render($page, $this->localLang($key, [
            'plugin.tx_academicpersons' => 'Extension label',
        ]));

        $this->assertStringContainsString(sprintf($markup, 'Extension label'), $content);
    }

    #[DataProvider('translationDataProvider')]
    #[Test]
    public function aPluginRendersTheLabelOfThePlugin(string $page, string $plugin, string $key, string $label, string $markup): void
    {
        $content = $this->render($page, $this->localLang($key, [
            'plugin.tx_academicpersons_' . $plugin => 'Plugin label',
        ]));

        $this->assertStringContainsString(sprintf($markup, 'Plugin label'), $content);
    }

    #[DataProvider('translationDataProvider')]
    #[Test]
    public function theLabelOfThePluginWinsOverTheOneOfTheExtension(string $page, string $plugin, string $key, string $label, string $markup): void
    {
        $content = $this->render($page, $this->localLang($key, [
            'plugin.tx_academicpersons' => 'Extension label',
            'plugin.tx_academicpersons_' . $plugin => 'Plugin label',
        ]));

        $this->assertStringContainsString(sprintf($markup, 'Plugin label'), $content);
        $this->assertStringNotContainsString('Extension label', $content);
    }
}
