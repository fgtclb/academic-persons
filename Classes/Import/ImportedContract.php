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
 * A contract of an {@see ImportedProfile}, with its contact records.
 *
 * The organisational unit and the function type are named by their import
 * identifiers, never by uid, and are looked up when the contract is written. A
 * unit or function type that no record carries is not set and is reported in
 * the {@see ImportResult}. The writer never creates one.
 *
 * The fields are database columns of the contract table. They must not name
 * `uid`, `pid`, `import_identifier`, a language or workspace column,
 * `profile`, `organisational_unit`, `function_type` or one of the three
 * contact columns.
 *
 * @api
 */
#[Exclude]
final readonly class ImportedContract
{
    /**
     * @param array<string, mixed> $fields database column => value
     * @param list<ImportedContact> $emailAddresses
     * @param list<ImportedContact> $phoneNumbers
     * @param list<ImportedContact> $physicalAddresses
     */
    public function __construct(
        public string $identifier,
        public array $fields = [],
        public ?string $organisationalUnitIdentifier = null,
        public ?string $functionTypeIdentifier = null,
        public array $emailAddresses = [],
        public array $phoneNumbers = [],
        public array $physicalAddresses = [],
    ) {}
}
