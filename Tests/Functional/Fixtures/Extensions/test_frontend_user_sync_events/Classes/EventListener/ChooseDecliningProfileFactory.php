<?php

declare(strict_types=1);

namespace TESTS\TestFrontendUserSyncEvents\EventListener;

use FGTCLB\AcademicPersons\Event\ChooseProfileFactoryEvent;
use TESTS\TestFrontendUserSyncEvents\Profile\DecliningProfileFactory;
use TYPO3\CMS\Core\Attribute\AsEventListener;

/**
 * Chooses the declining factory for the user named `declined`.
 */
final readonly class ChooseDecliningProfileFactory
{
    public function __construct(
        private DecliningProfileFactory $decliningProfileFactory,
    ) {}

    #[AsEventListener(identifier: 'test-frontend-user-sync-events/choose-declining-factory')]
    public function __invoke(ChooseProfileFactoryEvent $event): void
    {
        if (($event->getFrontendUserAuthentication()->user['username'] ?? '') === 'declined') {
            $event->setProfileFactory($this->decliningProfileFactory);
        }
    }
}
