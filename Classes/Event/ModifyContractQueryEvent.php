<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersons\Event;

use FGTCLB\AcademicPersons\Domain\Model\Contract;
use FGTCLB\AcademicPersons\Domain\Repository\ContractRepository;
use TYPO3\CMS\Extbase\Persistence\Generic\Qom\ConstraintInterface;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;

/**
 * Fired by {@see ContractRepository} before a contract query of the plugins is executed, so that
 * an installed extension can narrow the result without replacing the query the extension builds.
 *
 * The same shape as {@see ModifyProfileQueryEvent}, minus the demand: a contract query is only
 * ever a lookup of the uids an editor selected, and the demand of the profile plugins does not
 * describe it. The contract of that event applies here unchanged - see its docblock.
 */
final class ModifyContractQueryEvent
{
    /**
     * @var list<ConstraintInterface>
     */
    private array $constraints = [];

    /**
     * @param QueryInterface<Contract> $query
     */
    public function __construct(
        private readonly QueryInterface $query,
    ) {}

    /**
     * @return QueryInterface<Contract>
     */
    public function getQuery(): QueryInterface
    {
        return $this->query;
    }

    public function addConstraint(ConstraintInterface $constraint): void
    {
        $this->constraints[] = $constraint;
    }

    /**
     * @return list<ConstraintInterface>
     */
    public function getConstraints(): array
    {
        return $this->constraints;
    }
}
