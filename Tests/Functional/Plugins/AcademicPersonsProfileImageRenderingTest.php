<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Plugins;

use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Renders the profile image of the shipped card and public profile templates, which go
 * through the responsive image partial of academic_base.
 *
 * The card plugin stands in for the list, selected profiles and selected contracts plugins:
 * all four render the same `Profile/Item` partial. The fixture selects Max (uid 1), who has
 * a 600 x 800 JPEG as image, and Horst (uid 2), who has none - in that order.
 *
 * The detail request is the one of {@see AcademicPersonsPublicProfilePluginTest}, cHash
 * included, so it asks for profile 1 on the home page as well.
 */
final class AcademicPersonsProfileImageRenderingTest extends AbstractAcademicPersonsTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    private const FIXTURES = __DIR__ . '/Fixtures/AcademicPersonsProfileImage/';
    private const CONSTANTS = 'EXT:academic_persons/Tests/Functional/Plugins/Fixtures/TypoScript/Constants/';

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration([
            'FE' => [
                'cacheHash' => [
                    'requireCacheHashPresenceParameters' => ['value', 'testing[value]', 'tx_testing_link[value]'],
                    'excludedParameters' => ['L', 'tx_testing_link[excludedValue]'],
                    'enforceValidation' => true,
                ],
            ],
        ]);
        $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
        parent::setUp();
        $folder = $this->instancePath . '/fileadmin/images';
        GeneralUtility::mkdir_deep($folder);
        copy(self::FIXTURES . 'portrait.jpg', $folder . '/portrait.jpg');
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    /**
     * @param list<string> $additionalConstants
     */
    private function setUpPage(string $dataSet, array $additionalConstants = []): void
    {
        $this->importCSVDataSet(self::FIXTURES . $dataSet . '.csv');
        $this->setUpFrontendRootPage(
            pageId: 1,
            typoScriptFiles: [
                'constants' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript',
                    'EXT:academic_persons/Configuration/TypoScript/Default/constants.typoscript',
                    ...$additionalConstants,
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
    }

    private function setShowFields(string $showFields): void
    {
        $connection = $this->getConnectionPool()->getConnectionForTable('tt_content');
        $flexForm = (string)$connection->select(['pi_flexform'], 'tt_content', ['uid' => 1])->fetchOne();
        $connection->update(
            'tt_content',
            [
                'pi_flexform' => str_replace(
                    '<field index="settings.showFields"><value index="vDEF"></value></field>',
                    '<field index="settings.showFields"><value index="vDEF">' . $showFields . '</value></field>',
                    $flexForm,
                ),
            ],
            ['uid' => 1],
        );
    }

    private function renderDetailOfProfileOne(): string
    {
        return $this->renderFrontendPage(
            'https://www.acme.com/home?' . http_build_query([
                'tx_academicpersons_detail' => [
                    'controller' => 'Profile',
                    'action' => 'detail',
                    'profile' => 1,
                ],
                'cHash' => '13c8ec3ab2a317651a40bd164df8a366',
            ])
        );
    }

    /**
     * @return \DOMNodeList<\DOMNode>
     */
    private function nodes(\DOMXPath $xpath, string $query, ?\DOMNode $context = null): \DOMNodeList
    {
        $nodes = $xpath->query($query, $context);
        $this->assertInstanceOf(\DOMNodeList::class, $nodes, sprintf('The query "%s" is invalid.', $query));

        return $nodes;
    }

    private function countNodes(\DOMXPath $xpath, string $query, ?\DOMNode $context = null): int
    {
        return $this->nodes($xpath, $query, $context)->length;
    }

    private function parse(string $html): \DOMXPath
    {
        $document = new \DOMDocument();
        $document->loadHTML($html, LIBXML_NOERROR);

        return new \DOMXPath($document);
    }

    /**
     * @return list<\DOMElement> the cards in the order they are rendered
     */
    private function cards(\DOMXPath $xpath): array
    {
        $cards = [];
        foreach ($this->nodes($xpath, "//*[contains(concat(' ', normalize-space(@class), ' '), ' academic-persons-item ')]") as $card) {
            $this->assertInstanceOf(\DOMElement::class, $card);
            $cards[] = $card;
        }
        $this->assertCount(2, $cards);

        return $cards;
    }

    private function assertCardShowsTheProfileImage(\DOMXPath $xpath, \DOMElement $card): void
    {
        $this->assertSame(1, $this->countNodes($xpath, './/picture', $card));
        $sources = $this->nodes($xpath, './/picture/source', $card);
        $this->assertGreaterThan(0, $sources->length);
        foreach ($sources as $source) {
            $this->assertInstanceOf(\DOMElement::class, $source);
            $this->assertSame('image/webp', $source->getAttribute('type'));
            $this->assertStringEndsWith('.webp', $source->getAttribute('srcset'));
        }
        $image = $this->nodes($xpath, './/picture/img', $card)->item(0);
        $this->assertInstanceOf(\DOMElement::class, $image);
        $this->assertStringEndsWith('.jpg', $image->getAttribute('src'));
        $this->assertSame('card-img-top img-fluid', $image->getAttribute('class'));
        $this->assertSame('Portrait of Max Müllermann', $image->getAttribute('alt'));
    }

    #[Test]
    public function cardShowsTheProfileImageAsAResponsivePicture(): void
    {
        $this->setUpPage('cardPage');

        $xpath = $this->parse($this->renderFrontendPage('https://www.acme.com/home'));
        $this->assertCardShowsTheProfileImage($xpath, $this->cards($xpath)[0]);
    }

    #[Test]
    public function cardShowsThePlaceholderForAProfileWithoutImage(): void
    {
        $this->setUpPage('cardPage');

        $xpath = $this->parse($this->renderFrontendPage('https://www.acme.com/home'));
        $card = $this->cards($xpath)[1];
        $this->assertSame(0, $this->countNodes($xpath, './/picture', $card));
        $images = $this->nodes($xpath, './/img', $card);
        $this->assertSame(1, $images->length);
        $image = $images->item(0);
        $this->assertInstanceOf(\DOMElement::class, $image);
        $this->assertStringEndsWith('Images/ProfilePlaceholder.svg', $image->getAttribute('src'));
        $this->assertSame('card-img-top img-fluid', $image->getAttribute('class'));
    }

    #[Test]
    public function cardShowsNoImageForAProfileWithoutImageWhenThePlaceholderIsEmpty(): void
    {
        $this->setUpPage('cardPage', [self::CONSTANTS . 'PlaceholderDisabled.typoscript']);

        $xpath = $this->parse($this->renderFrontendPage('https://www.acme.com/home'));
        [$withImage, $withoutImage] = $this->cards($xpath);
        $this->assertCardShowsTheProfileImage($xpath, $withImage);
        $this->assertSame(0, $this->countNodes($xpath, './/img', $withoutImage));
    }

    #[Test]
    public function cardShowsNeitherImageNorPlaceholderWhenTheShownFieldsOmitTheImage(): void
    {
        $this->setUpPage('cardPage');
        $this->setShowFields('contract.position');

        $html = $this->renderFrontendPage('https://www.acme.com/home');
        $xpath = $this->parse($html);
        foreach ($this->cards($xpath) as $card) {
            $this->assertSame(0, $this->countNodes($xpath, './/img', $card));
        }
        // The shown fields are applied, not ignored: the position is still there.
        $this->assertStringContainsString('Professor', $html);
    }

    #[Test]
    public function cardShowsImageAndPlaceholderWhenTheShownFieldsIncludeTheImage(): void
    {
        $this->setUpPage('cardPage');
        $this->setShowFields('contract.position,profile.image');

        $xpath = $this->parse($this->renderFrontendPage('https://www.acme.com/home'));
        [$withImage, $withoutImage] = $this->cards($xpath);
        $this->assertCardShowsTheProfileImage($xpath, $withImage);
        $this->assertSame(1, $this->countNodes($xpath, './/img', $withoutImage));
    }

    /**
     * The academic_base partials are registered below the key of the project constant, so a
     * project overrides `Academic/Image.html` the way it overrides any partial of this
     * extension.
     */
    #[Test]
    public function projectOverrideOfTheSharedImagePartialWins(): void
    {
        $this->setUpPage('cardPage', [self::CONSTANTS . 'ImagePartialOverride.typoscript']);

        $html = $this->renderFrontendPage('https://www.acme.com/home');
        $this->assertSame(2, substr_count($html, '<span class="project-image-override">card</span>'));
        $this->assertStringNotContainsString('<picture', $html);
    }

    #[Test]
    public function detailShowsTheProfileImageAsAResponsivePictureWithTheNamesAsAlternativeText(): void
    {
        $this->setUpPage('detailPage');

        $xpath = $this->parse($this->renderDetailOfProfileOne());
        $this->assertSame(1, $this->countNodes($xpath, "//figure[@class='academic-persons-detail__figure']/picture"));
        $sources = $this->nodes($xpath, '//figure/picture/source');
        $this->assertGreaterThan(0, $sources->length);
        foreach ($sources as $source) {
            $this->assertInstanceOf(\DOMElement::class, $source);
            $this->assertSame('image/webp', $source->getAttribute('type'));
        }
        $image = $this->nodes($xpath, '//figure/picture/img')->item(0);
        $this->assertInstanceOf(\DOMElement::class, $image);
        $this->assertSame('academic-persons-detail__image img-fluid rounded-0', $image->getAttribute('class'));
        $this->assertSame('lazy', $image->getAttribute('loading'));
        // Title, first, middle and last name, as before; the empty middle name leaves a gap.
        $this->assertMatchesRegularExpression('/^Prof\. Dr\. Max\s+Müllermann$/u', $image->getAttribute('alt'));
    }

    #[Test]
    public function detailShowsNoImageAndNoPlaceholderForAProfileWithoutImage(): void
    {
        $this->setUpPage('detailPage');
        $this->getConnectionPool()
            ->getConnectionForTable('tx_academicpersons_domain_model_profile')
            ->update('tx_academicpersons_domain_model_profile', ['image' => 0], ['uid' => 1]);
        $this->getConnectionPool()
            ->getConnectionForTable('sys_file_reference')
            ->delete('sys_file_reference', ['uid' => 1]);

        $html = $this->renderDetailOfProfileOne();
        $this->assertStringContainsString('Müllermann', $html);
        $this->assertStringNotContainsString('academic-persons-detail__figure', $html);
        $this->assertStringNotContainsString('ProfilePlaceholder.svg', $html);
    }
}
