<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersons\Provider;

use FGTCLB\AcademicPersons\Domain\Model\Dto\ProfileCleanupCandidate;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\ConnectionPool;

/**
 * Finds the profiles `academic:cleanupprofiles` hides or deletes: live profiles of the
 * default language or of all languages, managed by the synchronisation and not excluded
 * from it, whose linked frontend users are all deleted, disabled or past their end time.
 *
 * A profile is managed by the synchronisation when it carries an import identifier,
 * which the synchronisation writes, or when one of its frontend users has the record
 * type the synchronisation reads. A profile an editor linked to another login is left
 * alone, as the synchronisation leaves it.
 *
 * One query reads every relation of such a profile together with the state of the
 * frontend user, ordered by profile and frontend user, and the rule is applied to the
 * rows of each profile in PHP. The query runs without any restriction, so that a
 * disabled, expired or deleted frontend user is seen, and a relation whose `fe_users`
 * row does not exist any more reaches PHP with `null` columns and counts as deleted.
 * The conditions on the profile are written out instead.
 *
 * A start time in the future does not make a frontend user inactive: that user is
 * about to become active, not gone.
 *
 * @internal for the profile cleanup of academic_persons, no public API.
 */
final readonly class InactiveFrontendUserProfileProvider
{
    private const PROFILE_TABLE = 'tx_academicpersons_domain_model_profile';
    private const RELATION_TABLE = 'tx_academicpersons_feuser_mm';

    /**
     * The record type of the frontend users the synchronisation reads, see
     * {@see FrontendUserProvider}.
     */
    private const SYNCHRONISED_USER_TYPE = 'Tx_Academicpersonsedit_Domain_Model_FrontendUser';

    public function __construct(
        private ConnectionPool $connectionPool,
        private Context $context,
    ) {}

    /**
     * A profile is in scope when at least one linked frontend user lies on a page of
     * $includePids, or on any page when $includePids is empty, and none lies on a page
     * of $excludePids. A frontend user row that does not exist any more lies on no page,
     * so a profile linked only to missing rows is in scope only without $includePids.
     * Whether a profile qualifies is always decided by all its linked frontend users.
     *
     * @param int[] $includePids
     * @param int[] $excludePids
     * @return list<ProfileCleanupCandidate> ordered by profile uid
     */
    public function findCandidates(array $includePids = [], array $excludePids = []): array
    {
        $now = (int)$this->context->getPropertyFromAspect('date', 'timestamp');
        $candidates = [];
        $profile = null;
        foreach ($this->fetchRelationRows() as $row) {
            if ($profile === null || $profile['uid'] !== (int)$row['profile_uid']) {
                if ($profile !== null) {
                    $candidates[] = $this->createCandidate($profile, $includePids, $excludePids);
                }
                $profile = [
                    'uid' => (int)$row['profile_uid'],
                    'label' => $this->createLabel($row),
                    'hidden' => (bool)$row['profile_hidden'],
                    'managed' => trim((string)$row['profile_import_identifier']) !== '',
                    'active' => false,
                    'deleted' => true,
                    'pids' => [],
                ];
            }
            if ($row['user_type'] === self::SYNCHRONISED_USER_TYPE) {
                $profile['managed'] = true;
            }
            if ($row['user_uid'] === null || (int)$row['user_deleted'] === 1) {
                $profile['pids'][] = $row['user_pid'] === null ? null : (int)$row['user_pid'];
                continue;
            }
            $profile['pids'][] = (int)$row['user_pid'];
            $profile['deleted'] = false;
            $endTime = (int)$row['user_endtime'];
            if ((int)$row['user_disable'] === 0 && ($endTime === 0 || $endTime > $now)) {
                $profile['active'] = true;
            }
        }
        if ($profile !== null) {
            $candidates[] = $this->createCandidate($profile, $includePids, $excludePids);
        }
        return array_values(array_filter($candidates));
    }

    /**
     * @param array{uid: int, label: string, hidden: bool, managed: bool, active: bool, deleted: bool, pids: list<int|null>} $profile
     * @param int[] $includePids
     * @param int[] $excludePids
     */
    private function createCandidate(array $profile, array $includePids, array $excludePids): ?ProfileCleanupCandidate
    {
        if ($profile['active'] || !$profile['managed']) {
            return null;
        }
        $pids = array_filter($profile['pids'], static fn(?int $pid): bool => $pid !== null);
        if (array_intersect($pids, $excludePids) !== []) {
            return null;
        }
        if ($includePids !== [] && array_intersect($pids, $includePids) === []) {
            return null;
        }
        return new ProfileCleanupCandidate(
            uid: $profile['uid'],
            label: $profile['label'],
            hidden: $profile['hidden'],
            allFrontendUsersDeleted: $profile['deleted'],
        );
    }

    /**
     * @param array<string, mixed> $row
     */
    private function createLabel(array $row): string
    {
        return implode(', ', array_filter(
            [trim((string)$row['profile_last_name']), trim((string)$row['profile_first_name'])],
            static fn(string $name): bool => $name !== '',
        ));
    }

    /**
     * @return iterable<array<string, mixed>>
     */
    private function fetchRelationRows(): iterable
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::PROFILE_TABLE);
        $queryBuilder->getRestrictions()->removeAll();
        $result = $queryBuilder
            ->select(
                'profile.uid AS profile_uid',
                'profile.hidden AS profile_hidden',
                'profile.first_name AS profile_first_name',
                'profile.last_name AS profile_last_name',
                'profile.import_identifier AS profile_import_identifier',
                'frontend_user.uid AS user_uid',
                'frontend_user.pid AS user_pid',
                'frontend_user.deleted AS user_deleted',
                'frontend_user.disable AS user_disable',
                'frontend_user.endtime AS user_endtime',
                'frontend_user.tx_extbase_type AS user_type',
            )
            ->from(self::PROFILE_TABLE, 'profile')
            ->innerJoin(
                'profile',
                self::RELATION_TABLE,
                'relation',
                $queryBuilder->expr()->eq('relation.uid_local', $queryBuilder->quoteIdentifier('profile.uid')),
            )
            ->leftJoin(
                'relation',
                'fe_users',
                'frontend_user',
                $queryBuilder->expr()->eq('frontend_user.uid', $queryBuilder->quoteIdentifier('relation.uid_foreign')),
            )
            ->where(
                $queryBuilder->expr()->eq('profile.deleted', 0),
                $queryBuilder->expr()->in(
                    'profile.sys_language_uid',
                    $queryBuilder->quoteArrayBasedValueListToIntegerList([-1, 0]),
                ),
                $queryBuilder->expr()->eq('profile.t3ver_wsid', 0),
                $queryBuilder->expr()->eq('profile.skip_sync', 0),
            )
            ->orderBy('profile.uid')
            ->addOrderBy('relation.uid_foreign')
            ->executeQuery();
        while ($row = $result->fetchAssociative()) {
            yield $row;
        }
    }
}
