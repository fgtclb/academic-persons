<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Domain\Repository;

use FGTCLB\AcademicPersons\Domain\Model\ProfileInformation;
use FGTCLB\AcademicPersons\Domain\Repository\ProfileInformationRepository;
use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;

/**
 * Coverage for `ProfileInformationRepository::findAll()`, the untested half of the repository -
 * `findByProfileAndType()` is pinned down by `ProfileInformationRepositoryTest`.
 *
 * The override exists for one line, `setRespectStoragePage(false)`. Without a request the
 * Extbase `QueryFactory` falls back to `storagePid = 0`, so the inherited implementation would
 * answer with the records on page `0` - with nothing, in an installation that stores its
 * profiles anywhere else.
 *
 * The contrast with `findByProfileAndType()` is what makes the rest of this class worth writing:
 * that method sorts explicitly by `sorting, uid`, this one sets no orderings at all and
 * `ProfileInformationRepository` declares no `$defaultOrderings`. The TCA `sortby`/`default_sortby`
 * of the table is a backend concept Extbase does not read, so the statement carries no `ORDER BY`
 * and the result order belongs to the DBMS. The assertions below therefore compare sorted uid
 * sets rather than an order.
 */
final class ProfileInformationRepositoryFindAllTest extends AbstractAcademicPersonsTestCase
{
    #[Test]
    public function profileInformationIsReturnedFromEveryStoragePage(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ProfileInformationRepositoryFindAll/profileInformation.csv');

        $result = $this->subject()->findAll();

        $this->assertSame([1, 2, 5], $this->resultUids($result));
        $this->assertSame([20, 30], $this->resultPids($result));
    }

    /**
     * The entries of a profile are rendered on its detail page, so a hidden one leaking through
     * here would publish a publication an editor took offline.
     */
    #[Test]
    public function hiddenProfileInformationIsNotReturned(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ProfileInformationRepositoryFindAll/profileInformation.csv');

        $this->assertNotContains(3, $this->resultUids($this->subject()->findAll()));
    }

    #[Test]
    public function deletedProfileInformationIsNotReturned(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ProfileInformationRepositoryFindAll/profileInformation.csv');

        $this->assertNotContains(4, $this->resultUids($this->subject()->findAll()));
    }

    /**
     * The language restriction is left in place, so the default language selects
     * `sys_language_uid IN (0, -1)`. An entry marked "all languages" - a publication title that
     * is not translated on purpose - belongs to every language's result.
     */
    #[Test]
    public function profileInformationForAllLanguagesIsReturned(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ProfileInformationRepositoryFindAll/profileInformation.csv');

        $this->assertContains(5, $this->resultUids($this->subject()->findAll()));
    }

    /**
     * The assertion counts occurrences rather than looking for the translation's own uid `6`:
     * Extbase maps a translated row onto the uid of its default language record, so a leaking
     * translation shows up as entry `1` a second time, never under its own uid.
     */
    #[Test]
    public function translationDoesNotAppearBesideItsDefaultLanguageRecord(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ProfileInformationRepositoryFindAll/profileInformation.csv');

        $uids = $this->resultUids($this->subject()->findAll());

        $this->assertContains(1, $uids);
        $this->assertCount(1, array_keys($uids, 1, true));
    }

    /**
     * The type is the record type of the table. `findAll()` passes no type, so entries of every
     * configured type come back together - which is exactly what `findByProfileAndType()` is
     * there to narrow down again.
     */
    #[Test]
    public function entriesOfEveryTypeAreReturned(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ProfileInformationRepositoryFindAll/profileInformation.csv');

        $types = [];
        foreach ($this->subject()->findAll() as $profileInformation) {
            $types[] = $profileInformation->getType();
        }
        sort($types);

        $this->assertSame(['type_1', 'type_1', 'type_2'], $types);
    }

    #[Test]
    public function emptyTableYieldsAnEmptyResult(): void
    {
        $result = $this->subject()->findAll();

        $this->assertCount(0, $result);
        $this->assertSame([], $this->resultUids($result));
    }

    private function subject(): ProfileInformationRepository
    {
        return $this->get(ProfileInformationRepository::class);
    }

    /**
     * @param QueryResultInterface<int, ProfileInformation> $result
     * @return int[]
     */
    private function resultUids(QueryResultInterface $result): array
    {
        $uids = [];
        foreach ($result as $profileInformation) {
            $uids[] = (int)$profileInformation->getUid();
        }
        sort($uids);
        return $uids;
    }

    /**
     * @param QueryResultInterface<int, ProfileInformation> $result
     * @return int[]
     */
    private function resultPids(QueryResultInterface $result): array
    {
        $pids = [];
        foreach ($result as $profileInformation) {
            $pids[] = (int)$profileInformation->getPid();
        }
        $pids = array_values(array_unique($pids));
        sort($pids);
        return $pids;
    }
}
