<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Domain\Repository;

use FGTCLB\AcademicPersons\Domain\Model\Contract;
use FGTCLB\AcademicPersons\Domain\Repository\ContractRepository;
use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;

/**
 * Coverage for `ContractRepository::findAll()`.
 *
 * `findByUids()` is pinned down for hidden records by `ContractRepositoryShowHiddenRecordsTest`,
 * and `getContractItemsForTcaItemsProcFunc()` - which does nothing but delegate here - is
 * exercised through FormEngine by `Tests/Functional/Backend/FormEngine/ContractItemsTest`. That
 * makes this method the one every contract select item in the backend is built from, so what it
 * leaves out is what an editor cannot pick.
 *
 * The override exists for one line, `setRespectStoragePage(false)`. Without a request the
 * Extbase `QueryFactory` falls back to `storagePid = 0`; the inherited implementation would
 * therefore offer the contracts stored on page `0`, which in a real installation is none of
 * them.
 *
 * No orderings are set and `ContractRepository` declares no `$defaultOrderings`. The table's TCA
 * `sortby`/`default_sortby` is a backend concept Extbase does not read, so the statement carries
 * no `ORDER BY` and the result order belongs to the DBMS - the assertions compare sorted uid
 * sets rather than an order.
 */
final class ContractRepositoryFindAllTest extends AbstractAcademicPersonsTestCase
{
    #[Test]
    public function contractsAreReturnedFromEveryStoragePage(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ContractRepositoryFindAll/contracts.csv');

        $result = $this->subject()->findAll();

        $this->assertSame([1, 2, 5], $this->resultUids($result));
        $this->assertSame([20, 30], $this->resultPids($result));
    }

    #[Test]
    public function hiddenContractsAreNotReturned(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ContractRepositoryFindAll/contracts.csv');

        $this->assertNotContains(3, $this->resultUids($this->subject()->findAll()));
    }

    #[Test]
    public function deletedContractsAreNotReturned(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ContractRepositoryFindAll/contracts.csv');

        $this->assertNotContains(4, $this->resultUids($this->subject()->findAll()));
    }

    /**
     * The language restriction is left in place, so the default language selects
     * `sys_language_uid IN (0, -1)`.
     */
    #[Test]
    public function contractForAllLanguagesIsReturned(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ContractRepositoryFindAll/contracts.csv');

        $this->assertContains(5, $this->resultUids($this->subject()->findAll()));
    }

    /**
     * A translated contract is an overlay of its default language record. Returning it as a
     * record of its own would put the same contract into the backend select twice, once per
     * language.
     *
     * The assertion counts occurrences rather than looking for the translation's own uid `6`:
     * Extbase maps a translated row onto the uid of its default language record, so a leaking
     * translation shows up as contract `1` a second time, never under its own uid.
     */
    #[Test]
    public function translationDoesNotAppearBesideItsDefaultLanguageRecord(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ContractRepositoryFindAll/contracts.csv');

        $uids = $this->resultUids($this->subject()->findAll());

        $this->assertContains(1, $uids);
        $this->assertCount(1, array_keys($uids, 1, true));
    }

    /**
     * The contracts come back mapped, not as raw rows - `ContractItemsTest` builds its select
     * labels from the model, so an empty `position` there would be a broken select item here.
     */
    #[Test]
    public function returnedContractsAreMappedModels(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ContractRepositoryFindAll/contracts.csv');

        $positions = [];
        foreach ($this->subject()->findAll() as $contract) {
            $this->assertInstanceOf(Contract::class, $contract);
            $positions[] = $contract->getPosition();
        }
        sort($positions);

        $this->assertSame(['Dean', 'Professor', 'Research Assistant'], $positions);
    }

    #[Test]
    public function emptyTableYieldsAnEmptyResult(): void
    {
        $result = $this->subject()->findAll();

        $this->assertCount(0, $result);
        $this->assertSame([], $this->resultUids($result));
    }

    private function subject(): ContractRepository
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

    /**
     * @param QueryResultInterface<int, Contract> $result
     * @return int[]
     */
    private function resultPids(QueryResultInterface $result): array
    {
        $pids = [];
        foreach ($result as $contract) {
            $pids[] = (int)$contract->getPid();
        }
        $pids = array_values(array_unique($pids));
        sort($pids);
        return $pids;
    }
}
