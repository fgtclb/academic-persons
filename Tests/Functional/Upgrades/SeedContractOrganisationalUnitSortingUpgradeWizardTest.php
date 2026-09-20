<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Upgrades;

use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use FGTCLB\AcademicPersons\Upgrades\SeedContractOrganisationalUnitSortingUpgradeWizard;
use PHPUnit\Framework\Attributes\Test;

/**
 * The fixture holds contracts of two organisational units plus one contract without
 * a unit, and its `sorting` values contradict uid order in both units, so the
 * expected result differs from every order the database could return by accident.
 *
 * Unit 1 holds uid 3 (`sorting` 1), uids 1 and 2 (both `sorting` 2, settled by uid)
 * and the deleted uid 7 (`sorting` 3); unit 2 holds uid 5 (`sorting` 5) before
 * uid 4 (`sorting` 7). Uid 6 carries no unit and has to stay at 0.
 */
final class SeedContractOrganisationalUnitSortingUpgradeWizardTest extends AbstractAcademicPersonsTestCase
{
    private const TABLE = 'tx_academicpersons_domain_model_contract';
    private const FIXTURE = __DIR__ . '/Fixtures/SeedContractOrganisationalUnitSorting/contractsOfTwoUnits.csv';

    #[Test]
    public function updateIsNecessaryWhileAContractOfAUnitIsUnseeded(): void
    {
        $this->importCSVDataSet(self::FIXTURE);

        $this->assertTrue($this->getSubject()->updateNecessary());
    }

    #[Test]
    public function executeUpdateNumbersTheContractsOfEveryUnit(): void
    {
        $this->importCSVDataSet(self::FIXTURE);

        $this->assertTrue($this->getSubject()->executeUpdate());

        $this->assertSame(
            [1 => 2, 2 => 3, 3 => 1, 4 => 2, 5 => 1, 6 => 0, 7 => 4],
            $this->fetchUnitSortingByUid(),
        );
    }

    #[Test]
    public function nothingIsLeftToDoAfterTheUpdate(): void
    {
        $this->importCSVDataSet(self::FIXTURE);
        $this->getSubject()->executeUpdate();

        $this->assertFalse($this->getSubject()->updateNecessary());
    }

    /**
     * Running it twice writes the same numbers: the wizard derives them from
     * `sorting`, which it does not touch.
     */
    #[Test]
    public function executeUpdateIsIdempotent(): void
    {
        $this->importCSVDataSet(self::FIXTURE);
        $this->getSubject()->executeUpdate();
        $afterFirstRun = $this->fetchUnitSortingByUid();

        $this->getSubject()->executeUpdate();

        $this->assertSame($afterFirstRun, $this->fetchUnitSortingByUid());
    }

    /**
     * An installation that has no contract with an organisational unit at all is not
     * offered the wizard.
     */
    #[Test]
    public function updateIsNotNecessaryWithoutContractsCarryingAUnit(): void
    {
        $this->assertFalse($this->getSubject()->updateNecessary());
    }

    /**
     * A second run **appends**, it does not renumber: the ranks the first run wrote -
     * and any arrangement an editor made on top of them - are left alone, and only a
     * contract that has none is given one, behind them.
     */
    #[Test]
    public function executeUpdateAppendsWithoutResettingWhatIsAlreadyRanked(): void
    {
        $this->importCSVDataSet(self::FIXTURE);
        $this->getSubject()->executeUpdate();
        $afterFirstRun = $this->fetchUnitSortingByUid();
        // The one contract without a organisational unit joins it, the way the DataHandler hook
        // would not have to: written straight to the database, so nothing ranks it.
        $this->assignParentWithoutRank(6, 1);

        $this->getSubject()->executeUpdate();

        $this->assertSame([1 => 2, 2 => 3, 3 => 1, 4 => 2, 5 => 1, 6 => 5, 7 => 4], $this->fetchUnitSortingByUid());
        unset($afterFirstRun[6]);
        $this->assertSame(
            $afterFirstRun,
            array_diff_key($this->fetchUnitSortingByUid(), [6 => true]),
            'The second run changed a rank it had already written.',
        );
    }

    /**
     * Writes the parent column straight to the database, without the DataHandler, so
     * the row ends up in the state an installation is in before the wizard has run.
     */
    private function assignParentWithoutRank(int $uid, int $parentUid): void
    {
        $connection = $this->getConnectionPool()->getConnectionForTable(self::TABLE);
        $connection->update(self::TABLE, ['organisational_unit' => $parentUid, 'organisational_unit_sorting' => 0], ['uid' => $uid]);
    }

    private function getSubject(): SeedContractOrganisationalUnitSortingUpgradeWizard
    {
        $subject = $this->get(SeedContractOrganisationalUnitSortingUpgradeWizard::class);
        $this->assertInstanceOf(SeedContractOrganisationalUnitSortingUpgradeWizard::class, $subject);
        return $subject;
    }

    /**
     * @return array<int, int>
     */
    private function fetchUnitSortingByUid(): array
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable(self::TABLE);
        $queryBuilder->getRestrictions()->removeAll();
        $rows = $queryBuilder
            ->select('uid', 'organisational_unit_sorting')
            ->from(self::TABLE)
            ->orderBy('uid')
            ->executeQuery()
            ->fetchAllAssociative();
        $sortingByUid = [];
        foreach ($rows as $row) {
            $sortingByUid[(int)$row['uid']] = (int)$row['organisational_unit_sorting'];
        }
        return $sortingByUid;
    }
}
