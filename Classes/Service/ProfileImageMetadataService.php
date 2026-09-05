<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersons\Service;

use FGTCLB\AcademicPersons\Domain\Model\Profile;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Index\MetaDataRepository;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;

/**
 * Keeps the title and alternative text of a profile's image reference equal to the
 * profile's name, per profile record: a translation carries its own `title`, so its
 * reference - localized or independent - gets the text composed from the translation
 * row, while the default-language reference gets the default-language name.
 *
 * Only the `sys_file_reference` row is written for that. It overrides the file
 * metadata for this reference alone, which is what makes the text language-correct
 * without touching `sys_file_metadata` - a file is shared between the languages of a
 * profile until one of them uploads its own, and its metadata row is the backend
 * editor's.
 *
 * The metadata record of a file the frontend editing just uploaded is the one
 * exception, and {@see initializeFileMetadata()} is it: that record is created empty
 * by the indexer and nothing else ever fills it, while `alternative` and `title` are
 * the fields an installation running `EXT:filemetadata` or
 * `fgtclb/file-required-attributes` marks required. It is written once, for the file
 * of that upload, and only where the record has nothing.
 *
 * Both writers of the profile name reach this service: the DataHandler hook for
 * backend saves and localizations, and the `AfterProfileUpdateEvent` listener for
 * the frontend editing flow, which persists through Extbase and never sees a hook.
 *
 * @internal owned by EXT:academic_persons, no public API.
 */
