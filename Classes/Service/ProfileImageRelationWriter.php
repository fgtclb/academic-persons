<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersons\Service;

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Writes the `image` relation of a profile record exclusively through the TYPO3
 * DataHandler, so that the reference index, history, hooks and - for a translated
 * profile - the `l10n_state` of the column are maintained by the core.
 *
 * The profile image is a `file` column with `allowLanguageSynchronization`: a
 * translation starts in the `parent` state, where core's `DataMapProcessor` carries
 * the default-language reference into the translation as a localized reference
 * (`l10n_parent` set) whenever the default record is written. `replace()` and
 * `remove()` on a translation switch the column to the `custom` state, which is what
 * makes the translation's image independent from then on.
 *
 * This is the single place that knows how the reference rows of the column are
 * selected: `findImageReference()` orders by `sorting_foreign, uid`, the same order
 * Extbase resolves the single file reference of a profile in, so every caller sees
 * the reference the frontend renders.
 *
 * **Workspaces.** Both tables are workspace aware (`versioningWS`), and a version row
 * keeps the `uid_foreign` of its live record, so an unrestricted lookup would return a
 * draft row beside the live one and the in-place branch of `replace()` could delete the
 * wrong one. Every lookup here is therefore restricted to **live rows** and every write
 * addresses the **live uid** - which is what the DataHandler expects: acting in a
 * non-live workspace it overlays the record itself and writes versioned rows, so a
 * draft edit never touches the live state. That is the model {@see RecordSynchronizer}
 * follows, and the reason a version uid is refused outright. Whether a *frontend*
 * request may act in a workspace at all is the caller's policy, not this class's - see
 * {@see DataHandlerExecutionContext::isFrontendRequestInWorkspace()}.
 *
 * Stateless: everything is passed in or read from the database per call.
 *
 * @internal owned by EXT:academic_persons, no public API.
 */
