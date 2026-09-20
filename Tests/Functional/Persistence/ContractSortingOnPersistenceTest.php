<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Persistence;

use FGTCLB\AcademicPersons\Domain\Model\Contract;
use FGTCLB\AcademicPersons\Domain\Model\OrganisationalUnit;
use FGTCLB\AcademicPersons\Domain\Model\Profile;
use FGTCLB\AcademicPersons\Domain\Repository\ContractRepository;
use FGTCLB\AcademicPersons\Domain\Repository\OrganisationalUnitRepository;
use FGTCLB\AcademicPersons\Domain\Repository\ProfileRepository;
use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;

/**
 * The frontend editor of `academic_persons_edit` writes contracts through the
 * Extbase repository, not through the DataHandler, so
 * {@see \FGTCLB\AcademicPersons\Hook\ContractSortingHook} never sees them. A
 * contract created or moved there would keep `organisational_unit_sorting` at `0`
 * and sort above everything in the organisational unit's list, tied with every
 * other one - which is what the column exists to prevent.
 *
 * These tests drive the Extbase path directly rather than through the editor:
 * `academic_persons` owns the column, and every Extbase write of the table passes
 * through the persistence events whatever writes it.
 */
final class ContractSortingOnPersistenceTest extends AbstractAcademicPersonsTestCase
{
    private const TABLE_CONTRACT = 'tx_academicpersons_domain_model_contract';
    private const FIXTURE = __DIR__ . '/Fixtures/ContractSortingOnPersistence/twoProfilesTwoUnits.csv';

    /**
     * A contract created with an organisational unit is appended to that unit's
     * list. The fixture's contracts are ranked first, so a new one has to land
     * behind them rather than at 0.
     */
    #[Test]
    public function aContractCreatedWithAnOrganisationalUnitIsAppendedToIt(): void
    {
        $this->importCSVDataSet(self::FIXTURE);
        $this->rankExistingContracts();

        $contract = new Contract();
        $contract->setPid(100);
        $contract->setProfile($this->profile(1));
        $contract->setOrganisationalUnit($this->organisationalUnit(1));
        $contract->setPosition('Assistant');
        $this->get(ContractRepository::class)->add($contract);
        $this->persist();

        $this->assertSame(5, $this->fetchRank($this->newestContractUid()));
    }

    /**
     * Changing the organisational unit appends the contract to the new one: the
     * rank it had in the old unit says nothing about where it belongs in the new
     * list.
     */
    #[Test]
    public function aContractThatChangesItsOrganisationalUnitIsAppendedToTheNewOne(): void
    {
        $this->importCSVDataSet(self::FIXTURE);
        $this->rankExistingContracts();

        $repository = $this->get(ContractRepository::class);
        $contract = $repository->findByUid(1);
        $this->assertInstanceOf(Contract::class, $contract);
        $contract->setOrganisationalUnit($this->organisationalUnit(2));
        $repository->update($contract);
        $this->persist();

        $this->assertSame(1, $this->fetchRank(1), 'The contract was not appended to the list of its new unit.');
    }

    /**
     * Clearing the organisational unit resets the column: a rank in a list the
     * record left would be restored together with the unit by a later save.
     */
    #[Test]
    public function clearingTheOrganisationalUnitResetsTheRank(): void
    {
        $this->importCSVDataSet(self::FIXTURE);
        $this->rankExistingContracts();

        $repository = $this->get(ContractRepository::class);
        $contract = $repository->findByUid(1);
        $this->assertInstanceOf(Contract::class, $contract);
        $contract->setOrganisationalUnit(null);
        $repository->update($contract);
        $this->persist();

        $this->assertSame(0, $this->fetchRank(1));
    }

    /**
     * Saving a contract without touching its organisational unit leaves its rank
     * alone - that is what makes an arrangement made in the unit form survive.
     */
    #[Test]
    public function savingAContractKeepsItsRankInTheUnit(): void
    {
        $this->importCSVDataSet(self::FIXTURE);
        $this->rankExistingContracts();

        $repository = $this->get(ContractRepository::class);
        $contract = $repository->findByUid(1);
        $this->assertInstanceOf(Contract::class, $contract);
        $contract->setPosition('Emeritus');
        $repository->update($contract);
        $this->persist();

        $this->assertSame(2, $this->fetchRank(1));
    }

