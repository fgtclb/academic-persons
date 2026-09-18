<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Plugins;

use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequestContext;

/**
 * Renders the shipped templates of this extension - the contract field partial through the
 * selected contracts plugin, and the profile detail template through the detail plugin.
 *
 * Every other plugin test of this extension loads `EXT:test_plugin_templates`, whose
 * simplified templates carry no contract fields at all, so this class is the one place the
 * shipped output is asserted.
 */
final class AcademicPersonsContractFieldRenderingTest extends AbstractAcademicPersonsTestCase
{
    use SiteBasedTestTrait;

    protected array $configurationToUseInTestInstance = [
        'SYS' => [
            'encryptionKey' => '4408d27a916d51e624b69af3554f516dbab61037a9f7b9fd6f81b4d3bedeccb6',
            'features' => [
                'subrequestPageErrors' => true,
            ],
        ],
        'FE' => [
            'cacheHash' => [
                'requireCacheHashPresenceParameters' => ['value', 'testing[value]', 'tx_testing_link[value]'],
                'excludedParameters' => ['L', 'tx_testing_link[excludedValue]'],
                'enforceValidation' => true,
            ],
            'debug' => false,
        ],
    ];

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    protected function setUp(): void
    {
        $this->coreExtensionsToLoad = array_unique([
            ...array_values($this->coreExtensionsToLoad),
            ...array_values([
                'typo3/cms-fluid-styled-content',
            ]),
        ]);
        parent::setUp();
    }

    protected function tearDown(): void
    {
        GeneralUtility::rmdir($this->instancePath . '/typo3conf/sites', true);
        parent::tearDown();
    }

    private function setUpTestCase(string $dataSet): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsContractFieldRendering/' . $dataSet . '.csv');
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
        $this->writeSiteConfiguration(
            identifier: 'acme',
            site: $this->buildSiteConfiguration(
                rootPageId: 1,
                base: 'https://www.acme.com/',
            ),
            languages: [
                $this->buildDefaultLanguageConfiguration(
                    identifier: 'EN',
                    base: '/',
                ),
            ],
        );
    }

    private function renderPage(string $url): string
    {
        $response = $this->executeFrontendSubRequest(new InternalRequest($url), new InternalRequestContext());
        $this->assertSame(200, $response->getStatusCode());

        return (string)$response->getBody();
    }

    private function renderSelectedContracts(): string
    {
        $this->setUpTestCase('selectedContractsPage');

        return $this->renderPage('https://www.acme.com/home');
    }

    private function renderProfileDetail(): string
    {
        $this->setUpTestCase('detailPage');

        return $this->renderPage('https://www.acme.com/home?' . http_build_query([
            'tx_academicpersons_detail' => [
                'controller' => 'Profile',
                'action' => 'detail',
                'profile' => 1,
            ],
            'cHash' => '13c8ec3ab2a317651a40bd164df8a366',
        ]));
    }

    /**
     * `contracts.organisationalUnit` is one of the items the "Show fields" selector offers,
     * and it is a relation: the partial has to render a property of the unit rather than the
     * object - the defect the contract location had before ACE-317, and the reason the row
     * used to render as a label with nothing behind it.
     */
    #[Test]
    public function selectedContractsPluginRendersTheOrganisationalUnitOfAContract(): void
    {
        $content = $this->renderSelectedContracts();

        $this->assertStringContainsString('Organisational Unit', $content);
        $this->assertStringContainsString('Institute of Applied Physics', $content);
    }

    /**
     * `displayText` is the field meant for output and `unitName` the one an import fills
     * reliably, so the second is what a unit without a display text renders.
     */
    #[Test]
    public function selectedContractsPluginFallsBackToTheUnitNameWithoutADisplayText(): void
    {
        $this->assertStringContainsString('Faculty of Engineering', $this->renderSelectedContracts());
    }

    /**
     * The third contract has no unit, and the row of a field a contract does not carry is not
     * rendered at all - not as an empty label either.
     */
    #[Test]
    public function selectedContractsPluginRendersNoUnitRowForAContractWithoutOne(): void
    {
        $content = $this->renderSelectedContracts();

        $this->assertStringContainsString('Erika', $content);
        $this->assertSame(2, substr_count($content, 'Organisational Unit'));
    }

    /**
     * A `tel:` URI carries no spaces, while the stored number is written for a reader.
     */
    #[Test]
    public function selectedContractsPluginRendersADialablePhoneNumberTarget(): void
    {
        $content = $this->renderSelectedContracts();

        $this->assertStringContainsString('href="tel:+496241509123"', $content);
        $this->assertStringContainsString('>+49 6241 509 123</a>', $content);
    }

    /**
     * The profile detail template builds a `tel:` target of its own, and it is the second
     * place that has to build it the same way.
     */
    #[Test]
    public function profileDetailRendersADialablePhoneNumberTarget(): void
    {
        $content = $this->renderProfileDetail();

        $this->assertStringContainsString('href="tel:+496241509123"', $content);
        $this->assertStringContainsString('+49 6241 509 123', $content);
    }
}
