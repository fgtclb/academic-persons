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
use FGTCLB\AcademicPersons\Domain\Repository\ProfileRepository;

/**
 * Dispatched in {@see ProfileRepository::findByDemand()}, before the query is built from the
 * demand. The listeners receive a copy of the demand it was given, so a listener changes the
 * query, whether it changes the demand in place or replaces it, and never the demand of the
 * caller.
 */
final class ModifyProfileDemandEvent
{
    public function __construct(private DemandInterface $demand) {}

    public function setDemand(DemandInterface $demand): void
    {
        $this->demand = $demand;
    }

    public function getDemand(): DemandInterface
    {
        return $this->demand;
    }
}
