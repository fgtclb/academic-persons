<?php

declare(strict_types=1);

namespace TESTS\TestProfileQueryConstraints\EventListener;

use FGTCLB\AcademicPersons\Event\ModifyContractQueryEvent;

/**
 * Excludes one contract uid from the selected-contracts plugin - see
 * {@see RestrictProfileQueryListener} for why the switch is a static.
 */
final class RestrictContractQueryListener
{
    public static int $excludedContractUid = 0;

    public function __invoke(ModifyContractQueryEvent $event): void
    {
        if (self::$excludedContractUid <= 0) {
            return;
        }
        $query = $event->getQuery();
        $event->addConstraint($query->logicalNot($query->equals('uid', self::$excludedContractUid)));
    }

    public static function reset(): void
    {
        self::$excludedContractUid = 0;
    }
}
