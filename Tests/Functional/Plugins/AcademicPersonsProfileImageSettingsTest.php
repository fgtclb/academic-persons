<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Plugins;

use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use FGTCLB\TestingHelper\FunctionalTestCase\ResponsiveImageAssertionTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\Exception as ViewHelperException;

/**
 * Renders the profile image settings `plugin.tx_academicpersons.image.*`: the crop variant
 * per view and the placeholders per gender.
 *
 * The fixture image is 600 x 800 pixels and stores three crop areas: `default` its upper
 * 600 x 720 pixels, `square` a 600 x 600 band and `portrait` a 300 x 400 area in the middle,
 * so the `default` crop, the other two and no crop at all (600 x 800) all differ. The
 * processed fallback image tells them apart by its dimensions: the fallback of neither the
 * `card` (690) nor the `detail` preset (1200) scales a 600 pixel wide image down.
 *
 * Four profiles are selected, in this order: Max (uid 1, gender "mr") with the image, and
 * three without one - Erika (uid 2, "ms"), Kim (uid 3, "diverse") and Alex (uid 4, no
 * gender). The selected profiles element stands in for every element that reads the list
 * crop variant: it renders the same grid and item partials the list does.
 */
final class AcademicPersonsProfileImageSettingsTest extends AbstractAcademicPersonsTestCase
{
    use FrontendPluginRenderingTrait;
    use ResponsiveImageAssertionTrait;
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    private const FIXTURES = __DIR__ . '/Fixtures/AcademicPersonsProfileImageSettings/';
    private const IMAGE_FIXTURE = __DIR__ . '/Fixtures/AcademicPersonsProfileImage/portrait.jpg';
    private const CONSTANTS = 'EXT:academic_persons/Tests/Functional/Plugins/Fixtures/TypoScript/Constants/';
    private const ITEM_IMAGE_CLASS = 'academic-persons-item__image card-img-top img-fluid';
    private const DETAIL_IMAGE_CLASS = 'academic-persons-detail__image img-fluid rounded-0';

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
        $this->addTestExtensionsToLoad('tests/test-profile-placeholders');
        parent::setUp();
        $folder = $this->instancePath . '/fileadmin/images';
        GeneralUtility::mkdir_deep($folder);
        copy(self::IMAGE_FIXTURE, $folder . '/portrait.jpg');
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    /**
     * @param list<string> $constants file names below the shared constants fixture folder
     */
    private function setUpPage(string $dataSet, array $constants = []): void
    {
        $this->importCSVDataSet(self::FIXTURES . $dataSet . '.csv');
        $this->setUpFrontendRootPage(
            pageId: 1,
            typoScriptFiles: [
                'constants' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript',
                    'EXT:academic_persons/Configuration/TypoScript/Default/constants.typoscript',
                    ...array_map(static fn(string $file): string => self::CONSTANTS . $file . '.typoscript', $constants),
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

    /**
     * The request of {@see AcademicPersonsPublicProfilePluginTest}, cHash included.
     */
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
     * @return array{0: \DOMXPath, 1: list<\DOMElement>} the page and its items in the order they are rendered
     */
    private function renderItems(): array
    {
        $xpath = $this->parseRenderedPage($this->renderFrontendPage('https://www.acme.com/home'));
        $items = [];
        foreach ($this->nodesMatching($xpath, "//*[contains(concat(' ', normalize-space(@class), ' '), ' academic-persons-item ')]") as $item) {
            $this->assertInstanceOf(\DOMElement::class, $item);
            $items[] = $item;
        }
        $this->assertCount(4, $items);

        return [$xpath, $items];
    }

    private function assertFallbackImageSize(\DOMXPath $xpath, \DOMNode $context, int $width, int $height): void
    {
        $image = $this->elementMatching($xpath, './/picture/img', $context);
        $this->assertSame(
            [(string)$width, (string)$height],
            [$image->getAttribute('width'), $image->getAttribute('height')],
            'The processed image has other dimensions than the expected crop area.',
        );
    }

    private function assertItemShowsTheCardPresetWithSize(\DOMXPath $xpath, \DOMElement $item, int $width, int $height): void
    {
        $this->assertRendersResponsivePicture($xpath, $item, 4, $width, self::ITEM_IMAGE_CLASS, 'Portrait of Max Müllermann');
        $this->assertFallbackImageSize($xpath, $item, $width, $height);
    }

    private function assertItemShowsThePlaceholder(\DOMXPath $xpath, \DOMElement $item, string $fileName): void
    {
        $this->assertSame(0, $this->countNodesMatching($xpath, './/picture', $item));
        $image = $this->elementMatching($xpath, sprintf('.//img[@class="%s"]', self::ITEM_IMAGE_CLASS), $item);
        $this->assertStringEndsWith('Images/' . $fileName, $image->getAttribute('src'));
        // Decorative: it shows nobody, and the name is the heading of the same item.
        $this->assertTrue($image->hasAttribute('alt'));
        $this->assertSame('', $image->getAttribute('alt'));
    }

    #[Test]
    public function withoutConfigurationTheCardRendersTheDefaultCrop(): void
    {
        $this->setUpPage('card');
        [$xpath, $items] = $this->renderItems();
        $this->assertItemShowsTheCardPresetWithSize($xpath, $items[0], 600, 720);
    }

    #[Test]
    public function withoutConfigurationTheListRendersTheDefaultCrop(): void
    {
        $this->setUpPage('selectedProfiles');
        [$xpath, $items] = $this->renderItems();
        $this->assertItemShowsTheCardPresetWithSize($xpath, $items[0], 600, 720);
    }

    #[Test]
    public function withoutConfigurationTheDetailRendersTheDefaultCrop(): void
    {
        $this->setUpPage('detail');
        $xpath = $this->parseRenderedPage($this->renderDetailOfProfileOne());
        $figure = $this->elementMatching($xpath, "//figure[@class='academic-persons-detail__figure']");
        $this->assertRendersResponsivePicture($xpath, $figure, 3, 600, self::DETAIL_IMAGE_CLASS);
        $this->assertFallbackImageSize($xpath, $figure, 600, 720);
    }

    #[Test]
    public function cardRendersTheConfiguredSquareCrop(): void
    {
        $this->setUpPage('card', ['CardCropVariantSquare']);
        [$xpath, $items] = $this->renderItems();
        $this->assertItemShowsTheCardPresetWithSize($xpath, $items[0], 600, 600);
    }

    #[Test]
    public function listKeepsTheDefaultCropWhenOnlyTheCardIsConfigured(): void
    {
        $this->setUpPage('selectedProfiles', ['CardCropVariantSquare']);
        [$xpath, $items] = $this->renderItems();
        $this->assertItemShowsTheCardPresetWithSize($xpath, $items[0], 600, 720);
    }

    #[Test]
    public function listRendersTheConfiguredSquareCrop(): void
    {
        $this->setUpPage('selectedProfiles', ['ListCropVariantSquare']);
        [$xpath, $items] = $this->renderItems();
        $this->assertItemShowsTheCardPresetWithSize($xpath, $items[0], 600, 600);
    }

    #[Test]
    public function cardKeepsTheDefaultCropWhenOnlyTheListIsConfigured(): void
    {
        $this->setUpPage('card', ['ListCropVariantSquare']);
        [$xpath, $items] = $this->renderItems();
        $this->assertItemShowsTheCardPresetWithSize($xpath, $items[0], 600, 720);
    }

    #[Test]
    public function detailRendersTheConfiguredPortraitCropWithTheDetailPreset(): void
    {
        $this->setUpPage('detail', ['DetailCropVariantPortrait']);
        $xpath = $this->parseRenderedPage($this->renderDetailOfProfileOne());
        $figure = $this->elementMatching($xpath, "//figure[@class='academic-persons-detail__figure']");
        $this->assertRendersResponsivePicture($xpath, $figure, 3, 300, self::DETAIL_IMAGE_CLASS);
        $this->assertFallbackImageSize($xpath, $figure, 300, 400);
    }

    /**
     * The core crop variant collection answers an unknown name with an empty area, which
     * processes the image uncropped, not with its `default` crop. That is kept, not
     * validated: a project with crop variants of its own only sets their names.
     */
    #[Test]
    public function undefinedCropVariantRendersTheUncroppedImage(): void
    {
        $this->setUpPage('card', ['UndefinedCropVariant']);
        [$xpath, $items] = $this->renderItems();
        $this->assertItemShowsTheCardPresetWithSize($xpath, $items[0], 600, 800);
    }

    #[Test]
    public function undefinedCropVariantRendersTheUncroppedImageInTheDetail(): void
    {
        $this->setUpPage('detail', ['UndefinedCropVariant']);
        $xpath = $this->parseRenderedPage($this->renderDetailOfProfileOne());
        $figure = $this->elementMatching($xpath, "//figure[@class='academic-persons-detail__figure']");
        $this->assertFallbackImageSize($xpath, $figure, 600, 800);
    }

    #[Test]
    public function withoutConfigurationEveryProfileWithoutImageShowsTheShippedPlaceholder(): void
    {
        $this->setUpPage('card');
        [$xpath, $items] = $this->renderItems();
        foreach ([1, 2, 3] as $index) {
            $this->assertItemShowsThePlaceholder($xpath, $items[$index], 'ProfilePlaceholder.svg');
        }
    }

    #[Test]
    public function placeholderOfTheSitePackageReplacesTheShippedOne(): void
    {
        $this->setUpPage('card', ['PlaceholderFromSitePackage']);
        [$xpath, $items] = $this->renderItems();
        foreach ([1, 2, 3] as $index) {
            $this->assertItemShowsThePlaceholder($xpath, $items[$index], 'Person.svg');
        }
    }

    #[Test]
    public function genderPlaceholderWinsOverTheDefaultOne(): void
    {
        $this->setUpPage('card', ['PlaceholderFromSitePackage', 'PlaceholderForGenderMs']);
        [$xpath, $items] = $this->renderItems();
        $this->assertItemShowsTheCardPresetWithSize($xpath, $items[0], 600, 720);
        $this->assertItemShowsThePlaceholder($xpath, $items[1], 'PersonMs.svg');
        // No placeholder for "diverse", and none for a profile without a gender.
        $this->assertItemShowsThePlaceholder($xpath, $items[2], 'Person.svg');
        $this->assertItemShowsThePlaceholder($xpath, $items[3], 'Person.svg');
    }

    /**
     * @return \Generator<string, array{0: string, 1: string, 2: string}>
     */
    public static function genderDataProvider(): \Generator
    {
        yield 'mr' => ['mr', 'PlaceholderForGenderMr', 'PersonMr.svg'];
        yield 'ms' => ['ms', 'PlaceholderForGenderMs', 'PersonMs.svg'];
        yield 'diverse' => ['diverse', 'PlaceholderForGenderDiverse', 'PersonDiverse.svg'];
    }

    /**
     * Every gender value of the profile has a setting of its own, and the shared setup maps
     * each of them: a mistake in one mapping line shows up for that gender only.
     */
    #[DataProvider('genderDataProvider')]
    #[Test]
    public function everyGenderGetsItsOwnPlaceholder(string $gender, string $constants, string $fileName): void
    {
        $this->setUpPage('card', ['PlaceholderFromSitePackage', $constants]);
        $this->getConnectionPool()
            ->getConnectionForTable('tx_academicpersons_domain_model_profile')
            ->update('tx_academicpersons_domain_model_profile', ['gender' => $gender], ['uid' => 2]);

        [$xpath, $items] = $this->renderItems();
        $this->assertItemShowsThePlaceholder($xpath, $items[1], $fileName);
        $this->assertItemShowsThePlaceholder($xpath, $items[3], 'Person.svg');
    }

    #[Test]
    public function genderPlaceholderAppliesToTheListAsWell(): void
    {
        $this->setUpPage('selectedProfiles', ['PlaceholderForGenderMs']);
        [$xpath, $items] = $this->renderItems();
        $this->assertItemShowsThePlaceholder($xpath, $items[1], 'PersonMs.svg');
        $this->assertItemShowsThePlaceholder($xpath, $items[2], 'ProfilePlaceholder.svg');
    }

    #[Test]
    public function genderPlaceholderRendersWhileTheDefaultOneIsSwitchedOff(): void
    {
        $this->setUpPage('card', ['PlaceholderDisabled', 'PlaceholderForGenderMs']);
        [$xpath, $items] = $this->renderItems();
        $this->assertItemShowsThePlaceholder($xpath, $items[1], 'PersonMs.svg');
        $this->assertRendersNoImage($xpath, $items[2], self::ITEM_IMAGE_CLASS);
        $this->assertRendersNoImage($xpath, $items[3], self::ITEM_IMAGE_CLASS);
    }

    /**
     * An unresolvable placeholder is a deploy error and fails the rendering with the
     * exception of the image view helper, rather than leaving the image out unnoticed.
     *
     * The code and the wording differ: TYPO3 v13 reports the invalid argument of the file
     * lookup (1509741914), v14 a resource that does not exist (1509741911), "does not exist
     * in fallback compatibility storage". Both name the path.
     */
    #[Test]
    public function missingPlaceholderFileFailsTheRendering(): void
    {
        $this->setUpPage('card', ['PlaceholderMissingFile']);

        $this->expectException(ViewHelperException::class);
        $this->expectExceptionMessageMatches('#Images/Missing\.svg"? does not exist#');
        $this->requestFrontendPage('https://www.acme.com/home');
    }
}
