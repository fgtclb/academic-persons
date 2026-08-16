<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Domain\Repository;

use FGTCLB\AcademicPersons\Domain\Model\Location;
use FGTCLB\AcademicPersons\Domain\Repository\LocationRepository;
use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;

/**
 * `LocationRepository::findAll()` fills the location select of the contract form in
 * `EXT:academic_persons_edit` (`ContractController::newAction()`/`editAction()`). A record
 * that silently drops out of this result is an option an editor cannot pick any more, and
 * nothing in the form reports it — which is what makes the query settings worth pinning
 * rather than the method body.
 *
 * The method is byte for byte the same as `FunctionTypeRepository::findAll()` and
 * `OrganisationalUnitRepository::findAll()`, and the three tables carry the same `ctrl`
 * capabilities. The three test classes are therefore deliberately parallel: they exist so a
 * change that touches one of the repositories and not the others shows up as a difference
 * between test classes rather than as an option missing in production.
 *
 * The one setting the method makes explicit is `setRespectStoragePage(false)`, and the
 * production code carries a `@todo` calling it bad design for multi site instances. It is
 * load-bearing all the same: `QueryFactory::create()` fills the storage page list from
 * `persistence.storagePid` and falls back to `'0'`, so without the lift the query becomes
 * `pid = <plugin storage pid>` and every location stored anywhere else vanishes from the
 * select. In this test case there is no request, the fallback applies, and dropping the
 * call empties the result entirely.
 */
final class LocationRepositoryTest extends AbstractAcademicPersonsTestCase
{
    private const FIXTURE = __DIR__ . '/Fixtures/LocationRepository/locations.csv';

    private function subject(): LocationRepository
    {
        return $this->get(LocationRepository::class);
    }

    /**
     * @param QueryResultInterface<int, Location> $result
     * @return int[]
     */
    private function sortedUids(QueryResultInterface $result): array
    {
        $uids = [];
        foreach ($result as $location) {
            $uids[] = (int)$location->getUid();
        }
        sort($uids);
        return $uids;
    }

    /**
     * @param QueryResultInterface<int, Location> $result
     * @return string[]
     */
    private function titles(QueryResultInterface $result): array
    {
        $titles = [];
        foreach ($result as $location) {
            $titles[] = $location->getTitle();
        }
        return $titles;
    }

    /**
     * An installation that has not been seeded yet renders the form with an empty select
     * rather than failing, so the empty case is part of the contract.
     */
    #[Test]
    public function findAllReturnsAnEmptyResultWhenNoRecordExists(): void
    {
        $this->assertCount(0, $this->subject()->findAll());
    }

    /**
     * The three visible default language records sit on three different pages, none of
     * which is the storage page Extbase falls back to. This is the case that fails the
     * moment `setRespectStoragePage(false)` is lost — see the class docblock.
     */
    #[Test]
    public function findAllReturnsRecordsFromEveryPage(): void
    {
        $this->importCSVDataSet(self::FIXTURE);

        $this->assertSame([1, 2, 3, 4], $this->sortedUids($this->subject()->findAll()));
    }

    /**
     * `enablecolumns.disabled` is the only enable column of the table, and the repository
     * does not lift it. A hidden location is an option an editor deliberately took out of
     * the form, so it must not come back through `findAll()`.
     */
    #[Test]
    public function findAllOmitsHiddenRecords(): void
    {
        $this->importCSVDataSet(self::FIXTURE);

        $uids = $this->sortedUids($this->subject()->findAll());

        $this->assertContains(1, $uids);
        $this->assertNotContains(5, $uids);
    }

    /**
     * Uid 6 sits on the same page as a visible record, so an empty result would not satisfy
     * this test — only a result that keeps the neighbour and drops the deleted record does.
     */
    #[Test]
    public function findAllOmitsDeletedRecords(): void
    {
        $this->importCSVDataSet(self::FIXTURE);

        $uids = $this->sortedUids($this->subject()->findAll());

        $this->assertContains(1, $uids);
        $this->assertNotContains(6, $uids);
    }

    /**
     * The near miss: uid 4 carries `sys_language_uid = -1` and uid 7 is a translation of
     * uid 1. Language handling is left at the Extbase default, so the "all languages"
     * record is a result of its own while the translation is represented by its default
     * language parent — the form must not offer the same location twice.
     */
    #[Test]
    public function findAllKeepsAllLanguageRecordsAndOmitsTranslations(): void
    {
        $this->importCSVDataSet(self::FIXTURE);

        $uids = $this->sortedUids($this->subject()->findAll());

        $this->assertContains(4, $uids);
        $this->assertNotContains(7, $uids);
        // Lifting `respectSysLanguage` does not add uid 7 to the result, it overlays the
        // translation onto uid 1 — the select then offers the same record twice, which is
        // only visible as a duplicate.
        $this->assertSame(array_values(array_unique($uids)), $uids);
    }

    /**
     * The repository declares no `$defaultOrderings` and `findAll()` adds none, so the TCA
     * `default_sortby` of `title asc` — which orders the record list in the backend — does
     * **not** reach the select of the frontend edit form. The fixture titles are
     * deliberately in reverse alphabetical order to make the difference visible: what comes
     * back is the natural order of the table, not `Alpha, Beta, Mu, Zeta`.
     *
     * A failure here on another DBMS is the finding, not a flaky test: it would mean the
     * order these three repositories return is not stable and has to be requested
     * explicitly.
     */
    #[Test]
    public function findAllDoesNotApplyTheTcaDefaultSortby(): void
    {
        $this->importCSVDataSet(self::FIXTURE);

        $titles = $this->titles($this->subject()->findAll());

        $this->assertSame(['Zeta Campus', 'Mu Campus', 'Alpha Campus', 'Beta Campus'], $titles);
        $sortedByTitle = $titles;
        sort($sortedByTitle);
        $this->assertNotSame($sortedByTitle, $titles);
    }
}
