<?php

declare(strict_types=1);

namespace TESTS\TestTcaOverrideAfterSettings\EventListener;

use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Configuration\Event\AfterTcaCompilationEvent;

/**
 * What the changelog tells a site package that has to differ from the settings in
 * the backend: a listener of its own, ordered after the persons settings. The
 * shipped settings lock the middle name, this listener unlocks it again.
 *
 * The core runs a listener with fewer orderings later, and among listeners with as
 * many orderings the one whose identifier sorts first runs first. This one is ordered after
 * `content-blocks-tca` like the persons listener, and its identifier sorts before
 * that one, so without its ordering after the persons identifier it would run first
 * and lose the middle name to the settings. A renamed persons identifier does the
 * same.
 */
final class UnlockMiddleNameAfterSettings
{
    #[AsEventListener(
        identifier: 'academic-fixture/unlock-middle-name',
        after: 'content-blocks-tca, academic-persons/apply-settings-to-tca',
    )]
    public function __invoke(AfterTcaCompilationEvent $event): void
    {
        $tca = $event->getTca();
        $tca['tx_academicpersons_domain_model_profile']['columns']['middle_name']['config']['readOnly'] = false;
        $event->setTca($tca);
    }
}
