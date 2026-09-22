<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Service;

/**
 * How many of the contracts left after filtering a profile view shows.
 *
 * The values are the ones a plugin FlexForm stores in `settings.contracts.display`
 * and an integrator writes to `profile.details.<block>.contracts` in `Settings.yaml`.
 *
 * @internal not part of public API.
 */
enum ContractDisplay: string
{
    case All = 'all';
    case First = 'first';

    /**
     * An unset, empty or unknown value is "all", which is what every view rendered
     * before the setting existed.
     */
    public static function fromSetting(mixed $value): self
    {
        return is_string($value) ? (self::tryFrom(trim($value)) ?? self::All) : self::All;
    }
}
