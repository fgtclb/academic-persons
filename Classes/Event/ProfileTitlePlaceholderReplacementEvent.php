<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Event;

use FGTCLB\AcademicPersons\Domain\Model\Profile;
use FGTCLB\AcademicPersons\PageTitle\ProfileTitleProvider;

/**
 * Fired in {@see ProfileTitleProvider::dispatchReplacementEvent()} for each found pageTitleFormat
 * placeholder value (`%%SOME_IDENTIFIER%%` => `SOME_IDENTIFER`) to allow changing the replacement
 * value.
 *
 * @internal event implementation is considered experimental for now and not part of Public API.
 */
final class ProfileTitlePlaceholderReplacementEvent
{
    public function __construct(
        private readonly Profile $profile,
        private readonly string $placeholder,
        private string $replacement,
    ) {}

    /**
     * Person profile extbase model for the current detail view page.
     */
    public function getProfile(): Profile
    {
        return $this->profile;
    }

    /**
     * Original placeholder value with removed surrounding `%` signs.
     */
    public function getPlaceholder(): string
    {
        return $this->placeholder;
    }

    /**
     * Current set replacement value for {@see self::getPlaceholder()}.
     */
    public function getReplacement(): string
    {
        return $this->replacement;
    }

    /**
     * Set the replacement value, which should be used to replace the
     * placeholder {@see self::getPlaceholder()}.
     */
    public function setReplacement(string $replacement): void
    {
        $this->replacement = $replacement;
    }
}
