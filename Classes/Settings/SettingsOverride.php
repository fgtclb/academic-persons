<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersons\Settings;

use Symfony\Component\DependencyInjection\Attribute\Exclude;

/**
 * What the settings file of one package changes, compared with the packages
 * loaded before it - see {@see SettingsOverrideComparator}.
 *
 * @internal not part of public API.
 */
#[Exclude]
final readonly class SettingsOverride
{
    /**
     * @param array<string, mixed> $delta The smallest file with the same effect
     * @param list<string> $removedEntries Dotted paths the package sets to `~`
     * @param list<string> $omittedEntries Dotted paths a copied map leaves out
     */
    public function __construct(
        public string $packageKey,
        public array $delta,
        public array $removedEntries,
        public array $omittedEntries,
    ) {}
}
