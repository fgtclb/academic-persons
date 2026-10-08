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
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject;
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
 * an Extbase write. The profile editing frontend of `academic_persons_edit` writes the
 * profile, its contracts, their addresses, email addresses and phone numbers, the
 * profile information and the image through Extbase. The automatic cache clearing of
 * Extbase flushes the pages of the storage folder and, on TYPO3 v13, tags of the record,
 * none of which the plugins carry. Without this listener every change made there stayed
 * invisible on the cached public pages until the cache expired.
 *
 * The persistence events are listened to rather than the editor being changed, because
 * every Extbase write passes through them, the reordering through
 * `PersistenceManager::update()` included, and so does the profile synchronisation of
 * this extension. The tags are flushed in the `pages` cache group, which is where the
 * page cache keeps them and where the core flushes the tags of a record as well.
 *
 * The detail view is tagged with the uid of the default-language record. The uid of an
 * Extbase entity is that uid for a translation too, the data mapper keeps the uid of the
 * translation in `_localizedUid`. A file reference is the exception, it is resolved from
 * its row and carries the uid of the translation it belongs to, so its parent is flushed
 * as well.
 */
final class FlushProfileViewCaches
{
    private const PROFILE_TABLE = 'tx_academicpersons_domain_model_profile';
    private const LIST_TAG = 'profile_list_view';
    private const DETAIL_TAG = 'profile_detail_view_%d';

    public function __construct(
        private readonly CacheManager $cacheManager,
        private readonly ConnectionPool $connectionPool,
    ) {}

    public function afterEntityAdded(EntityAddedToPersistenceEvent $event): void
    {
        $this->flushFor($event->getObject());
    }

    public function afterEntityUpdated(EntityUpdatedInPersistenceEvent $event): void
    {
        $this->flushFor($event->getObject());
    }

    public function afterEntityRemoved(EntityRemovedFromPersistenceEvent $event): void
    {
        $this->flushFor($event->getObject());
    }

    private function flushFor(DomainObjectInterface $object): void
    {
        $profileUids = $this->resolveProfileUids($object);
        if ($profileUids === null) {
            return;
        }
        $tags = [self::LIST_TAG];
        foreach ($profileUids as $profileUid) {
            $tags[] = sprintf(self::DETAIL_TAG, $profileUid);
        }
        $this->cacheManager->flushCachesInGroupByTags('pages', $tags);
    }

    /**
     * The profiles whose detail view shows the given record, or `null` for a record that
     * is no part of a profile. A record of a profile whose profile cannot be resolved,
     * a contact record without a contract for example, still flushes the lists.
     *
     * @return list<int>|null
     */
    private function resolveProfileUids(DomainObjectInterface $object): ?array
    {
        if ($object instanceof FileReference) {
            return $this->resolveProfileUidsOfFileReference($object);
        }
        $profile = match (true) {
            $object instanceof Profile => $object,
            $object instanceof Contract, $object instanceof ProfileInformation => $object->getProfile(),
            $object instanceof Address, $object instanceof Email, $object instanceof PhoneNumber => $object->getContract()?->getProfile(),
            default => false,
        };
        if ($profile === false) {
            return null;
        }
        $profileUid = (int)($profile?->getUid() ?? 0);
        return $profileUid > 0 ? [$profileUid] : [];
    }

    /**
     * Reads the record a file reference belongs to from its row. The row is read without
     * restrictions, because a removed reference is already marked deleted when the event
     * is dispatched, and the Extbase model exposes neither the table nor the record.
     *
     * @return list<int>|null
     */
    private function resolveProfileUidsOfFileReference(FileReference $fileReference): ?array
    {
        $uid = (int)($fileReference->_getProperty(AbstractDomainObject::PROPERTY_LOCALIZED_UID) ?? $fileReference->getUid() ?? 0);
        if ($uid <= 0) {
            return null;
        }
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_file_reference');
        $queryBuilder->getRestrictions()->removeAll();
        $reference = $queryBuilder
            ->select('tablenames', 'uid_foreign')
            ->from('sys_file_reference')
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)))
            ->executeQuery()
            ->fetchAssociative();
        if (!is_array($reference) || $reference['tablenames'] !== self::PROFILE_TABLE) {
            return null;
        }
        $profileUid = (int)$reference['uid_foreign'];
        if ($profileUid <= 0) {
            return [];
        }
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::PROFILE_TABLE);
        $queryBuilder->getRestrictions()->removeAll();
        $parentUid = (int)$queryBuilder
            ->select('l10n_parent')
            ->from(self::PROFILE_TABLE)
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($profileUid, Connection::PARAM_INT)))
            ->executeQuery()
            ->fetchOne();
        return $parentUid > 0 ? [$profileUid, $parentUid] : [$profileUid];
    }
}
