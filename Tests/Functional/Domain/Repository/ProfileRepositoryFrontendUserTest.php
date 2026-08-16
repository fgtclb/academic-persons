<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Domain\Repository;

use FGTCLB\AcademicPersons\Domain\Model\Profile;
use FGTCLB\AcademicPersons\Domain\Repository\ProfileRepository;
use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;

/**
 * Coverage for `ProfileRepository::findByFrontendUser()`.
 *
 * This is the access-control shaped method of the repository: `EXT:academic_persons_edit` asks
 * it which profile the logged-in frontend user may edit, and the profile update command asks it
 * which profile a user's data has to be written into. Everything it returns is therefore
 * something the caller is about to hand out or overwrite, which makes the negative cases -
 * another user's profile, a user without a profile, an unknown uid - the ones worth having.
 *
 * The constraint is an `IN` sub-select over the M:N table `tx_academicpersons_feuser_mm`
 * (`uid_local` = profile, `uid_foreign` = frontend user). It looks at the relation only: the
 * frontend user's own enable fields are not part of it, see
 * `profileOfADisabledFrontendUserIsStillReturned()`.
 *
 * `$showHidden` exists for the synchronization, which must keep a hidden profile up to date
 * without publishing it. The frontend keeps the default.
 */
final class ProfileRepositoryFrontendUserTest extends AbstractAcademicPersonsTestCase
{
    private const FRONTEND_USER_OWNER = 10;
    private const FRONTEND_USER_OTHER = 11;
    private const FRONTEND_USER_WITHOUT_PROFILE = 12;
    private const FRONTEND_USER_DISABLED = 13;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ProfileRepositoryFrontendUser/profilesOfFrontendUsers.csv');
    }

    #[Test]
    public function profilesAssignedToTheFrontendUserAreReturned(): void
    {
        $result = $this->subject()->findByFrontendUser(self::FRONTEND_USER_OWNER);

        $this->assertSame([1, 5, 6], $this->resultUids($result));
    }

    /**
     * The case this whole test class exists for. Profile 2 belongs to another frontend user and
     * nothing else distinguishes it from profile 1 - same page, same visibility, same language.
     * If it ever shows up here, a logged-in user can read and edit a stranger's record.
     */
    #[Test]
    public function profileOfAnotherFrontendUserIsNotReturned(): void
    {
        $uids = $this->resultUids($this->subject()->findByFrontendUser(self::FRONTEND_USER_OWNER));

        $this->assertNotContains(2, $uids);
    }

    /**
     * ... and not through the `$showHidden` switch either, which lifts a visibility restriction
     * and must not widen the ownership one.
     */
    #[Test]
    public function profileOfAnotherFrontendUserIsNotReturnedWithHiddenRecordsIncluded(): void
    {
        $uids = $this->resultUids($this->subject()->findByFrontendUser(self::FRONTEND_USER_OWNER, true));

        $this->assertNotContains(2, $uids);
    }

    /**
     * A profile may be assigned to more than one frontend user, so each of them sees it. That is
     * the one shape in which two users legitimately share a record.
     */
    #[Test]
    public function sharedProfileIsReturnedForEveryAssignedFrontendUser(): void
    {
        $this->assertContains(5, $this->resultUids($this->subject()->findByFrontendUser(self::FRONTEND_USER_OWNER)));
        $this->assertSame([2, 5], $this->resultUids($this->subject()->findByFrontendUser(self::FRONTEND_USER_OTHER)));
    }

    /**
     * The frontend controller uses an empty result to decide that there is nothing to edit, so
     * "no profile" has to be an empty result rather than a fallback to anything.
     */
    #[Test]
    public function frontendUserWithoutAProfileGetsAnEmptyResult(): void
    {
        $result = $this->subject()->findByFrontendUser(self::FRONTEND_USER_WITHOUT_PROFILE);

        $this->assertCount(0, $result);
        $this->assertSame([], $this->resultUids($result));
    }

    #[Test]
    public function unknownFrontendUserGetsAnEmptyResult(): void
    {
        $this->assertSame([], $this->resultUids($this->subject()->findByFrontendUser(999)));
    }

    /**
     * `0` is what an unauthenticated request carries. It must not match the relation rows whose
     * `uid_foreign` happens to be missing or `0`.
     */
    #[Test]
    public function frontendUserUidZeroGetsAnEmptyResult(): void
    {
        $this->assertSame([], $this->resultUids($this->subject()->findByFrontendUser(0)));
    }

    #[Test]
    public function hiddenProfileIsNotReturnedByDefault(): void
    {
        $this->assertNotContains(3, $this->resultUids($this->subject()->findByFrontendUser(self::FRONTEND_USER_OWNER)));
    }

    /**
     * The synchronization has to keep writing into a hidden profile, so it asks for it
     * explicitly - and must still only get the profiles of that one user.
     */
    #[Test]
    public function hiddenProfileIsReturnedWhenRequested(): void
    {
        $result = $this->subject()->findByFrontendUser(self::FRONTEND_USER_OWNER, true);

        $this->assertSame([1, 3, 5, 6], $this->resultUids($result));
    }

    /**
     * `$showHidden` lifts the `disabled` restriction, never the deleted one - a deleted profile
     * is gone for the synchronization as much as for the frontend.
     */
    #[Test]
    public function deletedProfileIsNeverReturned(): void
    {
        $this->assertNotContains(4, $this->resultUids($this->subject()->findByFrontendUser(self::FRONTEND_USER_OWNER)));
        $this->assertNotContains(4, $this->resultUids($this->subject()->findByFrontendUser(self::FRONTEND_USER_OWNER, true)));
    }

    /**
     * The storage page restriction is off, and it has to be: the profile of a frontend user is
     * wherever an editor put it, not on the page the plugin happens to be configured with.
     */
    #[Test]
    public function profileOnAnotherStoragePageIsReturned(): void
    {
        $result = $this->subject()->findByFrontendUser(self::FRONTEND_USER_OWNER);

        $this->assertContains(6, $this->resultUids($result));
        $this->assertSame([20, 30], $this->resultPids($result));
    }

    /**
     * The relation is the only thing the query looks at, so whether the frontend user is
     * disabled is none of this method's business - the caller has already authenticated. Pinned
     * because it reads like an omission and is not one.
     */
    #[Test]
    public function profileOfADisabledFrontendUserIsStillReturned(): void
    {
        $this->assertSame([8], $this->resultUids($this->subject()->findByFrontendUser(self::FRONTEND_USER_DISABLED)));
    }

    /**
     * Profile 7 is the translation of profile 1 and carries a relation row of its own. In the
     * default language it is an overlay of profile 1, not a second profile the user owns -
     * otherwise the edit plugin would offer the same person twice.
     *
     * The assertion counts occurrences rather than looking for uid `7`: Extbase maps a
     * translated row onto the uid of its default language record, so a leaking translation
     * shows up as profile `1` a second time, never under its own uid.
     */
    #[Test]
    public function translationDoesNotAppearBesideItsDefaultLanguageRecord(): void
    {
        $uids = $this->resultUids($this->subject()->findByFrontendUser(self::FRONTEND_USER_OWNER));

        $this->assertContains(1, $uids);
        $this->assertCount(1, array_keys($uids, 1, true));
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
