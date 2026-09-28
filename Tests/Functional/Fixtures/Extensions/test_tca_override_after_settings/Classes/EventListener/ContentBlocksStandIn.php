<?php

declare(strict_types=1);

namespace TESTS\TestTcaOverrideAfterSettings\EventListener;

use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Configuration\Event\AfterTcaCompilationEvent;

/**
 * Stands in for the TCA listener of EXT:content_blocks, under its identifier.
 * That extension builds its TCA in `BeforeTcaOverridesEvent` today. Should it move
 * the listener to this event, the persons settings still have to be applied after
 * it. Without the ordering of the persons listener neither of the two has one,
 * the core then runs them in the order of their identifiers, and this one would
 * come last and keep its lock of the website.
 */
final class ContentBlocksStandIn
{
    #[AsEventListener(identifier: 'content-blocks-tca')]
    public function __invoke(AfterTcaCompilationEvent $event): void
    {
        $tca = $event->getTca();
        $tca['tx_academicpersons_domain_model_profile']['columns']['website']['config']['readOnly'] = true;
        $event->setTca($tca);
    }
}
