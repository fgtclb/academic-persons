<?php

declare(strict_types=1);

namespace TESTS\TestProfileQueryConstraints\EventListener;

use FGTCLB\AcademicPersons\Domain\Model\Dto\DemandInterface;
use FGTCLB\AcademicPersons\Event\ModifyListProfilesEvent;
use FGTCLB\AcademicPersons\Event\ModifyProfileDemandEvent;

/**
 * Changes the demand it receives **in place** and never calls `setDemand()`: what such a
 * listener reaches beyond the query is what the tests using it are about. It records the
 * selection, the page and the letter of every demand it receives, before it changes it, and of
 * the demand a listener of `ModifyListProfilesEvent` receives after the query, which the list
 * then assigns to its view.
 *
 * Registered with the `event.listener` tag in `Services.yaml`, because
 * `TYPO3\CMS\Core\Attribute\AsEventListener` does not exist on TYPO3 v12. Inert unless a test
 * sets one of the three properties to change, and reset in its `setUp()` and `tearDown()`.
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
     * The letter written into the received demand.
     */
    public static ?string $alphabetFilter = null;

    /**
     * @var list<array{profileList: string, currentPage: int, alphabetFilter: string}> One entry
     *      per call, as received.
     */
    public static array $received = [];

    /**
     * @var array{profileList: string, currentPage: int, alphabetFilter: string}|null The demand
     *      a listener of `ModifyListProfilesEvent` receives.
     */
    public static ?array $listEventDemand = null;

    public static function reset(): void
    {
        self::$profileList = null;
        self::$currentPage = null;
        self::$alphabetFilter = null;
        self::$received = [];
        self::$listEventDemand = null;
    }

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
        if (self::$alphabetFilter !== null) {
            $demand->setAlphabetFilter(self::$alphabetFilter);
        }
    }

    /**
     * The list dispatches this event after the query with its own demand, and assigns the demand
     * the event carries afterwards to the view as `demand`. Nothing else of the test instance
     * replaces it, so the recorded demand is the one of the template as well.
     */
    public function recordListEventDemand(ModifyListProfilesEvent $event): void
    {
        if (!self::isActive()) {
            return;
        }
        self::$listEventDemand = self::describe($event->getProfileDemand());
    }

    private static function isActive(): bool
    {
        return self::$profileList !== null || self::$currentPage !== null || self::$alphabetFilter !== null;
    }

    /**
     * @return array{profileList: string, currentPage: int, alphabetFilter: string}
     */
    private static function describe(DemandInterface $demand): array
    {
        return [
            'profileList' => $demand->getProfileList(),
            'currentPage' => $demand->getCurrentPage(),
            'alphabetFilter' => $demand->getAlphabetFilter(),
        ];
    }
}
