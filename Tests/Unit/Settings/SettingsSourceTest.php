<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Unit\Settings;

use FGTCLB\AcademicBase\Settings\SettingsFileLoader;
use FGTCLB\AcademicBase\Settings\ValidationNormalizer;
use FGTCLB\AcademicPersons\Settings\AcademicPersonsSettingsFactory;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * There is one settings file, shipped by this extension, and the editing
 * extension reads it rather than shipping a second one. The public layout and
 * the editable fields share the `profile` map on purpose: an integrator
 * overriding the layout restates the fields with it, and never has to keep two
 * files in step.
 */
final class SettingsSourceTest extends UnitTestCase
{
    private const CENTRAL_FILE = __DIR__ . '/../../../Configuration/AcademicPersons/Settings.yaml';

    #[Test]
    public function theEditExtensionDoesNotShipASecondSettingsFile(): void
    {
        $editExtension = __DIR__ . '/../../../../academic-persons-edit';
        $this->assertDirectoryExists($editExtension);

        $this->assertFileDoesNotExist($editExtension . '/Configuration/AcademicPersonsEdit/Settings.yaml');
        $this->assertFileDoesNotExist($editExtension . '/Configuration/AcademicsPersonsEdit/Settings.yaml');
        $this->assertFileDoesNotExist($editExtension . '/Configuration/AcademicPersons/Settings.yaml');
        $this->assertFileExists(self::CENTRAL_FILE);
    }

    /**
     * What the editing extension relies on in the shipped file: the four maps, the
     * layout keys next to the fields in `profile`, a help text for every contract
     * and contact field, and the character limit of the one long rich text field.
     */
    #[Test]
    public function theCentralFileCarriesTheLayoutAndTheEditableFieldsTogether(): void
    {
        $configuration = Yaml::parseFile(self::CENTRAL_FILE);
        $this->assertIsArray($configuration);

        $this->assertSame(['profile', 'special', 'contracts', 'documentSections'], array_keys($configuration));
        $this->assertArrayHasKey('structure', $configuration['profile']);
        $this->assertArrayHasKey('details', $configuration['profile']);
        $this->assertArrayHasKey('gender', $configuration['profile']);
        $this->assertSame(1000, $configuration['profile']['miscellaneous']['characterLimit'] ?? null);
        $this->assertSame(['type' => 'contracts'], $configuration['documentSections']['contracts']);
        $this->assertSame(
            [
                'position',
                'organisationalUnit',
                'functionType',
                'validFrom',
                'validTo',
                'location',
                'room',
                'officeHours',
                'publish',
            ],
            array_keys($configuration['contracts']['fields']),
        );
        $this->assertSame(
            ['physicalAddresses', 'emailAddresses', 'phoneNumbers'],
            array_keys($configuration['contracts']['contactSections']),
        );
        foreach ($configuration['contracts']['fields'] as $identifier => $field) {
            $this->assertIsArray($field, $identifier);
            $this->assertIsString($field['helptext'] ?? null, $identifier);
        }
        foreach ($configuration['contracts']['contactSections'] as $sectionIdentifier => $section) {
            $this->assertIsArray($section, $sectionIdentifier);
            foreach ($section['fields'] ?? [] as $identifier => $field) {
                $this->assertIsArray($field, $identifier);
                $this->assertIsString($field['helptext'] ?? null, $identifier);
            }
        }
    }

    #[Test]
    public function theCentralFactoryNormalizesPublicAndEditSettingsTogether(): void
    {
        $configuration = Yaml::parseFile(self::CENTRAL_FILE);
        $this->assertIsArray($configuration);
        $factory = new AcademicPersonsSettingsFactory(
            new SettingsFileLoader($this->createMock(PhpFrontend::class), $this->createMock(PackageManager::class)),
            new ValidationNormalizer(),
        );

        $settings = $factory->normalize($configuration);

        $this->assertNotEmpty($settings->publicProfile->structure);
        $this->assertNotNull($settings->getProfileField('gender'));
        $this->assertNotNull($settings->getProfileField('firstName'));
        $this->assertNotEmpty($settings->documentSections);
        $this->assertNotEmpty($settings->contractContactSections);
    }
}
