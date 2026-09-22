<?php

declare(strict_types=1);

namespace TESTS\TestProfileQueryConstraints\EventListener;

use FGTCLB\AcademicPersons\Event\ModifyProfileQueryEvent;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;

/**
 * Narrows the profiles of the persons plugins.
 *
 * On this version line the event carries no plugin context, so there are no settings a listener
 * could be driven by. The switches are public statics instead: a test sets the one it is about
 * and resets it in `tearDown()`. Functional tests and the frontend sub-request they render run
 * in the same PHP process, so the listener sees them.
 */
final class RestrictProfileQueryListener
{
    /**
     * Adds "last name equals this" when it is not empty.
     */
    public static string $lastName = '';

    /**
     * Sets a condition with `matching()` on the query instead of adding it, which the repository
     * folds into its own rather than dropping or letting it replace them.
     */
    public static string $lastNameViaMatching = '';

    /**
     * Asks the query for an ordering of its own, which the repository overwrites.
     */
    public static bool $reorderByLastNameDescending = false;

    public function __invoke(ModifyProfileQueryEvent $event): void
    {
        $query = $event->getQuery();

        if (self::$lastName !== '') {
            $event->addConstraint($query->equals('lastName', self::$lastName));
        }

        if (self::$lastNameViaMatching !== '') {
            $query->matching($query->equals('lastName', self::$lastNameViaMatching));
        }

        if (self::$reorderByLastNameDescending) {
            $query->setOrderings(['lastName' => QueryInterface::ORDER_DESCENDING]);
        }
    }

    public static function reset(): void
    {
        self::$lastName = '';
        self::$lastNameViaMatching = '';
        self::$reorderByLastNameDescending = false;
    }
}
