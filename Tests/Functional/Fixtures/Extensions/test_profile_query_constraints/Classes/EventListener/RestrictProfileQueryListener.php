<?php

declare(strict_types=1);

namespace TESTS\TestProfileQueryConstraints\EventListener;

use FGTCLB\AcademicPersons\Event\ModifyProfileQueryEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;

/**
 * Narrows the profiles of the persons plugins, driven by plugin settings so that one fixture
 * extension serves every scenario: a test includes the TypoScript file of the behaviour it
 * wants and the listener stays inert for every other test of the same class.
 */
final class RestrictProfileQueryListener
{
    /**
     * Drives the one branch that cannot be reached through plugin settings: a repository call
     * that passes no context at all, which is what `findByUids()` does when it delegates. Set
     * it in the test and reset it in `tearDown()`.
     */
    public static bool $constrainWithoutContext = false;

    #[AsEventListener(identifier: 'test-profile-query-constraints/restrict-profiles')]
    public function __invoke(ModifyProfileQueryEvent $event): void
    {
        $context = $event->getPluginControllerActionContext();
        if ($context === null) {
            if (self::$constrainWithoutContext) {
                $event->addConstraint($event->getQuery()->equals('lastName', 'Achterberg'));
            }
            return;
        }
        $settings = $context->getSettings();

        // A listener that acts for one plugin only. The plugin name is the one the plugin was
        // registered with ("List", "Card", ...), not the content element type.
        $onlyForPlugin = (string)($settings['testQueryOnlyForPlugin'] ?? '');
        if ($onlyForPlugin !== '' && $onlyForPlugin !== $context->getPluginName()) {
            return;
        }

        $query = $event->getQuery();

        $lastName = (string)($settings['testQueryLastName'] ?? '');
        if ($lastName !== '') {
            $event->addConstraint($query->equals('lastName', $lastName));
        }

        // Expresses a constraint the way a listener is documented not to - with `matching()` on
        // the query - and asks for an ordering of its own. The repository folds the constraint in
        // and overwrites the ordering.
        if ((string)($settings['testQueryFightTheRepository'] ?? '') === '1') {
            $query->matching($query->equals('uid', 999999));
            $query->setOrderings(['lastName' => QueryInterface::ORDER_DESCENDING]);
            return;
        }

        // The same, with a condition that leaves records: what the repository does with it is
        // only observable where the repository has a constraint of its own, because `matching()`
        // replaces. Folded in, the two conditions hold together; had the listener's call been
        // left standing, it would have replaced the content element's filter and widened.
        if ((string)($settings['testQueryMatchingBesideAnEditorFilter'] ?? '') === '1') {
            $query->matching($query->equals('lastName', 'Achterberg'));
            return;
        }

        // The ordering alone, with nothing constrained, so that the list has something to order.
        if ((string)($settings['testQueryReorderTheList'] ?? '') === '1') {
            $query->setOrderings(['lastName' => QueryInterface::ORDER_DESCENDING]);
            return;
        }

        // Reads a setting of the content element itself - the selection an editor made in the
        // FlexForm of this very plugin - rather than one of the shared TypoScript settings.
        if ((string)($settings['testQueryDropFirstSelectedProfile'] ?? '') === '1') {
            $selectedProfiles = GeneralUtility::intExplode(',', (string)($settings['selectedProfiles'] ?? ''), true);
            if ($selectedProfiles !== []) {
                $event->addConstraint($query->logicalNot($query->equals('uid', $selectedProfiles[0])));
            }
        }
    }
}
