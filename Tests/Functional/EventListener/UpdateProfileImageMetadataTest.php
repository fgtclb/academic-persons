<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersons\Tests\Functional\EventListener;

use FGTCLB\AcademicPersons\Domain\Model\Profile;
use FGTCLB\AcademicPersons\Event\AfterProfileUpdateEvent;
use FGTCLB\AcademicPersons\EventListener\UpdateProfileImageMetadata;
use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\Test;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;

/**
 * Pins the wiring of {@see UpdateProfileImageMetadata} to `AfterProfileUpdateEvent`
 * (ACE-506): the listener is registered through the `event.listener` tag in
 * `Configuration/Services.yaml`, and a dispatched event ends in the reference row.
 * The service behind it is covered by `ProfileImageMetadataServiceTest`.
 */
final class UpdateProfileImageMetadataTest extends AbstractAcademicPersonsTestCase
{
    #[Test]
    public function listenerIsRegisteredForTheProfileUpdateEvent(): void
    {
        $listeners = iterator_to_array(
            $this->get(ListenerProvider::class)->getListenersForEvent(new AfterProfileUpdateEvent(new Profile())),
            false,
        );

        $this->assertNotEmpty(
            array_filter($listeners, static fn(callable $listener): bool => $listener instanceof UpdateProfileImageMetadata),
            'UpdateProfileImageMetadata is not registered for AfterProfileUpdateEvent.',
        );
    }

    #[Test]
    public function dispatchedEventWritesTheProfileNameIntoTheReferenceMetadata(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Service/RecordSynchronizer/Fixtures/ProfileWithRelations.csv');
        $profile = new Profile();
        $profile->_setProperty('uid', 1);

        $this->get(EventDispatcherInterface::class)->dispatch(new AfterProfileUpdateEvent($profile));

        $this->assertSame(
            ['title' => 'Erika Musterfrau', 'alternative' => 'Erika Musterfrau'],
            $this->getConnectionPool()
                ->getConnectionForTable('sys_file_reference')
                ->select(['title', 'alternative'], 'sys_file_reference', ['uid' => 1])
                ->fetchAssociative(),
        );
    }
}
