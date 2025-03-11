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
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Information\Typo3Version;
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
        if (method_exists($query->getQuerySettings(), 'getLanguageAspect')
            && method_exists($query->getQuerySettings(), 'setLanguageAspect')
        ) {
            $currentLanguageAspect = $query->getQuerySettings()->getLanguageAspect();
            $changedLanguageAspect = new LanguageAspect(
                $currentLanguageAspect->getId(),
                $currentLanguageAspect->getContentId(),
                $currentLanguageAspect->getOverlayType() === LanguageAspect::OVERLAYS_OFF ? LanguageAspect::OVERLAYS_ON_WITH_FLOATING : $currentLanguageAspect->getOverlayType()
            );
            $query->getQuerySettings()->setLanguageAspect($changedLanguageAspect);
        } else {
            // @todo Remove this when TYPO3 v11 support is dropped with 2.x.x.
            if (method_exists($query->getQuerySettings(), 'setLanguageOverlayMode')) {
                $query->getQuerySettings()->setLanguageOverlayMode(true);
            }
        }
        $query->getQuerySettings()->setRespectStoragePage(false);
        $query->getQuerySettings()->setRespectSysLanguage(false);
        $query->matching($query->in('uid', $uids));

        return $query->execute();
    }
}
