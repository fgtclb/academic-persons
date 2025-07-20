<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Upgrades;

use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use FGTCLB\AcademicPersons\Upgrades\ListTypeToCTypeUpgradeWizard;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * This upgrade wizards migrates deprecated `list_type` (subtype) content elements to `CType`(main type)
 * content elements, which includes all provided extbase plugins for `EXT:academic_persons.
 *
 * Further, permission selections for backend users and groups are updated to have the same permissions
 * in place for instances which has configured user permissions on list_type level.
 *
 * The upgrade wizard is based in the TYPO3 v13 `AbstractListTypeToCTypeUpdate` but not extending it,
 * because it would not be available in TYPO3 v12 instances.
 */
final class ListTypeToCTypeUpgradeWizardTest extends AbstractAcademicPersonsTestCase
{
    #[Test]
    public function updateNecessaryReturnsFalseWhenListTypeRecordsAreAvailable(): void
    {
        $subject = $this->get(ListTypeToCTypeUpgradeWizard::class);
        $this->assertInstanceOf(ListTypeToCTypeUpgradeWizard::class, $subject);
        $this->assertFalse($subject->updateNecessary());
    }

    public static function ttContentPluginDataSets(): \Generator
    {
        yield 'only detail - not deleted and hidden' => [
            'fixtureDataSetFile' => 'onlyDetail_notDeletedOrHidden.csv',
        ];
        yield 'only detail - not deleted and but hidden' => [
            'fixtureDataSetFile' => 'onlyDetail_notDeletedButHidden.csv',
        ];
        yield 'only detail - deleted but not hidden' => [
            'fixtureDataSetFile' => 'onlyDetail_deletedButNotHidden.csv',
        ];
        yield 'only list - not deleted and hidden' => [
            'fixtureDataSetFile' => 'onlyList_notDeletedOrHidden.csv',
        ];
        yield 'only list - not deleted and but hidden' => [
            'fixtureDataSetFile' => 'onlyList_notDeletedButHidden.csv',
        ];
        yield 'only list - deleted but not hidden' => [
            'fixtureDataSetFile' => 'onlyList_deletedButNotHidden.csv',
        ];
        yield 'only listanddetail - not deleted and hidden' => [
            'fixtureDataSetFile' => 'onlyListAndDetail_notDeletedOrHidden.csv',
        ];
        yield 'only listanddetail - not deleted and but hidden' => [
            'fixtureDataSetFile' => 'onlyListAndDetail_notDeletedButHidden.csv',
        ];
        yield 'only listanddetail - deleted but not hidden' => [
            'fixtureDataSetFile' => 'onlyListAndDetail_deletedButNotHidden.csv',
        ];
        yield 'only selectedcontracts - not deleted and hidden' => [
            'fixtureDataSetFile' => 'onlySelectedContracts_notDeletedOrHidden.csv',
        ];
        yield 'only selectedcontracts - not deleted and but hidden' => [
            'fixtureDataSetFile' => 'onlySelectedContracts_notDeletedButHidden.csv',
        ];
        yield 'only selectedcontracts - deleted but not hidden' => [
            'fixtureDataSetFile' => 'onlySelectedContracts_deletedButNotHidden.csv',
        ];
        yield 'only selectedprofiles - not deleted and hidden' => [
            'fixtureDataSetFile' => 'onlySelectedProfiles_notDeletedOrHidden.csv',
        ];
        yield 'only selectedprofiles - not deleted and but hidden' => [
            'fixtureDataSetFile' => 'onlySelectedProfiles_notDeletedButHidden.csv',
        ];
        yield 'only selectedprofiles - deleted but not hidden' => [
            'fixtureDataSetFile' => 'onlySelectedProfiles_deletedButNotHidden.csv',
        ];
    }

    #[DataProvider('ttContentPluginDataSets')]
    #[Test]
    public function updateNecessaryReturnsTrueWhenUpgradablePluginsExists(
        string $fixtureDataSetFile,
    ): void {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSets/' . $fixtureDataSetFile);
        $subject = $this->get(ListTypeToCTypeUpgradeWizard::class);
        $this->assertInstanceOf(ListTypeToCTypeUpgradeWizard::class, $subject);
        $this->assertTrue($subject->updateNecessary(), 'updateNecessary() returns true');
    }

    #[DataProvider('ttContentPluginDataSets')]
    #[Test]
    public function executeUpdateMigratesContentElementsAndReturnsTrue(
        string $fixtureDataSetFile,
    ): void {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSets/' . $fixtureDataSetFile);
        $subject = $this->get(ListTypeToCTypeUpgradeWizard::class);
        $this->assertInstanceOf(ListTypeToCTypeUpgradeWizard::class, $subject);
        $this->assertTrue($subject->executeUpdate(), 'updateNecessary() returns true');
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/Upgraded/' . $fixtureDataSetFile);
        $this->assertFalse($subject->updateNecessary(), 'updateNecessary() returns false after all elements have been migrated.');
    }

    #[Test]
    public function permissionsAreMigratedAsExpected(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSets/listTypeWithBackendUserPermissions.csv');
        $subject = $this->get(ListTypeToCTypeUpgradeWizard::class);
        $this->assertInstanceOf(ListTypeToCTypeUpgradeWizard::class, $subject);
        $this->assertTrue($subject->executeUpdate(), 'updateNecessary() returns true');
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/Upgraded/listTypeWithBackendUserPermissions.csv');
        $this->assertFalse($subject->updateNecessary(), 'updateNecessary() returns false after all elements have been migrated.');
    }
}
