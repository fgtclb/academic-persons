<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Fgtclb\AcademicPersons\Event;

use Fgtclb\AcademicPersons\Domain\Model\Profile;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface as DeprecatedExtbaseViewInterface;
use TYPO3Fluid\Fluid\View\ViewInterface;

final class ModifyDetailProfileEvent
{
    private Profile $profile;

    /**
     * The Extbase ViewInterface has been deprecated in TYPO3 v11.5 and has to be replaced with the TYPO3Fluid ViewInterface.
     * @see https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/11.5/Deprecation-95222-ExtbaseViewInterface.html
     *
     * @var ViewInterface|DeprecatedExtbaseViewInterface
     * @todo Add native type when v11 support is dropped.
     */
    private $view;

    /**
     * @param Profile $profile
     * @param ViewInterface|DeprecatedExtbaseViewInterface
     * @todo Add ViewInterface as type for $view when TYPO3 v11 support is dropped.
     */
    public function __construct(Profile $profile, $view)
    {
        $this->profile = $profile;
        $this->view = $view;
    }

    public function getProfile(): Profile
    {
        return $this->profile;
    }

    public function setProfile(Profile $profile): void
    {
        $this->profile = $profile;
    }

    public function getView(): ViewInterface
    {
        return $this->view;
    }
}
