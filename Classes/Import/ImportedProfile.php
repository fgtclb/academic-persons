<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersons\Import;

use Symfony\Component\DependencyInjection\Attribute\Exclude;

/**
 * One person of an external source, as import code hands it to
 * {@see ProfileImportWriter::write()}: the profile and its contracts.
 *
 * The identifier is the import identifier of the profile, `<source>:<key>`.
 * The fields are database columns of the profile table with the values to
 * store. The writer sets the page, the identifier and the relations itself, so
 * the fields must not name `uid`, `pid`, `import_identifier`, a language
 * or workspace column, or `contracts`.
 *
 * The page is where a new profile and its new records are created. An existing
 * profile is never moved, and its new records are created on its own page.
 *
 * @api
 */
#[Exclude]
final readonly class ImportedProfile
{
    /**
     * @param array<string, mixed> $fields database column => value
     * @param list<ImportedContract> $contracts
     */
    public function __construct(
        public string $identifier,
        public int $pid,
        public array $fields = [],
        public array $contracts = [],
    ) {}
}
