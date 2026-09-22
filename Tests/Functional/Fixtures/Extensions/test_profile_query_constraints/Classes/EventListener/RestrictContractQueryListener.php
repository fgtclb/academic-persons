<?php

declare(strict_types=1);

namespace TESTS\TestProfileQueryConstraints\EventListener;

use FGTCLB\AcademicPersons\Event\ModifyContractQueryEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;

/**
 * Excludes one contract uid from the selected-contracts plugin, driven by a plugin setting -
 * see {@see RestrictProfileQueryListener} for why.
 */
final class RestrictContractQueryListener
{
    #[AsEventListener(identifier: 'test-profile-query-constraints/restrict-contracts')]
    public function __invoke(ModifyContractQueryEvent $event): void
    {
        $context = $event->getPluginControllerActionContext();
        if ($context === null) {
            return;
        }
        $excludedContract = (int)($context->getSettings()['testQueryExcludeContract'] ?? 0);
        if ($excludedContract <= 0) {
            return;
        }
        $query = $event->getQuery();
        $event->addConstraint($query->logicalNot($query->equals('uid', $excludedContract)));
    }
}
