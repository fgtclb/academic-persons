<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersons\Event;

use FGTCLB\AcademicPersons\Domain\Model\Dto\DemandInterface;
use FGTCLB\AcademicPersons\Domain\Model\Profile;
use FGTCLB\AcademicPersons\Domain\Repository\ProfileRepository;
use TYPO3\CMS\Extbase\Persistence\Generic\Qom\ConstraintInterface;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;

/**
 * Fired by {@see ProfileRepository} before a profile query of the plugins is executed, so that
 * an installed extension can narrow the result without replacing the query the extension builds.
 *
 * A listener builds its conditions on {@see self::getQuery()} and hands them to
 * {@see self::addConstraint()}. The repository combines them with its own conditions using a
 * logical AND and applies everything afterwards, so a **constraint** can only narrow a result and
 * never widen it.
 *
 * A condition set with `matching()` on the query instead is folded into the same AND rather than
 * dropped, but `matching()` replaces, so with two such listeners only the last to run survives -
 * {@see self::addConstraint()} is the way that composes. An ordering a listener sets is
 * overwritten: `setOrderings()` is called after this event and the order belongs to the plugin.
 *
 * Everything else on the query is live and unguarded. The query settings, the limit and the
 * offset are read when the query is parsed, after this event, so a listener that changes them
 * really does change the query - `setRespectStoragePage(false)` and `setIgnoreEnableFields(true)`
 * widen the result past what the content element asked for. Add constraints, leave the rest alone.
 *
 * This version line does not tell a listener which plugin is being rendered, which settings the
 * content element carries or which request it belongs to. A listener therefore narrows every
 * profile query of the plugins alike. Version 3.0 adds that context; a listener written against
 * this event runs there unchanged.
 */
final class ModifyProfileQueryEvent
{
    /**
     * @var list<ConstraintInterface>
     */
    private array $constraints = [];

    /**
     * @param QueryInterface<Profile> $query
     * @param DemandInterface|null $demand The list demand behind the query, `null` for a lookup
     *        of uids an editor selected, which knows no demand.
     */
    public function __construct(
        private readonly QueryInterface $query,
        private readonly ?DemandInterface $demand,
    ) {}

    /**
     * @return QueryInterface<Profile>
     */
    public function getQuery(): QueryInterface
    {
        return $this->query;
    }

    public function getDemand(): ?DemandInterface
    {
        return $this->demand;
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
