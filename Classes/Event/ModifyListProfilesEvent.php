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
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;

final class ModifyListProfilesEvent
{
    /**
     * @var QueryResultInterface<Profile>
     */
    private QueryResultInterface $profiles;

    private ViewInterface $view;

    /**
     * @param QueryResultInterface<Profile> $profiles
     */
    public function __construct(QueryResultInterface $profiles, ViewInterface $view)
    {
        $this->profiles = $profiles;
        $this->view = $view;
    }

    /**
     * @return QueryResultInterface<Profile>
     */
    public function getProfiles(): QueryResultInterface
    {
        return $this->profiles;
    }

    /**
     * @param QueryResultInterface<Profile> $profiles
     */
    public function setProfiles(QueryResultInterface $profiles): void
    {
        $this->profiles = $profiles;
    }

    public function getView(): ViewInterface
    {
        return $this->view;
    }
}
