<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Service;

use FGTCLB\AcademicPersons\Domain\Model\Contract;
use FGTCLB\AcademicPersons\Domain\Model\Profile;
use TYPO3\CMS\Core\Context\Context;

/**
 * Selects the contracts of a profile a view shows, in three steps and in this order:
 *
 * 1. the unit and function type filter,
 * 2. validity on the date of the `date` context aspect, where a missing start or end
 *    date is open-ended,
 * 3. "first".
 *
 * So "first" is the first of the contracts the filters leave, in the editor's contract
 * order. The contracts are taken from the profile as loaded; nothing is queried.
 *
 * With validity in play the result also names the next midnight on which it changes:
 * the day after a valid contract ends, or the day a contract left out as not yet
 * started begins. The validity columns hold dates only, so a day is compared as a date
 * in the time zone of the context date.
 *
 * @internal not part of public API.
 */
final readonly class ContractSelector
{
    public function __construct(
        private Context $context,
    ) {}

    public function select(Profile $profile, ContractSelection $selection): ContractSelectionResult
    {
        $today = $this->today();
        $contracts = [];
        $changesAt = null;
        foreach ($profile->getContracts() as $contract) {
            if (!$this->matchesFilter($contract, $selection)) {
                continue;
            }
            if ($selection->onlyValid) {
                $validFrom = $this->day($contract->getValidFrom(), $today);
                if ($validFrom !== null && $validFrom > $today) {
                    $changesAt = $this->earlier($changesAt, $validFrom);
                    continue;
                }
                $validTo = $this->day($contract->getValidTo(), $today);
                if ($validTo !== null && $validTo < $today) {
                    continue;
                }
                if ($validTo !== null) {
                    $changesAt = $this->earlier($changesAt, $validTo->modify('+1 day'));
                }
            }
            $contracts[] = $contract;
        }
        if ($selection->display === ContractDisplay::First) {
            $contracts = array_slice($contracts, 0, 1);
        }

        return new ContractSelectionResult($contracts, $changesAt);
    }

    private function matchesFilter(Contract $contract, ContractSelection $selection): bool
    {
        if ($selection->organisationalUnits !== []
            && !in_array((int)$contract->getOrganisationalUnit()?->getUid(), $selection->organisationalUnits, true)
        ) {
            return false;
        }
        if ($selection->functionTypes !== []
            && !in_array((int)$contract->getFunctionType()?->getUid(), $selection->functionTypes, true)
        ) {
            return false;
        }
        return true;
    }

    /**
     * Midnight of the date the context is rendered for, in the context's time zone.
     */
    private function today(): \DateTimeImmutable
    {
        /** @var \DateTimeImmutable $now */
        $now = $this->context->getPropertyFromAspect('date', 'full');

        return $now->setTime(0, 0);
    }

    private function day(?\DateTimeInterface $date, \DateTimeImmutable $today): ?\DateTimeImmutable
    {
        if ($date === null) {
            return null;
        }
        return \DateTimeImmutable::createFromInterface($date)
            ->setTimezone($today->getTimezone())
            ->setTime(0, 0);
    }

    private function earlier(?\DateTimeImmutable $current, \DateTimeImmutable $candidate): \DateTimeImmutable
    {
        return $current === null || $candidate < $current ? $candidate : $current;
    }
}
