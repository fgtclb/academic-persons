<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersons\Domain\Repository;

use FGTCLB\AcademicPersons\Domain\Model\Contract;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * @extends Repository<Contract>
 */
class ContractRepository extends Repository
{
    /**
     * @return QueryResultInterface<int, Contract>
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
     * Resolve the contract records used to build the select items for the
     * `itemsProcFunc` of contract selection fields (TCA and FlexForm).
     *
     * The FormEngine `itemsProcFunc` parameters are passed through so the
     * query can be narrowed by context (effective pid, site, ...) later on
     * without touching the calling handler again.
     *
     * @param array{
     *      items: array<int, array{
     *       label?: string|null,
     *       value?: mixed,
     *       icon?: string|null,
     *       group?: string|null,
     *      }>,
     *      config: array<string, mixed>,
     *      TSconfig: array<string, mixed>,
     *      table: string,
     *      row: array<string, mixed>,
     *      field: string,
     *      effectivePid: int,
     *      site: Site|null,
     *      flexParentDatabaseRow?: array<string, mixed>|null,
     *      inlineParentUid?: int,
     *      inlineParentTableName?: string,
     *      inlineParentFieldName?: string,
     *      inlineParentConfig?: array<string, mixed>,
     *      inlineTopMostParentUid?: int,
     *      inlineTopMostParentTableName?: string,
     *      inlineTopMostParentFieldName?: string,
     *  } $parameters
     * @return QueryResultInterface<int, Contract>
     */
    public function getContractItemsForTcaItemsProcFunc(array $parameters): QueryResultInterface
    {
        return $this->findAll();
    }

    /**
     * @param int[] $uids
     * @return QueryResultInterface<int, Contract>
     */
    public function findByUids(array $uids, bool $showHidden = false): QueryResultInterface
    {
        $query = $this->createQuery();
        // Selected uid's are default language and we need to configure extbase in away to
        // properly handle the overlay. This is adopted from the generic extbase backend
        // implementation.
        $currentLanguageAspect = $query->getQuerySettings()->getLanguageAspect();
        $changedLanguageAspect = new LanguageAspect(
            $currentLanguageAspect->getId(),
            $currentLanguageAspect->getContentId(),
            $currentLanguageAspect->getOverlayType() === LanguageAspect::OVERLAYS_OFF ? LanguageAspect::OVERLAYS_ON_WITH_FLOATING : $currentLanguageAspect->getOverlayType()
        );
        $query->getQuerySettings()->setLanguageAspect($changedLanguageAspect);
        $query->getQuerySettings()->setRespectStoragePage(false);
        $query->getQuerySettings()->setRespectSysLanguage(false);
        if ($showHidden === true) {
            // Include hidden (disabled) records; other enable fields
            // (deleted, start-/endtime, fe_group) stay in effect.
            $query->getQuerySettings()->setIgnoreEnableFields(true);
            $query->getQuerySettings()->setEnableFieldsToBeIgnored(['disabled']);
        }
        $query->matching($query->in('uid', $uids));

        return $query->execute();
    }
}
