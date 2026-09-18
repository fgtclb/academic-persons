<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Plugins;

use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;

/**
 * Renders the `academicpersons_card` plugin in the frontend.
 *
 * Unlike the other plugin tests of this extension this one renders the shipped templates
 * rather than the simplified ones of `EXT:test_plugin_templates`: that fixture extension
 * carries no `Profile/Card.html`, and the point of the test is what a visitor gets — the
 * card wrapper, the `Profile/Item` partial and the contract fields it renders.
 *
 * `cardAction()` only queries when a profile list is configured, and it builds its demand
 * from three settings: the profile list, the hidden record option and the fallback flag.
 * It is the one action of this controller without a storage page restriction.
 *
 * The content element header is not part of the template. It comes from
 * `lib.contentElement`, and on TYPO3 v14 that renders through the `record` view variable,
 * which is what the header assertion covers.
 *
 * Multi language is out of scope here and covered by
 * {@see AcademicPersonsCardPluginLocalizationTest} instead, which is also where the
 * `@todo` claiming this action is broken in multi language sites was settled.
 */
final class AcademicPersonsCardPluginTest extends AbstractAcademicPersonsTestCase
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
        parent::setUp();
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    /**
     * @param list<string> $additionalConstantFiles Constants loaded after the shipped ones.
     */
    private function setUpTestCase(string $dataSet, array $additionalConstantFiles = []): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsCardPlugin/' . $dataSet . '.csv');
        $this->setUpFrontendRootPage(
            pageId: 1,
            typoScriptFiles: [
                'constants' => array_merge(
                    [
                        'EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript',
                        'EXT:academic_persons/Configuration/TypoScript/Default/constants.typoscript',
                    ],
                    $additionalConstantFiles,
                ),
                'setup' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript',
                    'EXT:academic_persons/Configuration/TypoScript/Default/setup.typoscript',
                    'EXT:academic_persons/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/Rendering.typoscript',
                ],
            ],
        );
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration(
                identifier: 'EN',
                base: '/',
            ),
        ]);
    }

    private function renderHomePage(): string
    {
        return $this->renderFrontendPage('https://www.acme.com/home');
    }

    private function setContentElementHeader(string $header): void
    {
        $this->getConnectionPool()
            ->getConnectionForTable('tt_content')
            ->update('tt_content', ['header' => $header], ['uid' => 1]);
    }

    /**
     * The item partial composes the heading from first, middle and last name, so an empty
     * middle name leaves two spaces in the markup. Matching on `\s+` asserts the rendered
     * name without depending on that spacing.
     */
    private function assertRendersProfileName(string $content, string $first, string $last): void
    {
        $this->assertMatchesRegularExpression(
            sprintf('#%s\s+%s#u', preg_quote($first, '#'), preg_quote($last, '#')),
            $content,
        );
    }

    #[Test]
    public function cardPluginRendersTheSelectedProfiles(): void
    {
        $this->setUpTestCase('cardPage');

        $content = $this->renderHomePage();
        $this->assertStringContainsString('academic-persons-card', $content);
        $this->assertStringContainsString('academic-persons-item', $content);
        $this->assertRendersProfileName($content, 'Max', 'Müllermann');
        $this->assertRendersProfileName($content, 'Horst', 'Huber');
        // Not part of the configured selection.
        $this->assertStringNotContainsString('Erika', $content);
    }

    #[Test]
    public function cardPluginRendersSelectedProfilesInSelectedOrder(): void
    {
        $this->setUpTestCase('cardPage');

        // The FlexForm selection is `2,1` - Horst before Max, the reverse of the uid
        // order. The query behind it matches `uid IN (...)` without any ordering, so
        // the sequence has to be restored from the selection (ACE-330).
        $content = $this->renderHomePage();
        $horst = strpos($content, 'Horst');
        $max = strpos($content, 'Max');
        $this->assertIsInt($horst);
        $this->assertIsInt($max);
        $this->assertLessThan($max, $horst, 'Horst is selected first and has to render first.');
    }

    #[Test]
    public function cardPluginRendersContentElementHeader(): void
    {
        $this->setUpTestCase('cardPage');
        $this->setContentElementHeader('Our team');

        $this->assertStringContainsString('Our team', $this->renderHomePage());
    }

    #[Test]
    public function cardPluginRendersProfileNameAsUngroupedHeading(): void
    {
        $this->setUpTestCase('cardPage');

        // The card template passes no `groupedProfiles`, so the item renders through
        // `Profile/Header` rather than `Profile/SectionHeader` — one level higher.
        $this->assertMatchesRegularExpression(
            '#<h2 class="card-title">\s*<a href="[^"]*">Max\s+Müllermann</a>\s*</h2>#',
            $this->renderHomePage(),
        );
    }

    #[Test]
    public function cardPluginRendersTheContractDataOfEachProfile(): void
    {
        $this->setUpTestCase('cardPage');

        $content = $this->renderHomePage();
        $this->assertStringContainsString('Professor', $content);
        $this->assertStringContainsString('A 101', $content);
        $this->assertStringContainsString('Lecturer', $content);
        // A contract location is a relation, so the partial has to render its title
        // rather than the object.
        $this->assertStringContainsString('Main Campus', $content);
        $this->assertStringNotContainsString('Domain\\Model\\Location', $content);
    }

    #[Test]
    public function cardPluginLinksEachProfileToTheConfiguredDetailPage(): void
    {
        $this->setUpTestCase('cardPage');

        $this->assertStringContainsString('href="/profiles?tx_academicpersons_detail', $this->renderHomePage());
    }

    #[Test]
    public function cardPluginRestrictsRenderedFieldsWhenConfigured(): void
    {
        $this->setUpTestCase('cardPage_showFields');

        $content = $this->renderHomePage();
        $this->assertStringContainsString('Professor', $content);
        // Only the configured field renders, so the other contract fields are gone.
        $this->assertStringNotContainsString('A 101', $content);
        $this->assertStringNotContainsString('Main Campus', $content);
    }

    /**
     * `contracts.organisationalUnit` is one of the items the `showFields` selector offers,
     * and it is a relation: the partial has to render a property of the unit rather than the
     * object - the defect the contract location had before ACE-317, and the reason the row
     * used to render as a label with nothing behind it.
     */
    #[Test]
    public function cardPluginRendersTheOrganisationalUnitOfAContract(): void
    {
        $this->setUpTestCase('cardPage_showFieldsContractRelations');

        $content = $this->renderHomePage();
        $this->assertStringContainsString('Organisational Unit', $content);
        $this->assertStringContainsString('Institute of Applied Physics', $content);
        $this->assertStringNotContainsString('OrganisationalUnit', $content);
    }

    /**
     * `displayText` is the field meant for output and `unitName` the one an import fills
     * reliably, so the second is what a unit without a display text renders.
     */
    #[Test]
    public function cardPluginFallsBackToTheUnitNameWithoutADisplayText(): void
    {
        $this->setUpTestCase('cardPage_showFieldsContractRelations');

        $this->assertStringContainsString('Faculty of Engineering', $this->renderHomePage());
    }

    /**
     * The third contract has no unit, and the row of a field a contract does not carry is not
     * rendered at all - not as an empty label either.
     */
    #[Test]
    public function cardPluginRendersNoUnitRowForAContractWithoutOne(): void
    {
        $this->setUpTestCase('cardPage_showFieldsContractRelations');

        $content = $this->renderHomePage();
        $this->assertRendersProfileName($content, 'Erika', 'Beispiel');
        $this->assertSame(2, substr_count($content, 'Organisational Unit'));
    }

    /**
     * A `tel:` URI carries no spaces, while the stored number is written for a reader. The
     * detail view has done this since it was written; the card rendered the stored number
     * into the target unchanged.
     */
    #[Test]
    public function cardPluginRendersADialablePhoneNumberTarget(): void
    {
        $this->setUpTestCase('cardPage_showFieldsContractRelations');

        $content = $this->renderHomePage();
        $this->assertStringContainsString('href="tel:+496241509123"', $content);
        $this->assertStringContainsString('>+49 6241 509 123</a>', $content);
    }

    /**
     * The prefix an integrator configures for an installation that stores phone numbers as
     * extensions only. It reaches the link target of every phone number and never the text.
     */
    #[Test]
    public function cardPluginPrefixesThePhoneNumberTargetWhenConfigured(): void
    {
        $this->setUpTestCase(
            'cardPage_showFieldsExtensionNumber',
            ['EXT:academic_persons/Tests/Functional/Plugins/Fixtures/TypoScript/Constants/PhoneLinkPrefix.typoscript'],
        );

        $content = $this->renderHomePage();
        $this->assertStringContainsString('href="tel:+496241509123"', $content);
        $this->assertStringContainsString('>123</a>', $content);
    }

    #[Test]
    public function cardPluginHidesHiddenProfilesByDefault(): void
    {
        $this->setUpTestCase('cardPage_hiddenProfile');

        $content = $this->renderHomePage();
        $this->assertRendersProfileName($content, 'Max', 'Müllermann');
        $this->assertStringNotContainsString('Erika', $content);
    }

    #[Test]
    public function cardPluginRendersHiddenProfilesWhenConfigured(): void
    {
        $this->setUpTestCase('cardPage_showHiddenRecords');

        $content = $this->renderHomePage();
        $this->assertRendersProfileName($content, 'Max', 'Müllermann');
        $this->assertRendersProfileName($content, 'Erika', 'Beispiel');
        // "Show hidden records" lifts the hidden flag only: profile 4 is selected, but its end
        // time has passed, and that keeps it out of the frontend.
        $this->assertStringNotContainsString('Abgelaufen', $content);
    }

    #[Test]
    public function cardPluginRendersNoProfilesFoundWithoutSelection(): void
    {
        $this->setUpTestCase('cardPage_withoutSelection');

        $content = $this->renderHomePage();
        $this->assertStringContainsString('academic-persons-card', $content);
        $this->assertStringContainsString('No profiles found', $content);
        $this->assertStringNotContainsString('academic-persons-item', $content);
    }
}
