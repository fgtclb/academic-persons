<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Unit\Service;

use FGTCLB\AcademicPersons\Domain\Model\Contract;
use FGTCLB\AcademicPersons\Domain\Model\FunctionType;
use FGTCLB\AcademicPersons\Domain\Model\OrganisationalUnit;
use FGTCLB\AcademicPersons\Domain\Model\Profile;
use FGTCLB\AcademicPersons\Service\ContractDisplay;
use FGTCLB\AcademicPersons\Service\ContractSelection;
use FGTCLB\AcademicPersons\Service\ContractSelector;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\DateTimeAspect;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * "Today" is the `date` aspect of the context, set per test. Contract dates are built the
 * way the Extbase data mapper builds them from the integer columns: a `\DateTime` in the
 * default time zone at local midnight.
 */
final class ContractSelectorTest extends UnitTestCase
{
    private const TODAY = '2026-03-10 14:30:00';

    private function selector(string $now = self::TODAY): ContractSelector
    {
        $context = new Context();
        $context->setAspect('date', new DateTimeAspect(new \DateTimeImmutable($now)));

        return new ContractSelector($context);
    }

    private function contract(
        string $position,
        ?string $validFrom = null,
        ?string $validTo = null,
        int $unit = 0,
        int $functionType = 0,
    ): Contract {
        $contract = new Contract();
        $contract->setPosition($position);
        $contract->setValidFrom($validFrom === null ? null : new \DateTime($validFrom . ' 00:00:00'));
        $contract->setValidTo($validTo === null ? null : new \DateTime($validTo . ' 00:00:00'));
        if ($unit > 0) {
            $organisationalUnit = new OrganisationalUnit();
            $organisationalUnit->_setProperty('uid', $unit);
            $contract->setOrganisationalUnit($organisationalUnit);
        }
        if ($functionType > 0) {
            $type = new FunctionType();
            $type->_setProperty('uid', $functionType);
            $contract->setFunctionType($type);
        }
        return $contract;
    }

    private function profile(Contract ...$contracts): Profile
    {
        /** @var ObjectStorage<Contract> $storage */
        $storage = new ObjectStorage();
        foreach ($contracts as $contract) {
            $storage->attach($contract);
        }
        $profile = new Profile();
        $profile->setContracts($storage);

        return $profile;
    }

    /**
     * @param list<Contract> $contracts
     * @return list<string>
     */
    private function positions(array $contracts): array
    {
        return array_map(static fn(Contract $contract): string => $contract->getPosition(), $contracts);
    }

    #[Test]
    public function theDefaultSelectionKeepsEveryContractInTheEditorsOrder(): void
    {
        $profile = $this->profile(
            $this->contract('Professor', validTo: '2020-12-31'),
            $this->contract('Dean', validFrom: '2030-01-01'),
            $this->contract('Lecturer'),
        );

        $result = $this->selector()->select($profile, new ContractSelection());

        $this->assertSame(['Professor', 'Dean', 'Lecturer'], $this->positions($result->contracts));
        $this->assertNull($result->changesAt);
    }

    #[Test]
    public function firstKeepsOnlyTheFirstContract(): void
    {
        $profile = $this->profile($this->contract('Professor'), $this->contract('Dean'));

        $result = $this->selector()->select($profile, new ContractSelection(display: ContractDisplay::First));

        $this->assertSame(['Professor'], $this->positions($result->contracts));
    }

    #[Test]
    public function firstOfAProfileWithoutContractsIsEmpty(): void
    {
        $result = $this->selector()->select($this->profile(), new ContractSelection(display: ContractDisplay::First));

        $this->assertSame([], $result->contracts);
    }

    #[Test]
    public function theUnitFilterKeepsTheContractsOfTheGivenUnits(): void
    {
        $profile = $this->profile(
            $this->contract('Mathematics', unit: 1),
            $this->contract('Computer Science', unit: 2),
            $this->contract('Physics', unit: 3),
            $this->contract('No unit'),
        );

        $result = $this->selector()->select($profile, new ContractSelection(organisationalUnits: [2, 3]));

        $this->assertSame(['Computer Science', 'Physics'], $this->positions($result->contracts));
    }

