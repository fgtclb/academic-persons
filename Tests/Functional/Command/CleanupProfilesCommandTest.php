<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Command;

use FGTCLB\AcademicPersons\Command\CleanupProfilesCommand;
use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\DataHandling\History\RecordHistoryStore;
use TYPO3\CMS\Core\Utility\ArrayUtility;

/**
 * `academic:cleanupprofiles` against real rows. Every frontend user has the record type
 * the synchronisation reads, unless stated otherwise. The profiles of the fixture, by uid:
 *
 *  1  one disabled frontend user, translated (101)      hidden
 *  2  one frontend user past its end time               hidden
 *  3  one deleted frontend user, translated (103)       deleted
 *  4  a relation to a frontend user row that is gone,   deleted
 *     and the import identifier of the synchronisation
 *  5  a disabled and an active frontend user            unchanged
 *  6  a disabled frontend user, excluded from sync      unchanged
 *  7  no frontend user                                  unchanged
 *  8  a disabled frontend user on page 200              hidden
 *  9  a frontend user whose start time lies ahead       unchanged
 * 10  a frontend user whose end time lies ahead         unchanged
 * 11  a deleted and a disabled frontend user            hidden, not deleted
 * 12  hidden already, one disabled frontend user        unchanged, not listed
 * 13  a deleted frontend user on page 200               deleted
 * 14  deleted already, one disabled frontend user       unchanged, not listed
 * 16  hidden already, one deleted frontend user         deleted
 * 17  disabled frontend users on page 100 and 200       hidden
 * 18  a disabled login of another record type           unchanged, not listed
 * 19  all languages, one disabled frontend user         hidden
 *
 * The translation 101 carries a relation to frontend user 1 of its own, and is hidden
 * through profile 1 rather than on its own.
 */
final class CleanupProfilesCommandTest extends AbstractAcademicPersonsTestCase
{
    private const PROFILE_TABLE = 'tx_academicpersons_domain_model_profile';

    /**
     * Every profile row as it is in the fixture: [hidden, deleted] by uid.
     */
    private const UNCHANGED = [
        1 => [0, 0], 2 => [0, 0], 3 => [0, 0], 4 => [0, 0], 5 => [0, 0], 6 => [0, 0], 7 => [0, 0],
        8 => [0, 0], 9 => [0, 0], 10 => [0, 0], 11 => [0, 0], 12 => [1, 0], 13 => [0, 0],
        14 => [0, 1], 16 => [1, 0], 17 => [0, 0], 18 => [0, 0], 19 => [0, 0],
        101 => [0, 0], 103 => [0, 0],
    ];

    protected function setUp(): void
    {
        // The pages cache keeps its entries, so that a test can see which tags a run
        // flushes. The testing framework sets it to the null backend.
        ArrayUtility::mergeRecursiveWithOverrule(
            $this->configurationToUseInTestInstance,
            ['SYS' => ['caching' => ['cacheConfigurations' => ['pages' => ['backend' => Typo3DatabaseBackend::class]]]]],
        );
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/CleanupProfiles/profiles.csv');
    }

    /**
     * @param array<string, mixed> $input
     */
    private function runCommand(array $input = []): CommandTester
    {
        $tester = new CommandTester($this->get(CleanupProfilesCommand::class));
        $tester->execute($input);
        return $tester;
    }

    /**
     * @return array<int, array{0: int, 1: int}> [hidden, deleted] by profile uid
     */
    private function getProfileStates(): array
    {
        $rows = $this->getConnectionPool()
            ->getConnectionForTable(self::PROFILE_TABLE)
            ->executeQuery('SELECT uid, hidden, deleted FROM ' . self::PROFILE_TABLE . ' ORDER BY uid')
            ->fetchAllAssociative();
        $states = [];
        foreach ($rows as $row) {
            $states[(int)$row['uid']] = [(int)$row['hidden'], (int)$row['deleted']];
        }
        return $states;
    }

    /**
     * @param array<int, array{0: int, 1: int}> $changes
     * @return array<int, array{0: int, 1: int}>
     */
    private function expectedStates(array $changes): array
    {
        return array_replace(self::UNCHANGED, $changes);
    }

    #[Test]
    public function aProfileOfDisabledOrExpiredUsersIsHiddenInEveryLanguage(): void
    {
        $tester = $this->runCommand();

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $states = $this->getProfileStates();
        $this->assertSame([1, 0], $states[1], 'Disabled frontend user');
        $this->assertSame([1, 0], $states[101], 'The translation follows the default language');
        $this->assertSame([1, 0], $states[2], 'Frontend user past its end time');
        $this->assertSame([1, 0], $states[8]);
        $this->assertSame([1, 0], $states[19], 'A profile of all languages');
    }

