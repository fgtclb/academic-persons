<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Domain\Repository;

use FGTCLB\AcademicPersons\Domain\Model\Contract;
use FGTCLB\AcademicPersons\Domain\Repository\ContractRepository;
use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;

final class ContractRepositoryShowHiddenRecordsTest extends AbstractAcademicPersonsTestCase
{
    private function getContractRepository(): ContractRepository
    {
        return $this->get(ContractRepository::class);
    }

    /**
     * @param QueryResultInterface<int, Contract> $result
     * @return int[]
     */
    private function resultUids(QueryResultInterface $result): array
    {
        $uids = [];
        foreach ($result as $contract) {
            $uids[] = (int)$contract->getUid();
        }
        sort($uids);
        return $uids;
    }

    #[Test]
    public function findByUidsExcludesHiddenRecordsByDefault(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ShowHiddenRecords/contracts.csv');
        $result = $this->getContractRepository()->findByUids([1, 2, 3, 4]);
        $this->assertSame([1, 3], $this->resultUids($result));
    }

    #[Test]
    public function findByUidsIncludesHiddenRecordsWhenRequested(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ShowHiddenRecords/contracts.csv');
        $result = $this->getContractRepository()->findByUids([1, 2, 3, 4], true);
        $this->assertSame([1, 2, 3, 4], $this->resultUids($result));
    }

    /**
     * The result orders by `uid` ascending since ACE-491, whatever order the uids were
     * requested in - `in()` does not preserve the argument order, and before the explicit
     * ordering the result order belonged to the DBMS. The order of the editor's selection
     * is deliberately not reproduced; that would be a behaviour change beyond making the
     * list reproducible.
     */
    #[Test]
    public function findByUidsReturnsUidOrderRegardlessOfTheRequestedOrder(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ShowHiddenRecords/contracts.csv');

        $uids = [];
        foreach ($this->getContractRepository()->findByUids([3, 1]) as $contract) {
            $uids[] = (int)$contract->getUid();
        }

        $this->assertSame([1, 3], $uids);
    }
}
