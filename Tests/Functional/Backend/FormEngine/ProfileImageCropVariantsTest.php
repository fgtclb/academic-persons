<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Backend\FormEngine;

use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\CropVariantsAssertionTrait;
use FGTCLB\TestingHelper\FunctionalTestCase\TcaHelperMethodsTrait;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * The crop variants an editor gets in the image cropper of a profile image.
 *
 * Templates request a variant by its name, so the names and ratios are what a site relies
 * on. The profile image is cropped before this change, with the free crop TYPO3 offers
 * when a field configures no variant: that crop is stored under the name `default`, and
 * the cropper has to show and keep it as it was.
 */
final class ProfileImageCropVariantsTest extends AbstractAcademicPersonsTestCase
{
    use CropVariantsAssertionTrait;
    use TcaHelperMethodsTrait;

    private const FIXTURES = __DIR__ . '/Fixtures/ProfileImageCropVariants/';

    protected function setUp(): void
    {
        parent::setUp();
        $folder = $this->instancePath . '/fileadmin/images';
        GeneralUtility::mkdir_deep($folder);
        copy(self::FIXTURES . 'portrait.jpg', $folder . '/portrait.jpg');
        $this->importCSVDataSet(self::FIXTURES . 'records.csv');
        $this->setUpBackendUser(1);
        $this->createTCABackup(false);
    }

    protected function tearDown(): void
    {
        $this->restoreTCABackup(true);
        parent::tearDown();
    }

    #[Test]
    public function theProfileImageOffersDefaultSquareAndPortrait(): void
    {
        $variants = $this->offeredCropVariants('tx_academicpersons_domain_model_profile', 1, 'image');

        $this->assertSame(['default', 'square', 'portrait'], array_keys($variants));
        $this->assertSame(['1:1' => 1.0], $this->aspectRatiosOf($variants['square']));
        $this->assertSame('1:1', $variants['square']['selectedRatio']);
        $this->assertSame(['3:4' => 0.75], $this->aspectRatiosOf($variants['portrait']));
        $this->assertSame('3:4', $variants['portrait']['selectedRatio']);
    }

    #[Test]
    public function theDefaultVariantIsTheOneTypo3OffersWithoutConfiguration(): void
    {
        $variants = $this->offeredCropVariants('tx_academicpersons_domain_model_profile', 1, 'image');
        $coreVariants = $this->cropVariantsWithoutConfiguration('tx_academicpersons_domain_model_profile', 1, 'image');

        $this->assertSame(['default'], array_keys($coreVariants));
        $this->assertSame($coreVariants['default'], $variants['default']);
    }

    /**
     * The fixture stores a crop area in no ratio the cropper offers. A `default` variant
     * with a fixed ratio would fit it into that ratio as soon as the editor opens the image.
     */
    #[Test]
    public function aCropStoredBeforeTheChangeKeepsItsArea(): void
    {
        $variants = $this->offeredCropVariants('tx_academicpersons_domain_model_profile', 1, 'image');

        $this->assertEquals(['x' => 0.1, 'y' => 0.2, 'width' => 0.5, 'height' => 0.3], $variants['default']['cropArea']);
        $this->assertSame('NaN', $variants['default']['selectedRatio']);
    }

    /**
     * The cropper hands a stored crop to the variant of its name. A variant without one
     * takes the stored crops in their stored order, starting with the first, even when
     * another variant took that one by name: the first new variant starts from the crop
     * stored for `default`, fitted into its ratio, the second one, with none left, from
     * the whole image. The fixture image is 600 x 800 pixels.
     */
    #[Test]
    public function theNewVariantsStartFromTheStoredCropAndTheWholeImage(): void
    {
        $variants = $this->offeredCropVariants('tx_academicpersons_domain_model_profile', 1, 'image');

        $this->assertEqualsWithDelta(['x' => 0.15, 'y' => 0.2, 'width' => 0.4, 'height' => 0.3], $variants['square']['cropArea'], 0.0001);
        $this->assertEqualsWithDelta(['x' => 0.0, 'y' => 0.0, 'width' => 1.0, 'height' => 1.0], $variants['portrait']['cropArea'], 0.0001);
    }

    /**
     * A variant a site does not want is disabled on the field, which is what the
     * documentation recommends. Page TSconfig is not: on a field that configures no
     * variants, it leaves the cropper with none.
     */
    #[Test]
    public function aVariantDisabledOnTheFieldIsNotOffered(): void
    {
        $tca = $GLOBALS['TCA'];
        $tca['tx_academicpersons_domain_model_profile']['columns']['image']['config']['overrideChildTca']['columns']['crop']['config']['cropVariants']['portrait']['disabled'] = true;
        $this->updateGlobalTCA($tca);

        $this->assertSame(['default', 'square'], array_keys($this->offeredCropVariants('tx_academicpersons_domain_model_profile', 1, 'image')));
    }
}
