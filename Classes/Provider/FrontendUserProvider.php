<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersons\Provider;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;

final class FrontendUserProvider
{
    public function __construct(private readonly ConnectionPool $connectionPool) {}

    /**
     * @param int[] $includePids
     * @param int[] $excludePids
     * @return array<int, array<string, mixed>>
     * @todo getUsersWithoutProfile() should return the doctrine result and not the full retrieved record array
     */
    public function getUsersWithoutProfile(array $includePids = [], array $excludePids = []): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('fe_users');
        $queryBuilder
            ->select('fe_users.*')
            ->from('fe_users')
            ->leftJoin(
                'fe_users',
                'tx_academicpersons_feuser_mm',
                'tx_academicpersons_feuser_mm',
                $queryBuilder->expr()->eq(
                    'fe_users.uid',
                    $queryBuilder->quoteIdentifier('tx_academicpersons_feuser_mm.uid_foreign')
                )
            )
            ->where(
                $queryBuilder->expr()->isNull('tx_academicpersons_feuser_mm.uid_local'),
                $queryBuilder->expr()->eq(
                    'fe_users.tx_extbase_type',
                    $queryBuilder->createNamedParameter('Tx_Academicpersonsedit_Domain_Model_FrontendUser', Connection::PARAM_STR)
                )
            );

        // Ensure to have index in rising order without wholes (integer index keys)
        $includePids = array_values($includePids);
        $excludePids = array_values($excludePids);
        // Remove excluded pids from include pids as this make no sense to have the same pid as IN() and NOT IN()
        if ($includePids !== [] && $excludePids !== []) {
            $includePids = array_values(
                array_filter(
                    $includePids,
                    static fn($value) => !in_array($value, $excludePids, true),
                )
            );
        }
        if ($excludePids !== []) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->notIn(
                    'fe_users.pid',
                    $queryBuilder->quoteArrayBasedValueListToIntegerList($excludePids)
                )
            );
        }
        if ($includePids !== []) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->in(
                    'fe_users.pid',
                    $queryBuilder->quoteArrayBasedValueListToIntegerList($includePids)
                )
            );
        }

        return $queryBuilder
            ->groupBy('fe_users.uid')
            ->executeQuery()
            ->fetchAllAssociative();
    }
}
