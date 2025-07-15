<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersons\Event;

use FGTCLB\AcademicPersons\Controller\ProfileController;
use FGTCLB\AcademicPersons\Domain\Model\Dto\PluginControllerActionContextInterface;
use FGTCLB\AcademicPersons\Domain\Model\Profile;
use FGTCLB\AcademicPersons\PageTitle\ProfileTitleProvider;
use TYPO3\CMS\Core\View\ViewInterface as CoreViewInterface;
use TYPO3Fluid\Fluid\View\ViewInterface as FluidViewInterface;

/**
 * Fired in {@see ProfileController::detailAction()} included in `detail` and `listanddetail`
 * extbase plugins to allow assigning additional data to the detail view or replace the
 * profile.
 */
final class ModifyDetailProfileEvent
{
    public function __construct(
        private Profile $profile,
        private readonly FluidViewInterface|CoreViewInterface $view,
        private readonly PluginControllerActionContextInterface $pluginControllerActionContext,
        private string $defaultPageTitleFormat,
        private string $settingsPageTitleFormat,
    ) {}

    public function getProfile(): Profile
    {
        return $this->profile;
    }

    public function setProfile(Profile $profile): void
    {
        $this->profile = $profile;
    }

    public function getView(): FluidViewInterface|CoreViewInterface
    {
        return $this->view;
    }

    public function getPluginControllerActionContext(): PluginControllerActionContextInterface
    {
        return $this->pluginControllerActionContext;
    }

    /**
     * Default pageTitleFormat defined by `EXT:academic_persons` to use as fallback when no pageTitleFormat is
     * configured for plugin (settings).
     *
     * Default to {@see ProfileTitleProvider::DETAIL_PAGE_TITLE_FORMAT} unless changed with
     * {@see self::setDefaultPageTitleFormat()}.
     */
    public function getDefaultPageTitleFormat(): string
    {
        return $this->defaultPageTitleFormat;
    }

    /**
     * Set the default pageTitleFormat to use as fallback in case plugin setting pageTitleFormat is not set.
     */
    public function setDefaultPageTitleFormat(string $defaultPageTitleFormat): void
    {
        $this->defaultPageTitleFormat = $defaultPageTitleFormat;
    }

    /**
     * PageTitleFormat from plugin settings.
     */
    public function getSettingsPageTitleFormat(): string
    {
        return $this->settingsPageTitleFormat;
    }

    /**
     * PSet the settings pageTitleFormat from plugin settings.
     *
     * Can be used to empty it and use a default or define a custom format to use and not touching the default.
     */
    public function setSettingsPageTitleFormat(string $settingsPageTitleFormat): void
    {
        $this->settingsPageTitleFormat = $settingsPageTitleFormat;
    }

    /**
     * Returns the pageTitleFormat to pass in {@see ProfileController::detailAction()} to
     * method {@see ProfileTitleProvider::setFromProfile()} to set detail view pageTitle.
     *
     * {@see self::getSettingsPageTitleFormat()} -> if empty use -> {@see self::getDefaultPageTitleFormat()}.
     */
    public function getPageTitleFormatToUse(): string
    {
        return $this->settingsPageTitleFormat ?: $this->defaultPageTitleFormat;
    }
}
