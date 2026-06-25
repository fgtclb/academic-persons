<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersons\Domain\Repository;

use FGTCLB\AcademicPersons\Domain\Model\Address;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * @extends Repository<Address>
 */
class AddressRepository extends Repository
{
    /**
     * @return QueryResultInterface<int, Address>
     */
    public function findAll(): QueryResultInterface
    {
        $query = $this->createQuery();
        // @todo Completely ignoring storage pages is a bad design, special for multi site instances.
        //       Needs a better way to deal with this hear and in other places.
        $query->getQuerySettings()->setRespectStoragePage(false);
        return $query->execute();
    }

    /**
     * Returns all addresses of the given contract, including the ones disabled (hidden) via the
     * frontend visibility toggle. Used by the frontend editing UI which must always list hidden
     * records so they can be shown again.
     *
     * @return QueryResultInterface<int, Address>
     */
    public function findByContractIncludingHidden(int $contractUid): QueryResultInterface
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);
        $query->getQuerySettings()->setIgnoreEnableFields(true);
        $query->getQuerySettings()->setEnableFieldsToBeIgnored(['disabled']);
        $query->matching($query->equals('contract', $contractUid));
        $query->setOrderings(['sorting' => QueryInterface::ORDER_ASCENDING]);
        return $query->execute();
    }

    /**
     * Returns a single address by uid, including the one disabled (hidden) via the frontend
     * visibility toggle. Required to resolve hidden records in the frontend editing UI, as
     * Extbase argument mapping respects enable fields and would not find them.
     */
    public function findByUidIncludingHidden(int $uid): ?Address
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);
        $query->getQuerySettings()->setIgnoreEnableFields(true);
        $query->getQuerySettings()->setEnableFieldsToBeIgnored(['disabled']);
        $query->matching($query->equals('uid', $uid));
        /** @var Address|null $address */
        $address = $query->execute()->getFirst();
        return $address;
    }
}
