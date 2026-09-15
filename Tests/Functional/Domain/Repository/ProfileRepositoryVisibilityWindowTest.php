<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Domain\Repository;

use FGTCLB\AcademicPersons\Domain\Model\Dto\ProfileDemand;
use FGTCLB\AcademicPersons\Domain\Model\Profile;
use FGTCLB\AcademicPersons\Domain\Repository\ProfileRepository;
use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;

/**
 * The visibility window - start time, end time and frontend user group - against the
 * "including hidden" lookups of `ProfileRepository`.
 *
 * The public paths lift the `hidden` flag only: the "show hidden records" listing, the
 * selected profiles and the detail view must keep a profile outside its window out of the
 * frontend. The synchronization lookup, `findByFrontendUser()` with `$showHidden`, lifts the
 * whole window, because a command-line run has no reason to skip a person whose profile is
 * scheduled, expired or restricted to a group.
 *
 * Every case runs in a frontend request on purpose. Outside of one Extbase takes its backend
 * path, which does not read the list of enable fields to ignore at all and drops every enable
 * field as soon as any is ignored - a test without a frontend request would pass for either
 * implementation.
 */
final class ProfileRepositoryVisibilityWindowTest extends AbstractAcademicPersonsTestCase
{
    private const FRONTEND_USER = 10;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/VisibilityWindow/profiles.csv');
        // Extbase reads the TypoScript setup of a frontend request to build the query settings;
        // an empty one is enough, the repository sets everything it relies on itself.
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $frontendTypoScript->setSetupArray([]);
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest('https://www.acme.com/'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('frontend.typoscript', $frontendTypoScript);
    }

    #[Test]
    public function showHiddenRecordsListingExcludesProfilesOutsideTheirVisibilityWindow(): void
    {
        $demand = (new ProfileDemand())->setShowHiddenRecords(true);

        $this->assertSame([1, 2], $this->resultUids($this->subject()->findByDemand($demand)));
    }

    #[Test]
    public function selectedProfilesIncludingHiddenExcludeProfilesOutsideTheirVisibilityWindow(): void
    {
        $this->assertSame([1, 2], $this->resultUids($this->subject()->findByUids([1, 2, 3, 4, 5, 6], true)));
    }

    public static function profilesOutsideTheirVisibilityWindowDataSets(): \Generator
    {
        yield 'end time has passed' => ['profileUid' => 3];
        yield 'start time lies in the future' => ['profileUid' => 4];
        yield 'restricted to a frontend user group' => ['profileUid' => 5];
    }

    #[DataProvider(methodName: 'profilesOutsideTheirVisibilityWindowDataSets')]
    #[Test]
    public function detailViewIncludingHiddenDoesNotReturnAProfileOutsideItsVisibilityWindow(int $profileUid): void
    {
        $this->assertNull($this->subject()->findByUidIncludingHidden($profileUid));
    }

    #[Test]
    public function frontendUserLookupWithoutSynchronizationSwitchRespectsTheVisibilityWindow(): void
    {
        $this->assertSame([1], $this->resultUids($this->subject()->findByFrontendUser(self::FRONTEND_USER)));
    }

    /**
     * The deleted profile 6 stays out: the synchronization lifts visibility, never deletion.
     */
    #[Test]
    public function frontendUserLookupForTheSynchronizationIgnoresTheVisibilityWindow(): void
    {
        $this->assertSame(
            [1, 2, 3, 4, 5],
            $this->resultUids($this->subject()->findByFrontendUser(self::FRONTEND_USER, true)),
        );
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
}
