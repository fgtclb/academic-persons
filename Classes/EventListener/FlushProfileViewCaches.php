<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersons\EventListener;

use FGTCLB\AcademicPersons\Domain\Model\Address;
use FGTCLB\AcademicPersons\Domain\Model\Contract;
use FGTCLB\AcademicPersons\Domain\Model\Email;
use FGTCLB\AcademicPersons\Domain\Model\PhoneNumber;
use FGTCLB\AcademicPersons\Domain\Model\Profile;
use FGTCLB\AcademicPersons\Domain\Model\ProfileInformation;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface;
use TYPO3\CMS\Extbase\Event\Persistence\EntityAddedToPersistenceEvent;
use TYPO3\CMS\Extbase\Event\Persistence\EntityRemovedFromPersistenceEvent;
use TYPO3\CMS\Extbase\Event\Persistence\EntityUpdatedInPersistenceEvent;

/**
 * Flushes the cached list and detail views of a profile when a record of it is written
 * through Extbase rather than through the DataHandler.
 *
 * The list plugin tags its page with `profile_list_view`, the detail plugin with
 * `profile_detail_view_<uid>`. {@see \FGTCLB\AcademicPersons\Hook\DataHandlerHooks}
 * flushes both after a backend save, and it does so alone: a DataHandler hook never sees
 * an Extbase write. The profile editor of `academic_persons_edit` writes the profile, its
 * contracts, their addresses, email addresses and phone numbers and the profile
 * information through Extbase, and so does the profile synchronisation from frontend
 * users. The automatic cache clearing of Extbase flushes the pages of the storage folder
 * and the tags of the record, none of which the plugins carry, and it never runs on the
 * command line. Only with the automatic cache tagging of the core
 * (`frontend.cache.autoTagging`) does a page carry the tag of a row it read, so a change
 * of that row reached it. Without this listener a change stayed invisible on the cached
 * public pages until the cache expired with that feature off, for a new record, and for
 * every write on the command line.
 *
 * The persistence events are listened to rather than the writers being changed, because
 * every Extbase write passes through them, the reordering of the editor through
 * `PersistenceManager::update()` included. The tags are flushed in the `pages` cache
 * group, which is where the page cache keeps them.
 *
 * The detail view is tagged with the uid of the default-language record. The uid of an
 * Extbase entity is that uid for a translation too, the data mapper keeps the uid of the
 * translation in `_localizedUid`, so the uid of the entity is the tag to flush.
 */
final readonly class FlushProfileViewCaches
{
    private const LIST_TAG = 'profile_list_view';
    private const DETAIL_TAG = 'profile_detail_view_%d';

    public function __construct(
        private CacheManager $cacheManager,
    ) {}

    #[AsEventListener(identifier: 'academic-persons/flush-profile-view-caches-on-add')]
    public function afterEntityAdded(EntityAddedToPersistenceEvent $event): void
    {
        $this->flushFor($event->getObject());
    }

    #[AsEventListener(identifier: 'academic-persons/flush-profile-view-caches-on-update')]
    public function afterEntityUpdated(EntityUpdatedInPersistenceEvent $event): void
    {
        $this->flushFor($event->getObject());
    }

    #[AsEventListener(identifier: 'academic-persons/flush-profile-view-caches-on-remove')]
    public function afterEntityRemoved(EntityRemovedFromPersistenceEvent $event): void
    {
        $this->flushFor($event->getObject());
    }

    /**
     * A record that is no part of a profile leaves the caches alone. A record of a
     * profile whose profile cannot be resolved, a contact record without a contract for
     * example, still flushes the lists.
     */
    private function flushFor(DomainObjectInterface $object): void
    {
        $profile = match (true) {
            $object instanceof Profile => $object,
            $object instanceof Contract, $object instanceof ProfileInformation => $object->getProfile(),
            $object instanceof Address, $object instanceof Email, $object instanceof PhoneNumber => $object->getContract()?->getProfile(),
            default => false,
        };
        if ($profile === false) {
            return;
        }
        $tags = [self::LIST_TAG];
        $profileUid = (int)($profile?->getUid() ?? 0);
        if ($profileUid > 0) {
            $tags[] = sprintf(self::DETAIL_TAG, $profileUid);
        }
        $this->cacheManager->flushCachesInGroupByTags('pages', $tags);
    }
}
