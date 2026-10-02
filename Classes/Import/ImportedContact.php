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
 * An e-mail address, a phone number or a physical address of an
 * {@see ImportedContract}. Which one it is follows from the list of the
 * contract it is in.
 *
 * The fields are database columns of the contact table. They must not name
 * `uid`, `pid`, `import_identifier`, a language or workspace column, or
 * `contract`.
 *
 * @api
 */
#[Exclude]
final readonly class ImportedContact
{
    /**
     * @param array<string, mixed> $fields database column => value
     */
    public function __construct(
        public string $identifier,
        public array $fields = [],
    ) {}
}
