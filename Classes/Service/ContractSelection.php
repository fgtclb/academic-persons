<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Service;

use Symfony\Component\DependencyInjection\Attribute\Exclude;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Which contracts of a profile a view shows, as {@see ContractSelector} applies it.
 *
 * Built from the settings of a plugin, or from one block of the detail view in
 * `Settings.yaml`. The defaults select every contract, so a view without any of the
 * settings renders what it rendered before they existed.
 *
 * @internal not part of public API.
 */
#[Exclude]
final readonly class ContractSelection
{
    /**
     * @param list<int> $organisationalUnits A contract of another unit is left out; empty keeps every unit
     * @param list<int> $functionTypes A contract of another function type is left out; empty keeps every type
     */
    public function __construct(
        public ContractDisplay $display = ContractDisplay::All,
        public array $organisationalUnits = [],
        public array $functionTypes = [],
        public bool $onlyValid = false,
    ) {}

    /**
     * The list, list-and-detail, card and selected-profiles plugins: `contracts.display`,
     * `contracts.onlyValid` and `contracts.matchFilter`, which applies the units and function
     * types the plugin itself is restricted to.
     *
     * @param array<array-key, mixed> $settings
     */
    public static function fromPluginSettings(array $settings): self
    {
        $contracts = is_array($settings['contracts'] ?? null) ? $settings['contracts'] : [];
        $matchFilter = (bool)($contracts['matchFilter'] ?? false);

        return new self(
            display: ContractDisplay::fromSetting($contracts['display'] ?? null),
            organisationalUnits: $matchFilter ? self::uidList($settings['organisationalUnits'] ?? null) : [],
            functionTypes: $matchFilter ? self::uidList($settings['functionTypes'] ?? null) : [],
            onlyValid: (bool)($contracts['onlyValid'] ?? false),
        );
    }

    /**
     * One block of the detail view, `profile.details.position` or `profile.details.contact`:
     * `contracts` and `onlyValid`.
     *
     * @param array<array-key, mixed> $block
     */
    public static function fromDetailBlock(array $block): self
    {
        return new self(
            display: ContractDisplay::fromSetting($block['contracts'] ?? null),
            onlyValid: (bool)($block['onlyValid'] ?? false),
        );
    }

    /**
     * @return list<int>
     */
    private static function uidList(mixed $value): array
    {
        if (!is_string($value) && !is_int($value)) {
            return [];
        }
        return array_values(array_filter(
            GeneralUtility::intExplode(',', (string)$value, true),
            static fn(int $uid): bool => $uid > 0,
        ));
    }
}