final readonly class ProfileImageRelationWriter
{
    private const PROFILE_TABLE = 'tx_academicpersons_domain_model_profile';
    private const REFERENCE_TABLE = 'sys_file_reference';
    private const FIELD_NAME = 'image';

    public function __construct(
        private ConnectionPool $connectionPool,
        private ResourceFactory $resourceFactory,
        private DataHandlerExecutionContext $executionContext,
        private TcaSchemaFactory $tcaSchemaFactory,
    ) {}

    /**
     * Assigns $file as the image of the profile.
     *
     * A single reference the profile owns itself (`l10n_parent = 0`) is re-pointed to
     * the new file in place, so its uid, sorting and metadata survive. In every other
     * case - no reference, a localized reference following the default-language
     * profile, or legacy duplicates - one new reference is created and the previous
     * ones are deleted. Either way the profile row is part of the datamap, so the
     * relation counter is re-derived by the DataHandler and a translated profile is
     * switched to the `custom` localization state of the column.
     *
     * @return list<int> The uids of the files the profile referenced before.
     */
    public function replace(int $profileUid, File $file): array
    {
        $profile = $this->getProfileRecord($profileUid);
        $oldReferences = $this->getImageReferences($profileUid);
        if (count($oldReferences) === 1 && $oldReferences[0]['translationParentUid'] === 0) {
            $referenceUid = $oldReferences[0]['uid'];
            $this->executionContext->runAsBackendUser(function (BackendUserAuthentication $backendUser) use (
                $profile,
                $profileUid,
                $referenceUid,
                $file,
            ): void {
                $this->executeDataHandler($backendUser, [
                    self::REFERENCE_TABLE => [
                        $referenceUid => ['uid_local' => $file->getUid()],
                    ],
                    self::PROFILE_TABLE => [
                        $profileUid => $this->buildProfileImageData($profile['languageUid'], (string)$referenceUid),
                    ],
                ]);
            });
            return [$oldReferences[0]['uid_local']];
        }
        $newReferenceId = 'NEW' . bin2hex(random_bytes(16));
        $referenceColumns = $this->getReferenceColumnNames();
        $this->executionContext->runAsBackendUser(function (BackendUserAuthentication $backendUser) use (
            $profile,
            $profileUid,
            $file,
            $newReferenceId,
            $referenceColumns,
        ): void {
            $dataHandler = $this->executeDataHandler($backendUser, [
                self::REFERENCE_TABLE => [
                    $newReferenceId => [
                        'pid' => $profile['pid'],
                        'uid_local' => $file->getUid(),
                        'uid_foreign' => $profileUid,
                        'tablenames' => self::PROFILE_TABLE,
                        'fieldname' => self::FIELD_NAME,
                        'sorting_foreign' => 1,
                        $referenceColumns['language'] => $profile['languageUid'],
                        $referenceColumns['translationParent'] => 0,
                    ],
                ],
                self::PROFILE_TABLE => [
                    $profileUid => $this->buildProfileImageData($profile['languageUid'], $newReferenceId),
                ],
            ]);
            if ((int)($dataHandler->substNEWwithIDs[$newReferenceId] ?? 0) <= 0) {
                throw new \UnexpectedValueException(
                    'The profile image reference could not be created.',
                    1757000001,
                );
            }
        });
        $this->deleteReferences($oldReferences);
        return array_values(array_unique(array_column($oldReferences, 'uid_local')));
    }

    /**
     * Drops the image of the profile: the relation counter becomes 0, every reference
     * of the column is deleted, and a translated profile is switched to the `custom`
     * localization state so the default-language image is not carried in again.
     *
     * @return list<int> The uids of the files the profile referenced before.
     */
    public function remove(int $profileUid): array
    {
        $profile = $this->getProfileRecord($profileUid);
        $oldReferences = $this->getImageReferences($profileUid);
        $this->executionContext->runAsBackendUser(
            function (BackendUserAuthentication $backendUser) use ($profile, $profileUid): void {
                $this->executeDataHandler($backendUser, [
                    self::PROFILE_TABLE => [
                        $profileUid => $this->buildProfileImageData($profile['languageUid'], ''),
                    ],
                ]);
            },
        );
        $this->deleteReferences($oldReferences);
        return array_values(array_unique(array_column($oldReferences, 'uid_local')));
    }

    /**
     * Re-submits the current image relation of a default-language profile, which makes
     * core's `DataMapProcessor` carry it into every translation whose image column is
     * in the `parent` state: a missing localized reference is created, a reference
     * whose default-language origin is gone is deleted, and the relation counter of
     * the translation is corrected. Translations in the `custom` state are untouched,
     * and so is the default record itself - the submitted value is what it already has.
     */
    public function propagateToTranslations(int $defaultProfileUid): void
    {
        $profile = $this->getProfileRecord($defaultProfileUid);
        if ($profile['languageUid'] !== 0) {
            throw new \InvalidArgumentException(
                sprintf('Profile %d is a translation, only a default-language profile can be propagated.', $defaultProfileUid),
                1757000002,
            );
        }
        $referenceUids = array_column($this->getImageReferences($defaultProfileUid), 'uid');
        $this->executionContext->runAsBackendUser(
            function (BackendUserAuthentication $backendUser) use ($defaultProfileUid, $referenceUids): void {
                $this->executeDataHandler($backendUser, [
                    self::PROFILE_TABLE => [
                        $defaultProfileUid => [self::FIELD_NAME => implode(',', $referenceUids)],
                    ],
                ]);
            },
        );
    }

    /**
     * Writes the title and alternative text of one image reference. Both columns of
     * `sys_file_reference` override the file metadata for this reference only, so the
     * file itself - which may be shared between profiles or languages - is never touched.
     */
    public function updateReferenceMetadata(int $referenceUid, string $title, string $alternative): void
    {
        $this->executionContext->runAsBackendUser(
            function (BackendUserAuthentication $backendUser) use ($referenceUid, $title, $alternative): void {
                $this->executeDataHandler($backendUser, [
                    self::REFERENCE_TABLE => [
                        $referenceUid => ['title' => $title, 'alternative' => $alternative],
                    ],
                ]);
            },
        );
    }

    /**
     * The reference the profile renders as its image, or null without one. Resolves
     * the real relation row instead of interpreting the profile's `image` column: the
     * DataHandler stores a relation count there, not a reference uid.
     *
     * @return array{uid: int, uid_local: int, languageUid: int, translationParentUid: int}|null
     */
    public function findImageReference(int $profileUid): ?array
    {
        return $this->getImageReferences($profileUid)[0] ?? null;
    }

    /**
     * Number of non-deleted `sys_file_reference` rows pointing at the file, in every
     * table and column. A hidden reference counts, and so does a workspace version -
     * visibility is not existence, and a draft still using the file is a reason to keep
     * it. Over-counting is the safe direction; under-counting deletes a file in use.
     *
     * **This counts FAL relations only.** A file used through a soft reference - an RTE
     * `t3://file` link, a typolink in a text column, a file collection - has no
     * `sys_file_reference` row at all and is reported as unused here. See the note on
     * {@see deleteUnreferencedFiles()} for what follows from that.
     */
    public function countFileReferences(File $file): int
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::REFERENCE_TABLE);
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        return (int)$queryBuilder
            ->count('uid')
            ->from(self::REFERENCE_TABLE)
            ->where(
                $queryBuilder->expr()->eq(
                    'uid_local',
                    $queryBuilder->createNamedParameter($file->getUid(), Connection::PARAM_INT),
                ),
            )
            ->executeQuery()
            ->fetchOne();
    }

    /**
     * Deletes every file of the list that no FAL relation uses any more, through its
     * storage so the index and the metadata go with it. $retainedFileUid is skipped
     * without a lookup - it is the file that was just assigned.
     *
     * Reachability is answered by {@see countFileReferences()}, which sees FAL relations
     * only, so this stays a narrow, interactive cleanup: the file an editor just
     * replaced in the profile editor, uploaded by that editor for that profile. It must
     * not be used over a bulk of records - a file that is only linked from an RTE text
     * would be deleted unattended and unrecoverably.
     *
     * @param list<int> $fileUids
     */
    public function deleteUnreferencedFiles(array $fileUids, int $retainedFileUid = 0): void
    {
        foreach (array_unique($fileUids) as $fileUid) {
            if ($fileUid <= 0 || $fileUid === $retainedFileUid) {
                continue;
            }
            try {
                $file = $this->resourceFactory->getFileObject($fileUid);
            } catch (FileDoesNotExistException) {
                continue;
            }
            if ($this->countFileReferences($file) === 0) {
                $file->getStorage()->deleteFile($file);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function buildProfileImageData(int $languageUid, string $imageValue): array
    {
        $data = [self::FIELD_NAME => $imageValue];
        if ($languageUid > 0) {
            $data['l10n_state'] = [self::FIELD_NAME => 'custom'];
        }
        return $data;
    }

    /**
     * The configured system columns of the profile table this class reads. They are
     * TCA `ctrl` configuration, not constants, so they are read from the schema.
     *
     * @return array{language: string, deleted: string}
     */
    private function getProfileColumnNames(): array
    {
        $schema = $this->tcaSchemaFactory->get(self::PROFILE_TABLE);
        return [
            'language' => $schema->getCapability(TcaSchemaCapability::Language)->getLanguageField()->getName(),
            'deleted' => $schema->getCapability(TcaSchemaCapability::SoftDelete)->getFieldName(),
        ];
    }

    /**
     * The configured localization columns of the reference table. They belong to
     * core's own `ctrl` section, which is TCA all the same.
     *
     * @return array{language: string, translationParent: string}
     */
    private function getReferenceColumnNames(): array
    {
        $languageCapability = $this->tcaSchemaFactory->get(self::REFERENCE_TABLE)
            ->getCapability(TcaSchemaCapability::Language);
        return [
            'language' => $languageCapability->getLanguageField()->getName(),
            'translationParent' => $languageCapability->getTranslationOriginPointerField()->getName(),
        ];
    }

    /**
     * The live profile row. A uid addressing a workspace version is refused: the
     * DataHandler addresses versioned records through their live uid and overlays them
     * itself, so writing against a version uid would publish draft state as live.
     *
     * @return array{pid: int, languageUid: int}
     */
    private function getProfileRecord(int $profileUid): array
    {
        $columns = $this->getProfileColumnNames();
        $record = $this->connectionPool
            ->getConnectionForTable(self::PROFILE_TABLE)
            ->select(
                ['pid', $columns['language'], 't3ver_oid'],
                self::PROFILE_TABLE,
                ['uid' => $profileUid, $columns['deleted'] => 0],
            )
            ->fetchAssociative();
        if ($record === false) {
            throw new \UnexpectedValueException(
                sprintf('Profile %d does not exist.', $profileUid),
                1757000003,
            );
        }
        if ((int)$record['t3ver_oid'] > 0) {
            throw new \InvalidArgumentException(
                sprintf('Profile %d is a workspace version, address the live record instead.', $profileUid),
                1757000005,
            );
        }
        return [
            'pid' => (int)$record['pid'],
            'languageUid' => (int)$record[$columns['language']],
        ];
    }

    /**
     * The live, non-deleted references of the image column, ordered the way Extbase
     * picks the single reference it renders - `sorting_foreign` first, `uid` as
     * tiebreaker. Workspace versions are excluded: they carry the `uid_foreign` of
     * their live row, so they would be returned beside it and make both the pick and
     * the count of the column ambiguous.
     *
     * @return list<array{uid: int, uid_local: int, languageUid: int, translationParentUid: int}>
     */
    private function getImageReferences(int $profileUid): array
    {
        $columns = $this->getReferenceColumnNames();
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::REFERENCE_TABLE);
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, 0));
        $rows = $queryBuilder
            ->select('uid', 'uid_local', $columns['language'], $columns['translationParent'])
            ->from(self::REFERENCE_TABLE)
            ->where(
                $queryBuilder->expr()->eq(
                    'uid_foreign',
                    $queryBuilder->createNamedParameter($profileUid, Connection::PARAM_INT),
                ),
                $queryBuilder->expr()->eq(
                    'tablenames',
                    $queryBuilder->createNamedParameter(self::PROFILE_TABLE),
                ),
                $queryBuilder->expr()->eq(
                    'fieldname',
                    $queryBuilder->createNamedParameter(self::FIELD_NAME),
                ),
            )
            ->orderBy('sorting_foreign')
            ->addOrderBy('uid')
            ->executeQuery()
            ->fetchAllAssociative();
        return array_map(
            static fn(array $row): array => [
                'uid' => (int)$row['uid'],
                'uid_local' => (int)$row['uid_local'],
                'languageUid' => (int)$row[$columns['language']],
                'translationParentUid' => (int)$row[$columns['translationParent']],
            ],
            $rows,
        );
    }

    /**
     * @param list<array{uid: int, uid_local: int, languageUid: int, translationParentUid: int}> $references
     */
    private function deleteReferences(array $references): void
    {
        if ($references === []) {
            return;
        }
        $this->executionContext->runAsBackendUser(function (BackendUserAuthentication $backendUser) use ($references): void {
            $cmdmap = [];
            foreach ($references as $reference) {
                $cmdmap[self::REFERENCE_TABLE][$reference['uid']]['delete'] = 1;
            }
            $this->executeDataHandler($backendUser, cmdmap: $cmdmap);
        });
    }

    /**
     * One DataHandler pass. A DataHandler error is an exception here, not a log line:
     * every caller changes a relation the profile row's counter has to agree with, and
     * a half-applied write is worse than a failed one.
     *
     * @param array<string, array<int|string, array<string, mixed>>> $datamap
     * @param array<string, array<int|string, array<string, mixed>>> $cmdmap
     */
    private function executeDataHandler(
        BackendUserAuthentication $backendUser,
        array $datamap = [],
        array $cmdmap = [],
    ): DataHandler {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start($datamap, $cmdmap, $backendUser);
        if ($datamap !== []) {
            $dataHandler->process_datamap();
        }
        if ($cmdmap !== []) {
            $dataHandler->process_cmdmap();
        }
        if ($dataHandler->errorLog !== []) {
            throw new \RuntimeException(
                'DataHandler reported errors while writing a profile image relation: ' . implode(' ', $dataHandler->errorLog),
                1757000004,
            );
        }
        return $dataHandler;
    }
}
