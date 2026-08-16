<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Domain\Repository;

use FGTCLB\AcademicPersons\Domain\Model\Profile;
use FGTCLB\AcademicPersons\Domain\Repository\ProfileRepository;
use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;

/**
 * Coverage for `ProfileRepository::findAll()`, which overrides the Extbase one for a single
 * reason: it switches the storage page restriction off. Without a request the Extbase
 * `QueryFactory` falls back to `storagePid = 0`, so the inherited implementation would answer
 * with the records on page `0` - in practice with nothing at all. That makes the storage page
 * cases below the ones that actually pin the override down; everything else pins the settings
 * the method deliberately leaves alone.
 *
 * `findAll()` sets no orderings and `ProfileRepository` declares no `$defaultOrderings`, so the
 * statement carries no `ORDER BY` and the result order is whatever the DBMS returns. The
 * assertions therefore compare sorted uid sets - asserting an order here would pin SQLite
 * behaviour that MySQL, MariaDB and PostgreSQL are free to differ on.
 *
 * The hidden and deleted expectations look like the ones in
 * `ProfileRepositoryShowHiddenRecordsTest`, but they are about a different method: there is no
 * `$showHidden` argument on `findAll()`, and there is no way to reach hidden profiles through it.
 */
final class ProfileRepositoryFindAllTest extends AbstractAcademicPersonsTestCase
{
    #[Test]
    public function profilesAreReturnedFromEveryStoragePage(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ProfileRepositoryFindAll/profiles.csv');

        $result = $this->subject()->findAll();

        $this->assertSame([1, 2, 5], $this->resultUids($result));
        $this->assertSame([20, 30], $this->resultPids($result));
    }

    /**
     * `findAll()` is the list view's entry point, so a hidden profile leaking through it would
     * publish a record an editor took offline.
     */
    #[Test]
    public function hiddenProfilesAreNotReturned(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ProfileRepositoryFindAll/profiles.csv');

        $this->assertNotContains(3, $this->resultUids($this->subject()->findAll()));
    }

    #[Test]
    public function deletedProfilesAreNotReturned(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ProfileRepositoryFindAll/profiles.csv');

        $this->assertNotContains(4, $this->resultUids($this->subject()->findAll()));
    }

    /**
     * `tx_academicpersons_domain_model_profile` carries `starttime` and `endtime` enable
     * columns. `findAll()` touches neither, so a profile that is not published yet stays out -
     * which is the reason the enable fields are not switched off wholesale together with the
     * storage page restriction.
     */
    #[Test]
    public function profilesOutsideTheirPublicationPeriodAreNotReturned(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ProfileRepositoryFindAll/profiles.csv');

        $uids = $this->resultUids($this->subject()->findAll());

        $this->assertNotContains(7, $uids);
        $this->assertNotContains(8, $uids);
    }

    /**
     * The language restriction is left in place, so the default language selects
     * `sys_language_uid IN (0, -1)`: a profile marked "all languages" belongs to every
     * language's result.
     */
    #[Test]
    public function profileForAllLanguagesIsReturned(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ProfileRepositoryFindAll/profiles.csv');

        $this->assertContains(5, $this->resultUids($this->subject()->findAll()));
    }

    /**
     * A translation is an overlay of its default language record, not a second profile. Were it
     * returned as one, the list view would show the same person twice.
     *
     * The assertion counts occurrences rather than looking for the translation's own uid `6`,
     * because that uid never reaches the caller: Extbase maps a translated row onto the uid of
     * its default language record, so a leaking translation shows up as profile `1` a second
     * time. Dropping the language restriction produces exactly that - `[1, 1, 2, 5]`.
     */
    #[Test]
    public function translationDoesNotAppearBesideItsDefaultLanguageRecord(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ProfileRepositoryFindAll/profiles.csv');

        $uids = $this->resultUids($this->subject()->findAll());

        $this->assertContains(1, $uids);
        $this->assertCount(1, array_keys($uids, 1, true));
    }

    /**
     * No fixture at all - an installation without a single profile must answer with an empty
     * result rather than with an exception. This is the case the storage page fallback would
     * turn into `InconsistentQuerySettingsException` were the table configured `rootLevel = 1`.
     */
    #[Test]
    public function emptyTableYieldsAnEmptyResult(): void
    {
        $result = $this->subject()->findAll();

        $this->assertCount(0, $result);
        $this->assertSame([], $this->resultUids($result));
    }

    private function subject(): ProfileRepository
    {
        return $this->get(ProfileRepository::class);
    }

    /**
     * @param QueryResultInterface<int, Profile> $result
     * @return int[]
     */
    private function resultUids(QueryResultInterface $result): array
    {
        $uids = [];
        foreach ($result as $profile) {
            $uids[] = (int)$profile->getUid();
        }
        sort($uids);
        return $uids;
    }

    /**
     * @param QueryResultInterface<int, Profile> $result
     * @return int[]
     */
    private function resultPids(QueryResultInterface $result): array
    {
        $pids = [];
        foreach ($result as $profile) {
            $pids[] = (int)$profile->getPid();
        }
        $pids = array_values(array_unique($pids));
        sort($pids);
        return $pids;
    }
}
