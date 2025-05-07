<?php

declare(strict_types=1);

namespace Fgtclb\AcademicPersons\Tests\Functional\Upgrades;

use Fgtclb\AcademicPersons\Upgrades\PluginUpgradeWizard;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\TestCase\FunctionalTestCase;

final class PluginUpgradeWizardTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = [
        'typo3/cms-install',
        'typo3/cms-rte-ckeditor',
    ];

    protected array $testExtensionsToLoad = [
        'fgtclb/academic-persons',
    ];

    #[Test]
    public function updateNecessaryReturnsFalseWhenListTypeRecordsAreAvailable(): void
    {
        $subject = $this->get(PluginUpgradeWizard::class);
        $this->assertInstanceOf(PluginUpgradeWizard::class, $subject);
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
        $subject = $this->get(PluginUpgradeWizard::class);
        $this->assertInstanceOf(PluginUpgradeWizard::class, $subject);
        $this->assertTrue($subject->updateNecessary(), 'updateNecessary() returns true');
    }

    #[DataProvider('ttContentPluginDataSets')]
    #[Test]
    public function executeUpdateMigratesContentElementsAndReturnsTrue(
        string $fixtureDataSetFile,
    ): void {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSets/' . $fixtureDataSetFile);
        $subject = $this->get(PluginUpgradeWizard::class);
        $this->assertInstanceOf(PluginUpgradeWizard::class, $subject);
        $this->assertTrue($subject->executeUpdate(), 'updateNecessary() returns true');
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/Upgraded/' . $fixtureDataSetFile);
    }
}
