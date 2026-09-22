<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Domain\Repository;

use FGTCLB\AcademicPersons\Domain\Repository\ProfileRepository;
use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\Test;
use TESTS\TestProfileQueryConstraints\EventListener\RestrictProfileQueryListener;

/**
 * `ModifyProfileQueryEvent` reaches a listener even where no plugin is behind the query.
 *
 * The plugin tests next to this one all go through a content element, so they only ever see a
 * dispatch with a context. `findByUids()` delegates to `findByUidsWithContext()` with none, and
 * a delegation that stopped dispatching would be invisible to every one of them.
 */
final class ProfileRepositoryQueryEventTest extends AbstractAcademicPersonsTestCase
{
    protected function setUp(): void
    {
        $this->addTestExtension('tests/test-profile-query-constraints');
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ProfileRepositoryQueryEvent/profiles.csv');
    }

    protected function tearDown(): void
    {
        RestrictProfileQueryListener::$constrainWithoutContext = false;
        parent::tearDown();
    }

    #[Test]
    public function findByUidsDispatchesTheEventWithoutAContext(): void
    {
        RestrictProfileQueryListener::$constrainWithoutContext = true;
        $repository = $this->get(ProfileRepository::class);

        $uids = [];
        foreach ($repository->findByUids([1, 2, 3]) as $profile) {
            $uids[] = $profile->getUid();
        }

        $this->assertSame([2], $uids);
    }

    #[Test]
    public function findByUidsReturnsEveryUidWhileNoListenerConstrainsIt(): void
    {
        $repository = $this->get(ProfileRepository::class);

        $uids = [];
        foreach ($repository->findByUids([1, 2, 3]) as $profile) {
            $uids[] = $profile->getUid();
        }

        $this->assertSame([1, 2, 3], $uids);
    }
}