    /**
     * The up/down sorting of the profile editing frontend reorders the contracts of
     * a **profile**, and writes `sorting` - the column the profile relation owns.
     * That is the right column and this change does not move it; what has to hold is
     * that the three mechanisms filling the unit's column do not react to it. The
     * contracts are written through Extbase, so the listener sees every one of them.
     */
    #[Test]
    public function reorderingTheContractsOfAProfileLeavesTheUnitArrangementAlone(): void
    {
        $this->importCSVDataSet(self::FIXTURE);
        $this->rankExistingContracts();

        $repository = $this->get(ContractRepository::class);
        foreach ([1 => 20, 2 => 10] as $uid => $sorting) {
            $contract = $repository->findByUid($uid);
            $this->assertInstanceOf(Contract::class, $contract);
            $contract->setSorting($sorting);
            $repository->update($contract);
        }
        $this->persist();

        $this->assertSame(
            [2, 1],
            $this->fetchOrder('profile', 1, 'sorting'),
            'The profile does not show its contracts in the order they were sorted into.',
        );
        $this->assertSame(
            [3, 1, 2, 4],
            $this->fetchOrder('organisational_unit', 1, 'organisational_unit_sorting'),
            'Sorting a profile changed the arrangement of the organisational unit.',
        );
    }

    /**
     * @return list<int>
     */
    private function fetchOrder(string $parentField, int $parentUid, string $sortField): array
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable(self::TABLE_CONTRACT);
        $queryBuilder->getRestrictions()->removeAll();
        $uids = $queryBuilder
            ->select('uid')
            ->from(self::TABLE_CONTRACT)
            ->where(
                $queryBuilder->expr()->eq(
                    $parentField,
                    $queryBuilder->createNamedParameter($parentUid, Connection::PARAM_INT),
                ),
            )
            ->orderBy($sortField)
            ->addOrderBy('uid')
            ->executeQuery()
            ->fetchFirstColumn();
        return array_map(intval(...), $uids);
    }

    /**
     * The state an installation is in once the upgrade wizard has run: the four
     * contracts of unit 1 are ranked in the order the unit form shows them.
     * Written straight to the database, so nothing but the assertion depends on it.
     */
    private function rankExistingContracts(): void
    {
        $connection = $this->getConnectionPool()->getConnectionForTable(self::TABLE_CONTRACT);
        foreach ([3 => 1, 1 => 2, 2 => 3, 4 => 4] as $uid => $rank) {
            $connection->update(self::TABLE_CONTRACT, ['organisational_unit_sorting' => $rank], ['uid' => $uid]);
        }
    }

    private function persist(): void
    {
        $this->get(PersistenceManagerInterface::class)->persistAll();
    }

    private function profile(int $uid): Profile
    {
        $profile = $this->get(ProfileRepository::class)->findByUid($uid);
        $this->assertInstanceOf(Profile::class, $profile);
        return $profile;
    }

    private function organisationalUnit(int $uid): OrganisationalUnit
    {
        $unit = $this->get(OrganisationalUnitRepository::class)->findByUid($uid);
        $this->assertInstanceOf(OrganisationalUnit::class, $unit);
        return $unit;
    }

    private function newestContractUid(): int
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable(self::TABLE_CONTRACT);
        $queryBuilder->getRestrictions()->removeAll();
        return (int)$queryBuilder
            ->select('uid')
            ->from(self::TABLE_CONTRACT)
            ->orderBy('uid', 'DESC')
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchOne();
    }

    private function fetchRank(int $uid): int
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable(self::TABLE_CONTRACT);
        $queryBuilder->getRestrictions()->removeAll();
        return (int)$queryBuilder
            ->select('organisational_unit_sorting')
            ->from(self::TABLE_CONTRACT)
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)),
            )
            ->executeQuery()
            ->fetchOne();
    }
}
