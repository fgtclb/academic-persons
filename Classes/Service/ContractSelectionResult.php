<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Service;

use FGTCLB\AcademicPersons\Domain\Model\Contract;
use Symfony\Component\DependencyInjection\Attribute\Exclude;

/**
 * What {@see ContractSelector::select()} returns: the contracts to show, and the moment
 * from which that answer would be different because a contract starts or stops being
 * valid.
 *
 * @internal not part of public API.
 */
#[Exclude]
final readonly class ContractSelectionResult
{
    /**
     * @param list<Contract> $contracts In the editor's contract order
     * @param \DateTimeImmutable|null $changesAt Midnight of the next day on which the
     *        selection changes; null when it does not depend on the date
     */
    public function __construct(
        public array $contracts,
        public ?\DateTimeImmutable $changesAt = null,
    ) {}
}
