<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersons\Settings;

use FGTCLB\AcademicBase\Settings\SettingsFileLoader;

/**
 * Compares the `Configuration/AcademicPersons/Settings.yaml` of every package
 * with the merge of the packages loaded before it, for the settings status
 * report and `academic:persons:settings:migrate --delta`.
 *
 * The packages are folded with {@see SettingsFileLoader::merge()}, the rule the
 * runtime uses, and every result is stated against that rule:
 *
 * - The delta is the smallest array that, merged onto the packages before it,
 *   gives what the package's own file gives, key order included. An entry equal
 *   to the earlier one is left out, a `~` only where an earlier package has the
 *   key. A map whose order the merge takes - one that names every earlier
 *   entry, where leaving out the unchanged ones would change the order - keeps
 *   every entry it names.
 * - A removed entry is a `~` for a key an earlier package has.
 * - An omitted entry is an earlier entry that a copied map leaves out, and that
 *   the package therefore inherits. A map is taken for a copy when it restates
 *   at least two earlier entries unchanged - in any key order, and also where a
 *   restated entry lacks keys upstream added after the copy was made -, or when
 *   it is part of a copy: while the files were merged per top-level key,
 *   everything below a copied map was replaced along with it. A delta restates
 *   nothing it does not change, and a single restated entry does not make a
 *   copy. The top level is never a copy: leaving out a whole top-level map
 *   never removed it.
 *
 * The first package that ships the file has nothing to be compared with and is
 * the base of the comparison; on an installation that is academic_persons.
 *
 * @internal not part of public API.
 */
final readonly class SettingsOverrideComparator
{
    public function __construct(
        private SettingsFileLoader $settingsFileLoader,
    ) {}

    /**
     * @param array<string, array<string, mixed>> $packageArrays As {@see SettingsFileLoader::loadPackageArrays()} returns them
     * @return list<SettingsOverride> One per package after the first one, in loading order
     */
    public function compare(array $packageArrays): array
    {
        $overrides = [];
        $merged = null;
        foreach ($packageArrays as $packageKey => $packageSettings) {
            if ($merged !== null) {
                $overrides[] = new SettingsOverride(
                    (string)$packageKey,
                    $this->delta($merged, $packageSettings),
                    $this->removedEntries($merged, $packageSettings, ''),
                    $this->omittedEntries($merged, $packageSettings, ''),
                );
            }
            $merged = $this->settingsFileLoader->merge($merged ?? [], $packageSettings);
        }
        return $overrides;
    }

    /**
     * @param array<array-key, mixed> $earlier
     * @param array<array-key, mixed> $later
     * @return array<array-key, mixed>
     */
    private function delta(array $earlier, array $later): array
    {
        $delta = [];
        foreach ($later as $key => $value) {
            if (!array_key_exists($key, $earlier)) {
                if ($value !== null) {
                    $delta[$key] = $value;
                }
                continue;
            }
            if ($this->isMap($value) && $this->isMap($earlier[$key])) {
                $nestedDelta = $this->delta($earlier[$key], $value);
                if ($nestedDelta !== []) {
                    // A map with integer keys can shrink to a list, which the
                    // merge would take as a value and replace the map with.
                    $delta[$key] = array_is_list($nestedDelta) ? $value : $nestedDelta;
                }
                continue;
            }
            if ($value !== $earlier[$key]) {
                $delta[$key] = $value;
            }
        }
        if (array_keys($this->merge($earlier, $delta)) === array_keys($this->merge($earlier, $later))) {
            return $delta;
        }
        // The map decides the key order, so the delta restates every entry the
        // map names, each reduced where it can be.
        $restatement = [];
        foreach ($later as $key => $value) {
            if (array_key_exists($key, $delta)) {
                $restatement[$key] = $delta[$key];
            } elseif ($value !== null) {
                $restatement[$key] = $value;
            }
        }
        return $restatement;
    }

    /**
     * @param array<array-key, mixed> $earlier
     * @param array<array-key, mixed> $later
     * @return list<string>
     */
    private function removedEntries(array $earlier, array $later, string $path): array
    {
        $removed = [];
        foreach ($later as $key => $value) {
            if ($value === null && array_key_exists($key, $earlier)) {
                $removed[] = $path . $key;
            } elseif ($this->isMap($value) && $this->isMap($earlier[$key] ?? null)) {
                array_push($removed, ...$this->removedEntries($earlier[$key], $value, $path . $key . '.'));
            }
        }
        return $removed;
    }

    /**
     * @param array<array-key, mixed> $earlier
     * @param array<array-key, mixed> $later
     * @return list<string>
     */
    private function omittedEntries(array $earlier, array $later, string $path, bool $parentIsCopy = false): array
    {
        $isCopy = $path !== '' && ($parentIsCopy || $this->restatedUnchangedCount($earlier, $later) >= 2);
        $omitted = [];
        if ($isCopy) {
            foreach (array_keys(array_diff_key($earlier, $later)) as $key) {
                $omitted[] = $path . $key;
            }
        }
        foreach ($later as $key => $value) {
            if ($this->isMap($value) && $this->isMap($earlier[$key] ?? null)) {
                array_push($omitted, ...$this->omittedEntries($earlier[$key], $value, $path . $key . '.', $isCopy));
            }
        }
        return $omitted;
    }

    /**
     * @param array<array-key, mixed> $earlier
     * @param array<array-key, mixed> $later
     */
    private function restatedUnchangedCount(array $earlier, array $later): int
    {
        $unchanged = 0;
        foreach ($later as $key => $value) {
            if (array_key_exists($key, $earlier) && $this->isUnchanged($earlier[$key], $value)) {
                $unchanged++;
            }
        }
        return $unchanged;
    }

    /**
     * Whether a value states nothing but what the earlier one has: an equal
     * value, or a map whose every entry is unchanged in that sense - whatever
     * its key order, and whether or not it names every earlier entry. A copy
     * made before upstream added a key, or one that sorted its keys, is still
     * a copy.
     */
    private function isUnchanged(mixed $earlier, mixed $later): bool
    {
        if (!$this->isMap($later) || !$this->isMap($earlier)) {
            return $later === $earlier;
        }
        foreach ($later as $key => $value) {
            if (!array_key_exists($key, $earlier) || !$this->isUnchanged($earlier[$key], $value)) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param array<array-key, mixed> $earlier
     * @param array<array-key, mixed> $later
     * @return array<array-key, mixed>
     */
    private function merge(array $earlier, array $later): array
    {
        /** @var array<string, mixed> $earlier */
        /** @var array<string, mixed> $later */
        return $this->settingsFileLoader->merge($earlier, $later);
    }

    /**
     * A map in the sense of the merge: an array that is not a list.
     *
     * @phpstan-assert-if-true array<array-key, mixed> $value
     */
    private function isMap(mixed $value): bool
    {
        return is_array($value) && !array_is_list($value);
    }
}
