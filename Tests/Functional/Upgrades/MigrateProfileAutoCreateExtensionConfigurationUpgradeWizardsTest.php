<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Upgrades;

use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use FGTCLB\AcademicPersons\Upgrades\MigrateProfileAutoCreateExtensionConfigurationUpgradeWizards;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;

/**
 * The wizard copies the profile auto create options of `academic_persons_edit` to
 * `academic_persons`. It used to report itself as necessary wherever
 * `academic_persons_edit` was loaded, also when there was nothing to copy (ACE-847).
 */
final class MigrateProfileAutoCreateExtensionConfigurationUpgradeWizardsTest extends AbstractAcademicPersonsTestCase
{
    protected function setUp(): void
    {
        $this->addTestExtension('fgtclb/academic-persons-edit');
        parent::setUp();
    }

    /**
     * @param array{autoCreateProfiles?: int|string, createProfileForUserGroups?: string} $persons
     * @param array{autoCreateProfiles?: int|string, createProfileForUserGroups?: string} $personsEdit
     */
    private function configure(array $persons, array $personsEdit): void
    {
        foreach (['academic_persons' => $persons, 'academic_persons_edit' => $personsEdit] as $extensionKey => $profile) {
            foreach ($profile as $option => $value) {
                $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'][$extensionKey]['profile'][$option] = $value;
            }
        }
    }

    private function subject(): MigrateProfileAutoCreateExtensionConfigurationUpgradeWizards
    {
        return $this->get(MigrateProfileAutoCreateExtensionConfigurationUpgradeWizards::class);
    }

    public static function nothingToMigrateDataSets(): \Generator
    {
        yield 'both at their defaults' => [
            ['autoCreateProfiles' => '0', 'createProfileForUserGroups' => ''],
            ['autoCreateProfiles' => '0', 'createProfileForUserGroups' => ''],
        ];
        yield 'set in academic_persons only' => [
            ['autoCreateProfiles' => '1', 'createProfileForUserGroups' => '3,4'],
            ['autoCreateProfiles' => '0', 'createProfileForUserGroups' => ''],
        ];
        yield 'set in both, academic_persons wins' => [
            ['autoCreateProfiles' => '1', 'createProfileForUserGroups' => '3'],
            ['autoCreateProfiles' => '1', 'createProfileForUserGroups' => '5'],
        ];
    }

    /**
     * @param array{autoCreateProfiles: string, createProfileForUserGroups: string} $persons
     * @param array{autoCreateProfiles: string, createProfileForUserGroups: string} $personsEdit
     */
    #[DataProvider('nothingToMigrateDataSets')]
    #[Test]
    public function updateIsNotNecessaryWithNothingToMigrate(array $persons, array $personsEdit): void
    {
        $this->configure($persons, $personsEdit);

        $this->assertFalse($this->subject()->updateNecessary());
    }

    public static function somethingToMigrateDataSets(): \Generator
    {
        yield 'automatic creation enabled' => [
            ['autoCreateProfiles' => '1', 'createProfileForUserGroups' => ''],
            ['autoCreateProfiles' => 1, 'createProfileForUserGroups' => ''],
        ];
        yield 'user groups set' => [
            ['autoCreateProfiles' => '0', 'createProfileForUserGroups' => '7,8'],
            ['autoCreateProfiles' => '0', 'createProfileForUserGroups' => '7,8'],
        ];
    }

    /**
     * @param array{autoCreateProfiles: string, createProfileForUserGroups: string} $personsEdit
     * @param array{autoCreateProfiles: int|string, createProfileForUserGroups: string} $expected
     */
    #[DataProvider('somethingToMigrateDataSets')]
    #[Test]
    public function aValueOfAcademicPersonsEditIsMigratedOnce(array $personsEdit, array $expected): void
    {
        $this->configure(['autoCreateProfiles' => '0', 'createProfileForUserGroups' => ''], $personsEdit);
        $this->assertTrue($this->subject()->updateNecessary());

        $this->assertTrue($this->subject()->executeUpdate());

        $profile = $this->get(ExtensionConfiguration::class)->get('academic_persons', 'profile');
        $this->assertEquals($expected['autoCreateProfiles'], $profile['autoCreateProfiles']);
        $this->assertSame($expected['createProfileForUserGroups'], $profile['createProfileForUserGroups']);
        $this->assertFalse($this->subject()->updateNecessary());
    }

    #[Test]
    public function theWizardHasADescription(): void
    {
        $this->assertStringContainsString('academic_persons_edit', $this->subject()->getDescription());
    }
}
