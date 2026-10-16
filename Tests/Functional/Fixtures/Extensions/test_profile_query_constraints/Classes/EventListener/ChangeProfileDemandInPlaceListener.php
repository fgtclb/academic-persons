<?php

declare(strict_types=1);

namespace TESTS\TestProfileQueryConstraints\EventListener;

use FGTCLB\AcademicBase\Event\ModifyPluginViewEvent;
use FGTCLB\AcademicPersons\Domain\Model\Dto\DemandInterface;
use FGTCLB\AcademicPersons\Event\ModifyProfileDemandEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Fluid\View\FluidViewAdapter;

/**
 * Changes the demand it receives **in place** and never calls `setDemand()`, the opposite of
 * {@see ReplaceProfileDemandListener}: what such a listener reaches beyond the query is what
 * the tests using it are about. It records the selection and the page of every demand it
 * receives, before it changes it, and the demand the list hands to its view.
 *
 * Inert unless a test sets one of the two properties to change, and reset in its `tearDown()`.
 */
final class ChangeProfileDemandInPlaceListener
{
    /**
     * The selection by hand written into the received demand.
     */
    public static ?string $profileList = null;

    /**
     * The page written into the received demand.
     */
    public static ?int $currentPage = null;

    /**
     * @var list<array{profileList: string, currentPage: int}> One entry per call, as received.
     */
    public static array $received = [];

    /**
     * @var array{profileList: string, currentPage: int}|null The `demand` the view received.
     */
    public static ?array $templateDemand = null;

    public static function reset(): void
    {
        self::$profileList = null;
        self::$currentPage = null;
        self::$received = [];
        self::$templateDemand = null;
    }

    #[AsEventListener(identifier: 'test-profile-query-constraints/change-profile-demand-in-place')]
    public function changeDemand(ModifyProfileDemandEvent $event): void
    {
        if (!self::isActive()) {
            return;
        }
        $demand = $event->getDemand();
        self::$received[] = self::describe($demand);
        if (self::$profileList !== null) {
            $demand->setProfileList(self::$profileList);
        }
        if (self::$currentPage !== null) {
            $demand->setCurrentPage(self::$currentPage);
        }
    }

    #[AsEventListener(identifier: 'test-profile-query-constraints/record-template-demand')]
    public function recordTemplateDemand(ModifyPluginViewEvent $event): void
    {
        if (!self::isActive()) {
            return;
        }
        $view = $event->getView();
        // TYPO3 v13 and v14 both hand an Extbase action this adapter.
        if (!$view instanceof FluidViewAdapter) {
            return;
        }
        $demand = $view->getRenderingContext()->getVariableProvider()->get('demand');
        if ($demand instanceof DemandInterface) {
            self::$templateDemand = self::describe($demand);
        }
    }

    private static function isActive(): bool
    {
        return self::$profileList !== null || self::$currentPage !== null;
    }

    /**
     * @return array{profileList: string, currentPage: int}
     */
    private static function describe(DemandInterface $demand): array
    {
        return [
            'profileList' => $demand->getProfileList(),
            'currentPage' => $demand->getCurrentPage(),
        ];
    }
}
