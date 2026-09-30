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
use FGTCLB\AcademicPersons\Event\BeforeProfileMappedFromFrontendUserEvent;
use FGTCLB\AcademicPersons\Profile\ProfileActionType;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class BeforeProfileMappedFromFrontendUserEventTest extends UnitTestCase
{
    #[Test]
    public function aNewEventIsNotSkippedAndCarriesTheDataItWasCreatedWith(): void
    {
        $event = new BeforeProfileMappedFromFrontendUserEvent(['uid' => 7, 'pid' => 5], ProfileActionType::Create);

        $this->assertFalse($event->isSkipped());
        $this->assertSame(['uid' => 7, 'pid' => 5], $event->getFrontendUserData());
        $this->assertSame(ProfileActionType::Create, $event->getAction());
        $this->assertNull($event->getProfile());
    }

    #[Test]
    public function theDataIsReplacedAndASkipIsKept(): void
    {
        $profile = new Profile();
        $event = new BeforeProfileMappedFromFrontendUserEvent(['uid' => 7, 'pid' => 5], ProfileActionType::Update, $profile);

        $event->setFrontendUserData(['uid' => 7, 'pid' => 5, 'ldap.room' => 'A 2.14']);
        $event->skip();

        $this->assertTrue($event->isSkipped());
        $this->assertSame(['uid' => 7, 'pid' => 5, 'ldap.room' => 'A 2.14'], $event->getFrontendUserData());
        $this->assertSame($profile, $event->getProfile());
    }
}
