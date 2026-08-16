<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Unit\Domain\Model\Dto;

use FGTCLB\AcademicPersons\Domain\Model\Dto\ProfileDemand;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * `ProfileDemand` is mapped by Extbase straight from request arguments in
 * `ProfileController::listAction()` and handed to `ProfileRepository::findByDemand()`.
 * It holds no logic of its own - what it does hold is the state a request that carries
 * no demand at all ends up querying with, and `findByDemand()` branches on exactly that
 * state. Those defaults are the contract, and they are what this test pins.
 */
final class ProfileDemandTest extends UnitTestCase
{
    /**
     * `ProfileRepository::getOrderingsFromDemand()` only accepts a `sortBy` that is
     * listed in the `demand/allowedSortByValues` extension configuration and a direction
     * of `asc` or `desc`. A default that is not in that list leaves the query without any
     * `ORDER BY`, and a profile list would come out in database order.
     */
    #[Test]
    public function aFreshDemandSortsByLastNameAscending(): void
    {
        $subject = new ProfileDemand();

        $this->assertSame('lastName', $subject->getSortBy());
        $this->assertSame('asc', $subject->getSortByDirection());
        $this->assertSame('', $subject->getGroupBy());
    }

    /**
     * Every filter is off and the paginator starts at the first page. A `currentPage` of
     * 0 would reach `SlidingWindowPagination` as an out-of-range page, and a non-empty
     * `alphabetFilter` would put a `last_name LIKE` constraint on a plugin that never
     * asked for one.
     */
    #[Test]
    public function aFreshDemandFiltersNothingAndStartsOnTheFirstPage(): void
    {
        $subject = new ProfileDemand();

        $this->assertSame(1, $subject->getCurrentPage());
        $this->assertSame('', $subject->getAlphabetFilter());
        $this->assertSame('', $subject->getProfileList());
        $this->assertSame([], $subject->getFunctionTypes());
        $this->assertSame([], $subject->getOrganisationalUnits());
    }

    /**
     * The three values that are deliberately not on `DemandInterface` and are only ever
     * set by the controller from plugin settings. Their defaults are the safe end of each
     * branch in `ProfileRepository::applyDemandSettings()`: an empty `storagePages` turns
     * the storage page restriction off instead of restricting to page 0, a fallback other
     * than 1 leaves the language aspect as the site configured it, and `showHiddenRecords`
     * false keeps the enable fields in effect - a default of true would publish every
     * hidden profile of an installation on the next request.
     */
    #[Test]
    public function aFreshDemandTransportsNoPluginOverrides(): void
    {
        $subject = new ProfileDemand();

        $this->assertSame('', $subject->getStoragePages());
        $this->assertSame(0, $subject->getFallbackForNonTranslated());
        $this->assertFalse($subject->getShowHiddenRecords());
    }

    /**
     * Every setter returns the demand itself, not a copy. `ProfileController` and the
     * `ModifyProfileDemandEvent` listeners mutate the instance they were handed and hand
     * the same one on - a setter returning a clone would drop everything set before it
     * from the object that actually reaches the repository.
     */
    #[Test]
    public function theSettersReturnTheSameInstanceSoTheyCanBeChained(): void
    {
        $subject = new ProfileDemand();

        $result = $subject
            ->setGroupBy('contracts.organisationalUnit')
            ->setSortBy('firstName')
            ->setSortByDirection('desc')
            ->setCurrentPage(3)
            ->setAlphabetFilter('B')
            ->setProfileList('12,34')
            ->setFunctionTypes([5, 8])
            ->setOrganisationalUnits([13])
            ->setStoragePages('7,9')
            ->setFallbackForNonTranslated(1)
            ->setShowHiddenRecords(true);

        $this->assertSame($subject, $result);
        $this->assertSame('contracts.organisationalUnit', $subject->getGroupBy());
        $this->assertSame('firstName', $subject->getSortBy());
        $this->assertSame('desc', $subject->getSortByDirection());
        $this->assertSame(3, $subject->getCurrentPage());
        $this->assertSame('B', $subject->getAlphabetFilter());
        $this->assertSame('12,34', $subject->getProfileList());
        $this->assertSame([5, 8], $subject->getFunctionTypes());
        $this->assertSame([13], $subject->getOrganisationalUnits());
        $this->assertSame('7,9', $subject->getStoragePages());
        $this->assertSame(1, $subject->getFallbackForNonTranslated());
        $this->assertTrue($subject->getShowHiddenRecords());
    }

    /**
     * The documented "overrules all other filter options" of `getProfileList()` is a
     * decision `ProfileRepository::applyDemandForQuery()` takes when it reads the demand,
     * not something the DTO enforces. Pinning that: a `ModifyProfileDemandEvent` listener
     * that clears the profile list again gets the previous filters back rather than an
     * emptied demand, and `ProfileController::adoptSettings()` may set both in any order.
     */
    #[Test]
    public function aProfileListDoesNotClearTheOtherFilters(): void
    {
        $subject = new ProfileDemand();
        $subject->setFunctionTypes([5]);
        $subject->setOrganisationalUnits([8]);
        $subject->setAlphabetFilter('B');
        $subject->setSortBy('firstName');

        $subject->setProfileList('12,34');

        $this->assertSame('12,34', $subject->getProfileList());
        $this->assertSame([5], $subject->getFunctionTypes());
        $this->assertSame([8], $subject->getOrganisationalUnits());
        $this->assertSame('B', $subject->getAlphabetFilter());
        $this->assertSame('firstName', $subject->getSortBy());
    }
}
