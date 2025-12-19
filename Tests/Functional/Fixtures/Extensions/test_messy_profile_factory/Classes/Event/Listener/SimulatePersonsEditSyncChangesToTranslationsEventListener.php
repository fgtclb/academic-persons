<?php

declare(strict_types=1);

namespace TESTS\TestMessyProfileFactory\Event\Listener;

use FGTCLB\AcademicPersons\Event\AfterProfileUpdateEvent;
use TYPO3\CMS\Core\Error\Http\PageNotFoundException;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Site\Entity\NullSite;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;

final class SimulatePersonsEditSyncChangesToTranslationsEventListener
{
    public function __construct(
        private readonly SiteFinder $siteFinder,
    ) {}

    public function __invoke(AfterProfileUpdateEvent $event): void
    {
        $site = $this->getSite((int)$event->getProfile()->getPid());
        if ($site === null) {
            throw new \RuntimeException(
                sprintf(
                    'Could not determine site configuration for profile "%s" and pageId "%s"',
                    $event->getProfile()->getUid(),
                    $event->getProfile()->getPid(),
                ),
                1766165603,
            );
        }
    }

    /**
     * @param int<0, max> $pid
     * @todo The site object should be passed when dispatching {@see AfterProfileUpdateEvent} as part of the event,
     *       so listener do not have the need to determine it on their own.
     */
    private function getSite(int $pid): ?Site
    {
        if ($pid === 0) {
            return null;
        }
        // First, try to get Site from global request
        $site = ($GLOBALS['TYPO3_REQUEST'] ?? null)?->getAttribute('site');
        // Second, take NullSite as not set, which indicates backend usage without a selected page in the page tree,
        // and may be wrong anyway.
        $site = $site instanceof NullSite ? null : $site;
        // No site yet, get the related site config for `$pid`.
        try {
            $site ??= $this->siteFinder->getSiteByPageId($pid);
        } catch (PageNotFoundException|SiteNotFoundException) {
            // Site could not determined.
            $site = null;
        }
        return $site;
    }
}
