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
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;

/**
 * Keeps the title and alternative text of a profile's image reference equal to the
 * profile's name, per profile record: a translation carries its own `title`, so its
 * reference - localized or independent - gets the text composed from the translation
 * row, while the default-language reference gets the default-language name.
 *
 * Only the `sys_file_reference` row is written. It overrides the file metadata for
 * this reference alone, which is what makes the text language-correct without
 * touching `sys_file_metadata` - a file is shared between the languages of a profile
 * until one of them uploads its own, and its metadata row is the backend editor's.
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

    public function __construct(
        private ConnectionPool $connectionPool,
        private ProfileImageRelationWriter $profileImageRelationWriter,
        private LoggerInterface $logger,
        private TcaSchemaFactory $tcaSchemaFactory,
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
        $imageReference = $this->profileImageRelationWriter->findImageReference($profileUid);
        if ($imageReference === null) {
            return null;
        }
        $metadataText = $this->buildMetadataText(
            (string)($profileRecord['title'] ?? ''),
            (string)($profileRecord['first_name'] ?? ''),
            (string)($profileRecord['middle_name'] ?? ''),
            (string)($profileRecord['last_name'] ?? ''),
        );
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
