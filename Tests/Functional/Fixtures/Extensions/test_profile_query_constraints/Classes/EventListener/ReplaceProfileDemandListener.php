<?php

declare(strict_types=1);

namespace TESTS\TestProfileQueryConstraints\EventListener;

use FGTCLB\AcademicPersons\Event\ModifyProfileDemandEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;

/**
 * Hands the repository a **replaced** demand, never the one it was given changed in place:
 * a listener that only mutated the demand would change the object the repository already
 * holds, and would pass whether or not the repository reads the demand back from the event.
 *
 * Inert unless a test sets one of the two properties, and reset in its `tearDown()`.
 */
final class ReplaceProfileDemandListener
{
    /**
     * @var list<int>|null The function types the replacement is restricted to.
     */
    public static ?array $functionTypes = null;

    /**
     * A letter the replacement asks for, which must not narrow anything but the list itself.
     */
    public static ?string $alphabetFilter = null;

    #[AsEventListener(identifier: 'test-profile-query-constraints/replace-profile-demand')]
    public function __invoke(ModifyProfileDemandEvent $event): void
    {
        if (self::$functionTypes === null && self::$alphabetFilter === null) {
            return;
        }
        $replacement = clone $event->getDemand();
        if (self::$functionTypes !== null && method_exists($replacement, 'setFunctionTypes')) {
            $replacement->setFunctionTypes(self::$functionTypes);
        }
        if (self::$alphabetFilter !== null) {
            $replacement->setAlphabetFilter(self::$alphabetFilter);
        }
        $event->setDemand($replacement);
    }
}
