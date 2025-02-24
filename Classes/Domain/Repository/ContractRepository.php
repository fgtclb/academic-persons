<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Fgtclb\AcademicPersons\Domain\Repository;

use Fgtclb\AcademicPersons\Domain\Model\Contract;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * @extends Repository<Contract>
 */
class ContractRepository extends Repository
{
    /**
     * @param int[] $uids
     * @return QueryResultInterface<Contract>
     */
    public function findByUids(array $uids): QueryResultInterface
    {
        $query = $this->createQuery();
        // Selected uid's are default language and we need to configure extbase in away to
        // properly handle the overlay. This is adopted from the generic extbase backend
        // implementation.
        // @todo Needs adoption for TYPO3 v12+ which respects the language aspect.
        $query->getQuerySettings()->setRespectStoragePage(false);
        $query->getQuerySettings()->setRespectSysLanguage(false);
        $query->getQuerySettings()->setLanguageOverlayMode(true);
        $query->matching($query->in('uid', $uids));

        return $query->execute();
    }
}