    #[Test]
    public function aProfileOfDeletedUsersIsDeletedWithItsTranslationAndContracts(): void
    {
        $this->runCommand();

        $states = $this->getProfileStates();
        $this->assertSame([0, 1], $states[3], 'Deleted frontend user');
        $this->assertSame([0, 1], $states[103], 'The translation is deleted with it');
        $this->assertSame([0, 1], $states[4], 'A frontend user row that is gone counts as deleted');
        $this->assertSame([0, 1], $states[13]);
        $this->assertSame([1, 1], $states[16], 'A hidden profile is deleted all the same');
        $contracts = $this->getConnectionPool()
            ->getConnectionForTable('tx_academicpersons_domain_model_contract')
            ->executeQuery('SELECT uid, deleted FROM tx_academicpersons_domain_model_contract ORDER BY uid')
            ->fetchAllKeyValue();
        $this->assertSame([1 => 0, 3 => 1, 4 => 1], array_map('intval', $contracts));
    }

    #[Test]
    public function aDeletedProfileIsRecordedInTheHistory(): void
    {
        $this->runCommand();

        $deletedUids = $this->getConnectionPool()
            ->getConnectionForTable('sys_history')
            ->executeQuery(
                'SELECT recuid FROM sys_history WHERE tablename = ? AND actiontype = ? ORDER BY recuid',
                [self::PROFILE_TABLE, RecordHistoryStore::ACTION_DELETE],
            )
            ->fetchFirstColumn();
        $this->assertSame([3, 4, 13, 16, 103], array_map('intval', $deletedUids));
    }

    #[Test]
    public function maintainedProfilesAndProfilesWithAnActiveUserStayUnchanged(): void
    {
        $this->runCommand();

        $states = $this->getProfileStates();
        $this->assertSame([0, 0], $states[5], 'A second, active frontend user');
        $this->assertSame([0, 0], $states[6], 'Excluded from the synchronisation');
        $this->assertSame([0, 0], $states[7], 'No frontend user');
        $this->assertSame([0, 0], $states[9], 'A start time ahead is no inactive frontend user');
        $this->assertSame([0, 0], $states[10], 'An end time ahead is no inactive frontend user');
        $this->assertSame([0, 0], $states[18], 'A login the synchronisation does not read');
    }

    #[Test]
    public function aProfileWithADeletedAndADisabledUserIsHiddenNotDeleted(): void
    {
        $this->runCommand();

        $this->assertSame([1, 0], $this->getProfileStates()[11]);
    }

    #[Test]
    public function everyChangedProfileIsListedAndAHiddenProfileIsNotHiddenAgain(): void
    {
        $tester = $this->runCommand();

        $this->assertSame(
            implode(PHP_EOL, [
                'Profile 1 "Disabled, Dora": hidden',
                'Profile 2 "Expired, Eric": hidden',
                'Profile 3 "Deleted, Dean": deleted',
                'Profile 4 "Missing, Mia": deleted',
                'Profile 8 "Guest, Gil": hidden',
                'Profile 11 "Mixed, Max": hidden',
                'Profile 13 "Gone, Gus": deleted',
                'Profile 16 "Hiddendeleted, Hedy": deleted',
                'Profile 17 "Twopages, Tess": hidden',
                'Profile 19 "Alllanguages, Alma": hidden',
                '10 profile(s) changed.',
            ]) . PHP_EOL,
            $tester->getDisplay(),
        );
    }

    #[Test]
    public function disabledKeepLeavesProfilesOfDisabledUsers(): void
    {
        $this->runCommand(['--disabled' => 'keep']);

        $this->assertSame(
            $this->expectedStates([3 => [0, 1], 103 => [0, 1], 4 => [0, 1], 13 => [0, 1], 16 => [1, 1]]),
            $this->getProfileStates(),
        );
    }

    #[Test]
    public function deletedHideHidesProfilesOfDeletedUsers(): void
    {
        $tester = $this->runCommand(['--deleted' => 'hide']);

        $this->assertSame(
            $this->expectedStates([
                1 => [1, 0], 101 => [1, 0], 2 => [1, 0], 3 => [1, 0], 103 => [1, 0], 4 => [1, 0],
                8 => [1, 0], 11 => [1, 0], 13 => [1, 0], 17 => [1, 0], 19 => [1, 0],
            ]),
            $this->getProfileStates(),
        );
        $this->assertStringNotContainsString('Profile 16', $tester->getDisplay(), 'Hidden already');
    }

