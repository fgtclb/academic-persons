<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersons\Event;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileReference;

/**
 * Announces the metadata this extension is about to write for the image of a profile,
 * and lets a listener change it. Whatever the listener leaves in {@see getMetadata()}
 * is what is written; an empty array writes nothing.
 *
 * It is dispatched twice, for the two records that carry image metadata, and
 * {@see getTargetTable()} says which one is being written:
 *
 * - `sys_file_metadata`, the record of the file itself. Written once, by the frontend
 *   upload that created the file, and only for the fields it found empty: `title`,
 *   `alternative` and, where `EXT:filemetadata` adds the column, `copyright`. This is
 *   the place for the columns an installation adds and requires beyond those -
 *   `right_of_use` of `fgtclb/file-required-attributes`, for one - which is why the
 *   file is handed over rather than only its uid.
 * - `sys_file_reference`, the profile's own relation row. Written whenever the name of
 *   the profile record changes, from a backend save, a localization or a frontend
 *   edit, and therefore the language-correct place for the text. A value written here
 *   is rewritten on the next save of the profile, so a listener that wants to own a
 *   field has to set it on every dispatch.
 *
 * Both records are handed over, whichever of them is being written: `getFile()` is
 * the file, and its own metadata record is reachable through `$file->getMetaData()`;
 * `getFileReference()` is the relation row of the profile. A listener can therefore
 * read one to decide what to write into the other.
 *
 * Fields the target table does not have are dropped, so a listener may set a column
 * unconditionally: where the installation does not have it, the value goes nowhere.
 * `copyright` is such a column - it belongs to `sys_file_metadata`, and the relation
 * row has no equivalent.
 *
 * **System fields are dropped, not written.** The identity, the relation, the
 * localization, the workspace and the enable columns of the target table - `uid`,
 * `pid`, `file`, `uid_local`, `uid_foreign`, `tablenames`, `fieldname`,
 * `sys_language_uid`, `l10n_parent`, `t3ver_*`, `deleted`, `hidden` and their kind -
 * are refused with a warning in the log. This event sets metadata; repointing a
 * relation or moving a record is the DataHandler's business.
 */
final class ModifyProfileImageMetadataEvent
{
    /**
     * @param array<string, string> $metadata
     */
    public function __construct(
        private readonly string $targetTable,
        private readonly File $file,
        private readonly ?FileReference $fileReference,
        private readonly int $profileUid,
        private array $metadata,
        private readonly ?ServerRequestInterface $request = null,
    ) {}

    /**
     * `sys_file_metadata` or `sys_file_reference`.
     */
    public function getTargetTable(): string
    {
        return $this->targetTable;
    }

    /**
     * The file the profile image is stored in - for the reference write as well, where
     * it is the file the relation points at. Its own metadata record is
     * `$event->getFile()->getMetaData()`.
     */
    public function getFile(): File
    {
        return $this->file;
    }

    /**
     * The image relation of the profile, for both writes: the row being written for
     * `sys_file_reference`, and the row the uploaded file was just assigned to for
     * `sys_file_metadata`. Null only when the profile has no image relation at all.
     */
    public function getFileReference(): ?FileReference
    {
        return $this->fileReference;
    }

    /**
     * The request the write happens in, and null where there is none - a command on
     * the command line, or a caller that has no request to pass on.
     */
    public function getRequest(): ?ServerRequestInterface
    {
        return $this->request;
    }

    /**
     * The profile record the image belongs to - a translation for its own image.
     */
    public function getProfileUid(): int
    {
        return $this->profileUid;
    }

    /**
     * @return array<string, string>
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * @param array<string, string> $metadata
     */
    public function setMetadata(array $metadata): void
    {
        $this->metadata = $metadata;
    }
}
