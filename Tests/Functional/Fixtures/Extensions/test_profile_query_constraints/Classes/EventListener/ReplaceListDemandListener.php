<?php

declare(strict_types=1);

namespace TESTS\TestProfileQueryConstraints\EventListener;

use FGTCLB\AcademicPersons\Event\ModifyListProfilesEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;

/**
 * Hands the list action a **replaced** demand after the query ran, asking for a letter -
 * what a listener of the list event can do to the demand the view receives.
 *
 * Inert unless a test sets the letter, and reset in its `tearDown()`.
 */
final class ReplaceListDemandListener
{
    public static ?string $alphabetFilter = null;

    #[AsEventListener(identifier: 'test-profile-query-constraints/replace-list-demand')]
    public function __invoke(ModifyListProfilesEvent $event): void
    {
        if (self::$alphabetFilter === null) {
            return;
        }
        $replacement = clone $event->getProfileDemand();
        $replacement->setAlphabetFilter(self::$alphabetFilter);
        $event->setProfileDemand($replacement);
    }
}
