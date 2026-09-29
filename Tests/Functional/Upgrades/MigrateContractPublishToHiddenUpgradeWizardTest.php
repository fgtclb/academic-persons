<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Upgrades;

use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use FGTCLB\AcademicPersons\Upgrades\MigrateContractPublishToHiddenUpgradeWizard;
use PHPUnit\Framework\Attributes\Test;

/**
 * The installation still has the `publish` column, which the fixture extension
 * brings back, as it is before the database compare removes it.
 *
 * The fixture holds a contract that was not published and one that was, each
 * with a translation whose own flag says the opposite, a published and a not
 * published contract that are hidden already, the latter with a visible
 * translation, a deleted contract, a contract for all languages, workspace
 * versions of default records and of translations, and translations without a
 * default record.
 */
final class MigrateContractPublishToHiddenUpgradeWizardTest extends AbstractAcademicPersonsTestCase
{
    public const TABLE = 'tx_academicpersons_domain_model_contract';

    /**
     * What the wizard leaves in `hidden`, by uid. The default language record
     * decides, and its translations follow it whatever their own flag says: 2 is
     * hidden with 1, 4 stays visible with 3, 10 is hidden with 6.
     *
     * A workspace version of a translation names the live default record as its
     * parent. It follows the version of that record in its own workspace where
     * there is one: 11 is hidden with 8, the version of 3, and 16 stays visible
     * with 14, the version of 13, while the live 15 is hidden with 13. Without
     * such a version it follows the live record: 12 is hidden with 1.
     *
     * A translation without a default record decides by its own flag: 17, and
     * 18, whose default record is gone.
     */
    public const EXPECTED_HIDDEN = [
        1 => 1, 2 => 1, 3 => 0, 4 => 0, 5 => 1, 6 => 1, 7 => 1, 8 => 1, 9 => 1, 10 => 1,
        11 => 1, 12 => 1, 13 => 1, 14 => 0, 15 => 1, 16 => 0, 17 => 1, 18 => 1,
    ];

    private const FIXTURE = __DIR__ . '/Fixtures/MigrateContractPublishToHidden/contracts.csv';

    protected array $testExtensionsToLoad = [
        'fgtclb/environment-state-manager',
        'fgtclb/academic-base',
        'fgtclb/academic-persons',
        'tests/test-contract-publish-column',
    ];

    #[Test]
    public function updateIsNecessaryWhileAContractThatWasNotPublishedIsVisible(): void
    {
        $this->importCSVDataSet(self::FIXTURE);

        $this->assertTrue($this->getSubject()->updateNecessary());
    }

    #[Test]
    public function executeUpdateHidesContractsThatWereNotPublishedWithTheirTranslations(): void
    {
        $this->importCSVDataSet(self::FIXTURE);

        $this->assertTrue($this->getSubject()->executeUpdate());

        $this->assertSame(self::EXPECTED_HIDDEN, $this->fetchHiddenByUid());
    }

    #[Test]
    public function nothingIsLeftToDoAfterTheUpdate(): void
    {
        $this->importCSVDataSet(self::FIXTURE);
        $this->getSubject()->executeUpdate();

        $this->assertFalse($this->getSubject()->updateNecessary());
    }

    #[Test]
    public function aSecondRunChangesNothing(): void
    {
        $this->importCSVDataSet(self::FIXTURE);
        $this->getSubject()->executeUpdate();

        $this->getSubject()->executeUpdate();

        $this->assertSame(self::EXPECTED_HIDDEN, $this->fetchHiddenByUid());
    }

    /**
     * A translation that is visible below a hidden record that was not published
     * is work of its own: the core copies `hidden` into it only when the DataHandler
     * saves the record, so it may never have been copied.
     */
    #[Test]
    public function updateIsNecessaryWhileOnlyATranslationIsLeftVisible(): void
    {
        $this->importCSVDataSet(self::FIXTURE);
        $this->getSubject()->executeUpdate();
        $this->getConnectionPool()->getConnectionForTable(self::TABLE)->update(self::TABLE, ['hidden' => 0], ['uid' => 10]);

        $this->assertTrue($this->getSubject()->updateNecessary());
    }

    /**
     * An installation that never had a contract gets no work offered.
     */
    #[Test]
    public function updateIsNotNecessaryWithoutContracts(): void
    {
        $this->assertFalse($this->getSubject()->updateNecessary());
    }

    private function getSubject(): MigrateContractPublishToHiddenUpgradeWizard
    {
        return new MigrateContractPublishToHiddenUpgradeWizard($this->getConnectionPool());
    }

    /**
     * @return array<int, int>
     */
    private function fetchHiddenByUid(): array
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable(self::TABLE);
        $queryBuilder->getRestrictions()->removeAll();
        $rows = $queryBuilder
            ->select('uid', 'hidden')
            ->from(self::TABLE)
            ->orderBy('uid')
            ->executeQuery()
            ->fetchAllAssociative();
        $hiddenByUid = [];
        foreach ($rows as $row) {
            $hiddenByUid[(int)$row['uid']] = (int)$row['hidden'];
        }
        return $hiddenByUid;
    }
}
