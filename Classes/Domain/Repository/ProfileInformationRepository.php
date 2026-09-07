<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons_edit" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersons\Domain\Repository;

use FGTCLB\AcademicPersons\Domain\Model\Profile;
use FGTCLB\AcademicPersons\Domain\Model\ProfileInformation;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * @extends Repository<ProfileInformation>
 */
class ProfileInformationRepository extends Repository
{
    /**
     * @return QueryResultInterface<int, ProfileInformation>
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
     * @return QueryResultInterface<int, ProfileInformation>
     */
    public function findByProfileAndType(Profile $profile, string $type): QueryResultInterface
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);

        return $query
            ->matching(
                $query->logicalAnd(
                    $query->equals('profile', $profile),
                    $query->equals('type', $type)
                )
            )
            ->setOrderings([
                'sorting' => QueryInterface::ORDER_ASCENDING,
                'uid' => QueryInterface::ORDER_ASCENDING,
            ])
            ->execute();
    }

    /**
     * The same list including the records disabled (hidden) via the frontend visibility
     * toggle. Used by the frontend editing UI which must always list hidden records so they
     * can be shown again; every public view keeps `findByProfileAndType()` and the relation.
     *
     * @return QueryResultInterface<int, ProfileInformation>
     */
    public function findByProfileAndTypeIncludingHidden(Profile $profile, string $type): QueryResultInterface
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);
        $query->getQuerySettings()->setIgnoreEnableFields(true);
        $query->getQuerySettings()->setEnableFieldsToBeIgnored(['disabled']);

        return $query
            ->matching(
                $query->logicalAnd(
                    $query->equals('profile', $profile),
                    $query->equals('type', $type)
                )
            )
            ->setOrderings([
                'sorting' => QueryInterface::ORDER_ASCENDING,
                'uid' => QueryInterface::ORDER_ASCENDING,
            ])
            ->execute();
    }
}
