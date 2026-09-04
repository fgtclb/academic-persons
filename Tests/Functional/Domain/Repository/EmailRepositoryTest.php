<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Domain\Repository;

use FGTCLB\AcademicPersons\Domain\Model\Email;
use FGTCLB\AcademicPersons\Domain\Repository\EmailRepository;
use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;

/**
 * Covers the three public methods of `EmailRepository`.
 *
 * The two `IncludingHidden` methods carry the risk this class exists for: they lift the
 * `disabled` enable field so the frontend editing UI (`EXT:academic_persons_edit`
 * `ContractController`/`EmailAddressController`), `ProfileFactory` and the
 * `EXT:academic_contacts4pages` `AddressRecordProvider` can list and resolve a record an editor
 * just toggled off. A regression there does not raise anything - the record simply stops
 * appearing in the edit form, and the editor can never switch it back on.
 *
 * `findAll()` is deliberately *not* one of them: it keeps the enable fields and only widens the
 * storage page restriction, so it is the method that must be shown to still hide a hidden record.
 *
 * The fixture is built so that no assertion can pass by accident: `sorting` runs against the uid
 * order, the hidden record is the one with the lowest `sorting`, one record belongs to another
 * contract, one carries `contract = 0`, and one lives on a second storage page.
 */
final class EmailRepositoryTest extends AbstractAcademicPersonsTestCase
{
    private const FIXTURE = __DIR__ . '/Fixtures/EmailRepositoryTest/emails.csv';

    private function subject(): EmailRepository
    {
        return $this->get(EmailRepository::class);
    }

    /**
     * @param QueryResultInterface<int, Email> $result
     * @return int[] uids in the order the repository returned them
     */
    private function uidsInResultOrder(QueryResultInterface $result): array
    {
        $uids = [];
        foreach ($result as $email) {
            $uids[] = (int)$email->getUid();
        }
        return $uids;
    }

    /**
     * `findAll()` sets no orderings at all, so the order it returns is whatever the DBMS
     * produces. Sorting the uids keeps the expectation honest across the four DBMS the
     * functional suite runs on.
     *
     * @param QueryResultInterface<int, Email> $result
     * @return int[]
     */
    private function sortedUids(QueryResultInterface $result): array
    {
        $uids = $this->uidsInResultOrder($result);
        sort($uids);
        return $uids;
    }

    /**
     * The storage page restriction is lifted, so the record on pid 2 is part of the result even
     * though no storage page was ever configured for the test.
     */
    #[Test]
    public function findAllReturnsRecordsFromEveryStoragePage(): void
    {
        $this->importCSVDataSet(self::FIXTURE);

        $this->assertSame([1, 3, 5, 6, 7], $this->sortedUids($this->subject()->findAll()));
    }

    /**
     * The counterpart of `findByContractIncludingHidden()`: `findAll()` never lifts the
     * `disabled` field, so uid 2 must stay out. If this and the `IncludingHidden` expectation
     * ever agree, one of the two methods has lost its purpose.
     */
    #[Test]
    public function findAllExcludesHiddenRecords(): void
    {
        $this->importCSVDataSet(self::FIXTURE);

        $this->assertNotContains(2, $this->sortedUids($this->subject()->findAll()));
    }

    #[Test]
    public function findAllExcludesDeletedRecords(): void
    {
        $this->importCSVDataSet(self::FIXTURE);

        $this->assertNotContains(4, $this->sortedUids($this->subject()->findAll()));
    }

    #[Test]
    public function findAllReturnsAnEmptyResultWhenNoRecordExists(): void
    {
        $this->assertSame([], $this->sortedUids($this->subject()->findAll()));
    }

    /**
     * The promise of the method name and the whole reason it exists: uid 2 is hidden and comes
     * back, uid 4 is deleted and does not - `setEnableFieldsToBeIgnored(['disabled'])` narrows
     * the blanket `setIgnoreEnableFields(true)` to exactly the visibility toggle.
     *
     * The order is the `sorting` order, not the uid order: uid 2 is first because it has the
     * lowest `sorting`, and the hidden record being the first one is what an editor sees.
     */
    #[Test]
    public function findByContractIncludingHiddenReturnsHiddenRecordsInSortingOrder(): void
    {
        $this->importCSVDataSet(self::FIXTURE);

        $result = $this->subject()->findByContractIncludingHidden(1);

        $this->assertSame([2, 3, 1, 7], $this->uidsInResultOrder($result));
    }

    /**
     * Records that share a `sorting` value - the state of every record an editor never
     * reordered - are returned in uid order within that value: the `uid` tiebreaker the
     * repository rule of docs/architecture/database-queries.md asks for.
     *
     * SQLite cannot make this fail: uid is the rowid, so uid order is its natural order for
     * equal `sorting` values and the assertion passes with or without the tiebreaker there.
     * PostgreSQL can, and does: without the tiebreaker this test is red on `-d postgres`,
     * which is where it was proven to fail.
     */
    #[Test]
    public function findByContractIncludingHiddenBreaksEqualSortingTiesByUid(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/EmailRepositoryTest/equalSorting.csv');

        $result = $this->subject()->findByContractIncludingHidden(1);

        $this->assertSame([2, 3, 1, 4], $this->uidsInResultOrder($result));
    }

