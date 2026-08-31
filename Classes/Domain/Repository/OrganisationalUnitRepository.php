<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersons\Domain\Repository;

use FGTCLB\AcademicPersons\Domain\Model\OrganisationalUnit;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * @extends Repository<OrganisationalUnit>
 */
class OrganisationalUnitRepository extends Repository
{
    /**
     * @return QueryResultInterface<int, OrganisationalUnit>
     */
    public function findAll(): QueryResultInterface
    {
        $query = $this->createQuery();
        // @todo Completely ignoring storage pages is a bad design, special for multi site instances.
        //       Needs a better way to deal with this hear and in other places.
        $query->getQuerySettings()->setRespectStoragePage(false);
        // Without this the order is whatever the DBMS yields, which is not the same
        // list twice once an index gives the planner an alternative. The TCA
        // "default_sortby" deliberately does not apply here - see the test.
        $query->setOrderings(['uid' => QueryInterface::ORDER_ASCENDING]);
        return $query->execute();
    }
}