final readonly class ProfileImageMetadataService
{
    private const PROFILE_TABLE = 'tx_academicpersons_domain_model_profile';

    /**
     * The `sys_file_metadata` columns an upload fills. Both are core columns, so they
     * exist without `EXT:filemetadata`; everything that extension or a project adds is
     * for a listener to fill.
     *
     * @var list<string>
     */
    private const UPLOAD_METADATA_FIELDS = ['title', 'alternative'];

    public function __construct(
        private ConnectionPool $connectionPool,
        private ProfileImageRelationWriter $profileImageRelationWriter,
        private LoggerInterface $logger,
        private TcaSchemaFactory $tcaSchemaFactory,
        private MetaDataRepository $metaDataRepository,
    ) {}

    /**
     * @return array{title: string, alternative: string}|null The written metadata, or
     *                                                          null when the profile
     *                                                          is unpersisted or has
     *                                                          no image reference.
     */
    public function update(Profile $profile): ?array
    {
        $profileUid = $profile->getUid();
        return $profileUid === null ? null : $this->updateForProfileUid($profileUid);
    }

    /**
     * @return array{title: string, alternative: string}|null The written metadata, or
     *                                                          null when the profile
     *                                                          does not exist or has
     *                                                          no image reference.
     */
    public function updateForProfileUid(int $profileUid): ?array
    {
        $metadataText = $this->composeMetadataText($profileUid);
        if ($metadataText === null) {
            return null;
        }
        $imageReference = $this->profileImageRelationWriter->findImageReference($profileUid);
        if ($imageReference === null) {
            return null;
        }
        try {
            $this->profileImageRelationWriter->updateReferenceMetadata($imageReference['uid'], $metadataText, $metadataText);
        } catch (\Throwable $exception) {
            // Called from inside a DataHandler hook: a failed metadata write must not
            // turn an otherwise successful profile save into an exception - and the
            // nested DataHandler run can surface more than the writer's own
            // RuntimeException.
            $this->logger->error(
                'The image metadata of profile {profileUid} could not be written: {reason}',
                ['profileUid' => $profileUid, 'reason' => $exception->getMessage()],
            );
            return null;
        }
        return ['title' => $metadataText, 'alternative' => $metadataText];
    }

    /**
     * Fills the `sys_file_metadata` record of a file the frontend profile editing has
     * just uploaded, and only there: the record the indexer created for it is empty,
     * nothing else in this extension writes it, and `alternative` and `title` are the
     * fields an installation running `EXT:filemetadata` or
     * `fgtclb/file-required-attributes` marks required. A value the record already
     * carries is kept - the metadata record is the backend editor's from then on, and
     * a re-upload of a file that is already indexed must not silently rewrite it.
     *
     * The text is the one {@see updateForProfileUid()} writes on the reference: the
     * composed name of the profile that uploaded the file.
     *
     * @return array<string, string>|null The fields written, or null when there is
     *                                    nothing to write - an unknown profile, a
     *                                    profile without a name, or a record that
     *                                    carries both values already.
     */
    public function initializeFileMetadata(File $file, int $profileUid): ?array
    {
        $fileUid = $file->getUid();
        if ($fileUid <= 0) {
            return null;
        }
        $metadataText = $this->composeMetadataText($profileUid);
        if ($metadataText === null || $metadataText === '') {
            return null;
        }
        try {
            $fileMetadata = $this->metaDataRepository->findByFileUid($fileUid);
            $metadata = [];
            foreach (self::UPLOAD_METADATA_FIELDS as $fieldName) {
                if (trim((string)($fileMetadata[$fieldName] ?? '')) === '') {
                    $metadata[$fieldName] = $metadataText;
                }
            }
            // @todo ACE-89: dispatch a PSR-14 event here that hands the file, the file
            //       reference and $metadata to listeners and writes what they return,
            //       so a project can add the columns its own installation requires -
            //       `copyright` with EXT:filemetadata, `right_of_use` with
            //       `fgtclb/file-required-attributes`.
            if ($metadata === []) {
                return null;
            }
            if ($fileMetadata === []) {
                $this->metaDataRepository->createMetaDataRecord($fileUid, $metadata);
            } else {
                $this->metaDataRepository->update($fileUid, $metadata, $fileMetadata);
            }
        } catch (\Throwable $exception) {
            // The upload itself succeeded and the image is assigned by now: metadata
            // that could not be written is a defect to log, not a reason to refuse the
            // request and delete the file the person just uploaded.
            $this->logger->error(
                'The file metadata of the image of profile {profileUid} could not be written: {reason}',
                ['profileUid' => $profileUid, 'reason' => $exception->getMessage()],
            );
            return null;
        }
        return $metadata;
    }

    /**
     * The text both writers use: the name of the profile record, composed from its
     * title and its name columns. Null when no such record exists.
     */
    private function composeMetadataText(int $profileUid): ?string
    {
        if ($profileUid <= 0) {
            return null;
        }
        $profileRecord = $this->connectionPool
            ->getConnectionForTable(self::PROFILE_TABLE)
            ->select(
                ['title', 'first_name', 'middle_name', 'last_name'],
                self::PROFILE_TABLE,
                ['uid' => $profileUid, $this->getDeletedColumnName() => 0],
            )
            ->fetchAssociative();
        if ($profileRecord === false) {
            return null;
        }
        return $this->buildMetadataText(
            (string)($profileRecord['title'] ?? ''),
            (string)($profileRecord['first_name'] ?? ''),
            (string)($profileRecord['middle_name'] ?? ''),
            (string)($profileRecord['last_name'] ?? ''),
        );
    }

    /**
     * The configured soft-delete column of the profile table. It is TCA `ctrl`
     * configuration, not a constant, so it is read from the schema.
     */
    private function getDeletedColumnName(): string
    {
        return $this->tcaSchemaFactory->get(self::PROFILE_TABLE)
            ->getCapability(TcaSchemaCapability::SoftDelete)
            ->getFieldName();
    }

    private function buildMetadataText(string ...$parts): string
    {
        $parts = array_map(
            static fn(string $part): string => trim((string)preg_replace('/\s+/u', ' ', $part)),
            $parts,
        );
        return implode(' ', array_filter($parts, static fn(string $part): bool => $part !== ''));
    }
}