    #[Test]
    public function theFunctionTypeFilterKeepsTheContractsOfTheGivenTypes(): void
    {
        $profile = $this->profile(
            $this->contract('Professor', functionType: 1),
            $this->contract('Dean', functionType: 2),
            $this->contract('No type'),
        );

        $result = $this->selector()->select($profile, new ContractSelection(functionTypes: [2]));

        $this->assertSame(['Dean'], $this->positions($result->contracts));
    }

    #[Test]
    public function aContractHasToMatchTheUnitAndTheFunctionType(): void
    {
        $profile = $this->profile(
            $this->contract('Unit only', unit: 1, functionType: 9),
            $this->contract('Type only', unit: 9, functionType: 1),
            $this->contract('Both', unit: 1, functionType: 1),
        );

        $result = $this->selector()->select(
            $profile,
            new ContractSelection(organisationalUnits: [1], functionTypes: [1]),
        );

        $this->assertSame(['Both'], $this->positions($result->contracts));
    }

    #[Test]
    public function firstIsTheFirstContractLeftByTheFilter(): void
    {
        $profile = $this->profile(
            $this->contract('Mathematics', unit: 1),
            $this->contract('Computer Science', unit: 2),
            $this->contract('Computer Science, second', unit: 2),
        );

        $result = $this->selector()->select(
            $profile,
            new ContractSelection(display: ContractDisplay::First, organisationalUnits: [2]),
        );

        $this->assertSame(['Computer Science'], $this->positions($result->contracts));
    }

    #[Test]
    public function onlyValidKeepsTheContractsValidToday(): void
    {
        $profile = $this->profile(
            $this->contract('Ended yesterday', validFrom: '2020-01-01', validTo: '2026-03-09'),
            $this->contract('Ends today', validFrom: '2020-01-01', validTo: '2026-03-10'),
            $this->contract('Starts today', validFrom: '2026-03-10', validTo: '2027-01-01'),
            $this->contract('Starts tomorrow', validFrom: '2026-03-11'),
            $this->contract('Open start', validTo: '2030-01-01'),
            $this->contract('Open end', validFrom: '2020-01-01'),
            $this->contract('Open on both sides'),
        );

        $result = $this->selector()->select($profile, new ContractSelection(onlyValid: true));

        $this->assertSame(
            ['Ends today', 'Starts today', 'Open start', 'Open end', 'Open on both sides'],
            $this->positions($result->contracts),
        );
    }

    #[Test]
    public function firstIsTheFirstContractValidToday(): void
    {
        $profile = $this->profile(
            $this->contract('Emeritus', validTo: '2025-12-31'),
            $this->contract('Professor', validFrom: '2026-01-01'),
            $this->contract('Dean'),
        );

        $result = $this->selector()->select(
            $profile,
            new ContractSelection(display: ContractDisplay::First, onlyValid: true),
        );

        $this->assertSame(['Professor'], $this->positions($result->contracts));
    }

    /**
     * A simulated date, as the frontend preview of a backend user sets it, is what "today"
     * means - not the server clock.
     */
    #[Test]
    public function todayIsTheDateOfTheContext(): void
    {
        $profile = $this->profile(
            $this->contract('Assistant', validFrom: '2019-01-01', validTo: '2020-12-31'),
            $this->contract('Professor', validFrom: '2021-01-01'),
        );
        $selection = new ContractSelection(onlyValid: true);

        $this->assertSame(['Assistant'], $this->positions($this->selector('2020-06-15 08:00:00')->select($profile, $selection)->contracts));
        $this->assertSame(['Professor'], $this->positions($this->selector('2021-01-01 00:00:00')->select($profile, $selection)->contracts));
        $this->assertSame(['Assistant'], $this->positions($this->selector('2020-12-31 23:59:59')->select($profile, $selection)->contracts));
    }