    #[Test]
    public function deletedKeepLeavesProfilesOfDeletedUsers(): void
    {
        $this->runCommand(['--deleted' => 'keep']);

        $this->assertSame(
            $this->expectedStates([
                1 => [1, 0], 101 => [1, 0], 2 => [1, 0], 8 => [1, 0], 11 => [1, 0], 17 => [1, 0], 19 => [1, 0],
            ]),
            $this->getProfileStates(),
        );
    }

    /**
     * One frontend user on an included page is enough. A relation to a frontend user
     * row that is gone lies on no page, so profile 4 is left out once pages are included.
     */
    #[Test]
    public function includePidsLooksAtProfilesWithAFrontendUserOnThosePages(): void
    {
        $this->runCommand(['--include-pids' => '200']);

        $this->assertSame(
            $this->expectedStates([8 => [1, 0], 13 => [0, 1], 17 => [1, 0]]),
            $this->getProfileStates(),
        );
    }

    /**
     * One frontend user on an excluded page is enough to leave a profile alone.
     */
    #[Test]
    public function excludePidsLeavesProfilesWithAFrontendUserOnThosePages(): void
    {
        $this->runCommand(['--exclude-pids' => '200']);

        $this->assertSame(
            $this->expectedStates([
                1 => [1, 0], 101 => [1, 0], 2 => [1, 0], 3 => [0, 1], 103 => [0, 1], 4 => [0, 1], 11 => [1, 0],
                16 => [1, 1], 19 => [1, 0],
            ]),
            $this->getProfileStates(),
        );
    }

    #[Test]
    public function aDryRunListsEveryChangeAndWritesNothing(): void
    {
        $tester = $this->runCommand(['--dry-run' => true]);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertSame(
            implode(PHP_EOL, [
                'Profile 1 "Disabled, Dora": would be hidden',
                'Profile 2 "Expired, Eric": would be hidden',
                'Profile 3 "Deleted, Dean": would be deleted',
                'Profile 4 "Missing, Mia": would be deleted',
                'Profile 8 "Guest, Gil": would be hidden',
                'Profile 11 "Mixed, Max": would be hidden',
                'Profile 13 "Gone, Gus": would be deleted',
                'Profile 16 "Hiddendeleted, Hedy": would be deleted',
                'Profile 17 "Twopages, Tess": would be hidden',
                'Profile 19 "Alllanguages, Alma": would be hidden',
                'Dry run: 10 profile(s) would change. Nothing was written.',
            ]) . PHP_EOL,
            $tester->getDisplay(),
        );
        $this->assertSame(self::UNCHANGED, $this->getProfileStates());
    }

    /**
     * Only deletions happen in this run, so the list is flushed by the deletion rather
     * than by a profile hidden on the way.
     */
    #[Test]
    public function aDeletedProfileLeavesTheCachedListAndDetailPages(): void
    {
        $cache = $this->get(CacheManager::class)->getCache('pages');
        $cache->set('list', 'list', ['profile_list_view']);
        $cache->set('detail-3', 'detail', ['profile_detail_view_3']);
        $cache->set('detail-5', 'detail', ['profile_detail_view_5']);

        $this->runCommand(['--disabled' => 'keep']);

        $this->assertFalse($cache->has('list'), 'The list shows the deleted profile no longer');
        $this->assertFalse($cache->has('detail-3'), 'The detail view of the deleted profile');
        $this->assertTrue($cache->has('detail-5'), 'A profile the run left alone keeps its cache');
    }

    #[Test]
    public function aProfileStaysHiddenWhenItsUserIsEnabledAgain(): void
    {
        $this->runCommand();
        $this->getConnectionPool()->getConnectionForTable('fe_users')->update('fe_users', ['disable' => 0], ['uid' => 1]);

        $this->runCommand();

        $this->assertSame([1, 0], $this->getProfileStates()[1]);
    }

    #[Test]
    public function anUnknownActionIsRefused(): void
    {
        $tester = $this->runCommand(['--deleted' => 'purge']);

        $this->assertSame(Command::INVALID, $tester->getStatusCode());
        $this->assertStringContainsString('--deleted must be one of: delete, hide, keep.', $tester->getDisplay());
        $this->assertSame(self::UNCHANGED, $this->getProfileStates());
    }

    /**
     * A mistyped page list would otherwise shrink to the uids it can read, and exclude
     * less than was meant.
     */
    #[Test]
    public function aPageListWithAPartThatIsNoUidIsRefused(): void
    {
        $tester = $this->runCommand(['--exclude-pids' => '200;300']);

        $this->assertSame(Command::INVALID, $tester->getStatusCode());
        $this->assertStringContainsString('--include-pids and --exclude-pids take a comma-separated list', $tester->getDisplay());
        $this->assertSame(self::UNCHANGED, $this->getProfileStates());
    }
}
