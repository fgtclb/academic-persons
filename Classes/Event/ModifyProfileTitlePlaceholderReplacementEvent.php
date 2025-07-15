<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Event;

use FGTCLB\AcademicPersons\Domain\Model\Dto\PluginControllerActionContextInterface;
use FGTCLB\AcademicPersons\Domain\Model\Profile;
use FGTCLB\AcademicPersons\PageTitle\ProfileTitleProvider;

/**
 * Fired in {@see ProfileTitleProvider::dispatchModifyProfileTitlePlaceholderReplacementEvent()} for each
 * found pageTitleFormat placeholder value (`%%SOME_IDENTIFIER%%` => `SOME_IDENTIFER`) to allow changing
 * the replacement value.
 *
 * Note that the event is executed after all internal replacement methods has been processed and that
 * this event is dispatched for each single placeholder on its own.
 */
final class ModifyProfileTitlePlaceholderReplacementEvent
{
    public function __construct(
        private readonly PluginControllerActionContextInterface $pluginControllerActionContext,
        private readonly Profile $profile,
        private readonly string $placeholder,
        private string $replacement,
    ) {}

    public function getPluginControllerActionContext(): PluginControllerActionContextInterface
    {
        return $this->pluginControllerActionContext;
    }

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