    /**
     * A day is a day in the time zone of the context date, whatever zone a contract date
     * carries. The end date here is Berlin's midnight of 2026-03-10, held in UTC as
     * 2026-03-09 23:00 - read in its own zone it would be the 9th, and the contract would
     * be gone a day early.
     */
    #[Test]
    public function aDayIsADayInTheTimeZoneOfTheContextDate(): void
    {
        $defaultTimeZone = date_default_timezone_get();
        date_default_timezone_set('Europe/Berlin');
        try {
            $contract = new Contract();
            $contract->setPosition('Professor');
            $contract->setValidTo(new \DateTime('@' . (new \DateTimeImmutable('2026-03-10 00:00:00'))->getTimestamp()));
            // Precondition: the date is held in UTC, not in the zone of the context.
            $this->assertSame('+00:00', $contract->getValidTo()?->getTimezone()->getName());

            $result = $this->selector('2026-03-10 12:00:00')->select(
                $this->profile($contract),
                new ContractSelection(onlyValid: true),
            );

            $this->assertSame(['Professor'], $this->positions($result->contracts));
            $this->assertEquals(new \DateTimeImmutable('2026-03-11 00:00:00'), $result->changesAt);
        } finally {
            date_default_timezone_set($defaultTimeZone);
        }
    }

    #[Test]
    public function aShownContractEndingOnADayChangesTheSelectionAtTheFollowingMidnight(): void
    {
        $profile = $this->profile($this->contract('Professor', validTo: '2026-03-20'));

        $result = $this->selector()->select($profile, new ContractSelection(onlyValid: true));

        $this->assertEquals(new \DateTimeImmutable('2026-03-21 00:00:00'), $result->changesAt);
    }

    #[Test]
    public function aContractEndingTodayChangesTheSelectionAtTheNextMidnight(): void
    {
        $profile = $this->profile($this->contract('Professor', validTo: '2026-03-10'));

        $result = $this->selector()->select($profile, new ContractSelection(onlyValid: true));

        $this->assertEquals(new \DateTimeImmutable('2026-03-11 00:00:00'), $result->changesAt);
    }

    #[Test]
    public function aContractLeftOutBecauseItStartsLaterChangesTheSelectionOnItsStartDate(): void
    {
        $profile = $this->profile($this->contract('Dean', validFrom: '2026-04-01'));

        $result = $this->selector()->select($profile, new ContractSelection(onlyValid: true));

        $this->assertSame([], $result->contracts);
        $this->assertEquals(new \DateTimeImmutable('2026-04-01 00:00:00'), $result->changesAt);
    }

    #[Test]
    public function theEarliestChangeWins(): void
    {
        $profile = $this->profile(
            $this->contract('Professor', validTo: '2026-05-31'),
            $this->contract('Dean', validFrom: '2026-03-15'),
            $this->contract('Lecturer', validTo: '2026-03-12'),
            $this->contract('Assistant', validFrom: '2026-06-01'),
        );

        $result = $this->selector()->select($profile, new ContractSelection(onlyValid: true));

        $this->assertEquals(new \DateTimeImmutable('2026-03-13 00:00:00'), $result->changesAt);
    }

    /**
     * An expired contract never comes back, and an open-ended one never ends: neither
     * changes the selection on a later day.
     */
    #[Test]
    public function expiredAndOpenEndedContractsChangeNothing(): void
    {
        $profile = $this->profile(
            $this->contract('Emeritus', validTo: '2025-12-31'),
            $this->contract('Professor', validFrom: '2020-01-01'),
            $this->contract('Dean'),
        );

        $result = $this->selector()->select($profile, new ContractSelection(onlyValid: true));

        $this->assertNull($result->changesAt);
    }

    /**
     * A contract the unit filter leaves out is never shown, so its dates change nothing.
     */
    #[Test]
    public function aContractLeftOutByTheFilterChangesNothing(): void
    {
        $profile = $this->profile(
            $this->contract('Mathematics', validTo: '2026-03-12', unit: 1),
            $this->contract('Computer Science', unit: 2),
        );

        $result = $this->selector()->select(
            $profile,
            new ContractSelection(organisationalUnits: [2], onlyValid: true),
        );

        $this->assertNull($result->changesAt);
    }

    /**
     * Without "only valid" the output does not depend on the date at all.
     */
    #[Test]
    public function withoutOnlyValidNothingChangesWithTheDate(): void
    {
        $profile = $this->profile(
            $this->contract('Professor', validTo: '2026-03-12'),
            $this->contract('Dean', validFrom: '2026-03-15'),
        );

        $result = $this->selector()->select($profile, new ContractSelection(display: ContractDisplay::First));

        $this->assertNull($result->changesAt);
    }
}