    /**
     * The values are read through the model rather than the uid alone, so a mapping that
     * silently returns the wrong row is caught as well.
     */
    #[Test]
    public function findByContractIncludingHiddenMapsTheRecordValues(): void
    {
        $this->importCSVDataSet(self::FIXTURE);

        $addresses = [];
        foreach ($this->subject()->findByContractIncludingHidden(1) as $email) {
            $addresses[] = $email->getEmail();
        }

        $this->assertSame(
            ['bonn@example.org', 'cologne@example.org', 'berlin@example.org', 'gera@example.org'],
            $addresses,
        );
    }

    /**
     * uid 5 belongs to contract 2 and is perfectly visible, which makes it the row that would
     * leak first if the `contract` constraint were dropped or widened.
     */
    #[Test]
    public function findByContractIncludingHiddenDoesNotReturnRecordsOfAnotherContract(): void
    {
        $this->importCSVDataSet(self::FIXTURE);

        $this->assertSame([5], $this->uidsInResultOrder($this->subject()->findByContractIncludingHidden(2)));
    }

    /**
     * Contract 3 exists and simply has no email addresses - the case a profile without an email
     * address produces, which must be an empty result rather than everything.
     */
    #[Test]
    public function findByContractIncludingHiddenReturnsAnEmptyResultForAContractWithoutRecords(): void
    {
        $this->importCSVDataSet(self::FIXTURE);

        $this->assertSame([], $this->uidsInResultOrder($this->subject()->findByContractIncludingHidden(3)));
    }

    #[Test]
    public function findByContractIncludingHiddenReturnsAnEmptyResultForAnUnknownContract(): void
    {
        $this->importCSVDataSet(self::FIXTURE);

        $this->assertSame([], $this->uidsInResultOrder($this->subject()->findByContractIncludingHidden(999)));
    }

    /**
     * `contract` is `int unsigned DEFAULT '0' NOT NULL`, so an email address that was never
     * assigned to a contract carries 0 - and 0 is also what an unresolved argument degrades to.
     * The method treats it as an ordinary value and returns exactly those orphaned records,
     * which is worth pinning down: it is neither a wildcard nor an error.
     */
    #[Test]
    public function findByContractIncludingHiddenTreatsContractZeroAsAnOrdinaryValue(): void
    {
        $this->importCSVDataSet(self::FIXTURE);

        $this->assertSame([6], $this->uidsInResultOrder($this->subject()->findByContractIncludingHidden(0)));
    }

    #[Test]
    public function findByUidIncludingHiddenReturnsAVisibleRecord(): void
    {
        $this->importCSVDataSet(self::FIXTURE);

        $email = $this->subject()->findByUidIncludingHidden(1);

        $this->assertInstanceOf(Email::class, $email);
        $this->assertSame(1, $email->getUid());
        $this->assertSame('berlin@example.org', $email->getEmail());
    }

    /**
     * Extbase argument mapping respects the enable fields, so without this method the frontend
     * editing UI could list a hidden email address but not open it for editing.
     */
    #[Test]
    public function findByUidIncludingHiddenReturnsAHiddenRecord(): void
    {
        $this->importCSVDataSet(self::FIXTURE);

        $email = $this->subject()->findByUidIncludingHidden(2);

        $this->assertInstanceOf(Email::class, $email);
        $this->assertSame(2, $email->getUid());
        $this->assertSame('bonn@example.org', $email->getEmail());
    }

    /**
     * pid 2 is outside any storage page the query could have been restricted to.
     */
    #[Test]
    public function findByUidIncludingHiddenIgnoresTheStoragePage(): void
    {
        $this->importCSVDataSet(self::FIXTURE);

        $email = $this->subject()->findByUidIncludingHidden(7);

        $this->assertInstanceOf(Email::class, $email);
        $this->assertSame('gera@example.org', $email->getEmail());
    }

    /**
     * Only `disabled` is ignored. A deleted record stays gone, an unknown uid resolves to
     * nothing, and `0` - what an unmapped Extbase argument degrades to - must not accidentally
     * return the first row of the table.
     */
    #[Test]
    #[DataProvider('uidsWithoutAResolvableRecordDataProvider')]
    public function findByUidIncludingHiddenReturnsNull(int $uid): void
    {
        $this->importCSVDataSet(self::FIXTURE);

        $this->assertNull($this->subject()->findByUidIncludingHidden($uid));
    }

    /**
     * @return \Generator<string, array{0: int}>
     */
    public static function uidsWithoutAResolvableRecordDataProvider(): \Generator
    {
        yield 'deleted record' => [4];
        yield 'unknown uid' => [999];
        yield 'uid zero' => [0];
        yield 'negative uid' => [-1];
    }
}
