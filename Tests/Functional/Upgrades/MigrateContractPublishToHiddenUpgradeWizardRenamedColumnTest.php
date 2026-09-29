<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Upgrades;

use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use FGTCLB\AcademicPersons\Upgrades\MigrateContractPublishToHiddenUpgradeWizard;
use PHPUnit\Framework\Attributes\Test;

/**
 * The database analyser renames a column it no longer finds in the schema to
 * `zzz_deleted_<name>` before it drops it. An installation that let it do that
 * before the wizard ran still has the data, under that name, and the wizard
 * reads it there.
 */
final class MigrateContractPublishToHiddenUpgradeWizardRenamedColumnTest extends AbstractAcademicPersonsTestCase
{
    private const FIXTURE = __DIR__ . '/Fixtures/MigrateContractPublishToHidden/contractsWithRenamedColumn.csv';

    protected array $testExtensionsToLoad = [
        'fgtclb/environment-state-manager',
        'fgtclb/academic-base',
        'fgtclb/academic-persons',
        'tests/test-contract-publish-renamed',
    ];

    #[Test]
    public function theRenamedColumnIsMigratedLikeTheOriginalOne(): void
    {
        $this->importCSVDataSet(self::FIXTURE);
        $subject = new MigrateContractPublishToHiddenUpgradeWizard($this->getConnectionPool());

        $this->assertTrue($subject->updateNecessary());
        $subject->executeUpdate();

        $table = MigrateContractPublishToHiddenUpgradeWizardTest::TABLE;
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll();
        $hiddenByUid = [];
        foreach ($queryBuilder->select('uid', 'hidden')->from($table)->orderBy('uid')->executeQuery()->fetchAllAssociative() as $row) {
            $hiddenByUid[(int)$row['uid']] = (int)$row['hidden'];
        }
        $this->assertSame(MigrateContractPublishToHiddenUpgradeWizardTest::EXPECTED_HIDDEN, $hiddenByUid);
        $this->assertFalse($subject->updateNecessary());
    }
}
