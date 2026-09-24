<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersons\Tests\Unit\Event;

use FGTCLB\AcademicPersons\Domain\Model\Profile;
use FGTCLB\AcademicPersons\Event\AfterProfileUpdateEvent;
use FGTCLB\AcademicPersons\Event\ProfileUpdateOrigin;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class AfterProfileUpdateEventTest extends UnitTestCase
{
    #[Test]
    public function anEventCreatedWithTheProfileOnlyHasNoSiteAndAnUnknownOrigin(): void
    {
        $profile = $this->createStub(Profile::class);

        $event = new AfterProfileUpdateEvent($profile);

        $this->assertSame($profile, $event->getProfile());
        $this->assertNull($event->getSite());
        $this->assertSame(ProfileUpdateOrigin::Unknown, $event->getOrigin());
    }

    #[Test]
    public function theSiteAndTheOriginArePassedThrough(): void
    {
        $profile = $this->createStub(Profile::class);
        $site = $this->createStub(Site::class);

        $event = new AfterProfileUpdateEvent($profile, $site, ProfileUpdateOrigin::Backend);

        $this->assertSame($profile, $event->getProfile());
        $this->assertSame($site, $event->getSite());
        $this->assertSame(ProfileUpdateOrigin::Backend, $event->getOrigin());
    }

    /**
     * The case set is fixed: a listener may `match` over it exhaustively, so a case
     * added later would break it. The values are what a listener logs or compares.
     */
    #[Test]
    public function theOriginsAreTheSixDecidedCases(): void
    {
        $this->assertSame(
            [
                'Creation' => 'creation',
                'Synchronization' => 'synchronization',
                'FrontendEditing' => 'frontend-editing',
                'Backend' => 'backend',
                'Import' => 'import',
                'Unknown' => 'unknown',
            ],
            array_column(
                array_map(
                    static fn(ProfileUpdateOrigin $origin): array => ['name' => $origin->name, 'value' => $origin->value],
                    ProfileUpdateOrigin::cases(),
                ),
                'value',
                'name',
            ),
        );
    }
}
