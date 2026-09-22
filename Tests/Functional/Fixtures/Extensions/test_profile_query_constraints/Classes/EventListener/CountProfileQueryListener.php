<?php

declare(strict_types=1);

namespace TESTS\TestProfileQueryConstraints\EventListener;

use FGTCLB\AcademicPersons\Event\ModifyProfileQueryEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;

/**
 * Counts the profile queries a rendering builds, which is how a test sees a query that
 * leaves no trace in the markup - such as the letter query of a list whose navigation is not
 * rendered. Changes nothing. A test resets it before it renders.
 */
final class CountProfileQueryListener
{
    public static int $dispatches = 0;

    #[AsEventListener(identifier: 'test-profile-query-constraints/count-profile-queries')]
    public function __invoke(ModifyProfileQueryEvent $event): void
    {
        self::$dispatches++;
    }
}
