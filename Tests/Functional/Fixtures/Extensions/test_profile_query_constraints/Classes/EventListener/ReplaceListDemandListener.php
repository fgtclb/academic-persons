<?php

declare(strict_types=1);

namespace TESTS\TestProfileQueryConstraints\EventListener;

use FGTCLB\AcademicPersons\Event\ModifyListProfilesEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;

/**
 * Hands the list action a **replaced** demand after the query ran, asking for a letter or
 * a view mode - what a listener of the list event can do to the demand the view receives.
 *
 * Inert unless a test sets one of them, and reset in its `tearDown()`.
 */
final class ReplaceListDemandListener
{
    public static ?string $alphabetFilter = null;

    public static ?string $viewMode = null;

    #[AsEventListener(identifier: 'test-profile-query-constraints/replace-list-demand')]
    public function __invoke(ModifyListProfilesEvent $event): void
    {
        if (self::$alphabetFilter === null && self::$viewMode === null) {
            return;
        }
        $replacement = clone $event->getProfileDemand();
        if (self::$alphabetFilter !== null) {
            $replacement->setAlphabetFilter(self::$alphabetFilter);
        }
        if (self::$viewMode !== null) {
            $replacement->setViewMode(self::$viewMode);
        }
        $event->setProfileDemand($replacement);
    }
}
