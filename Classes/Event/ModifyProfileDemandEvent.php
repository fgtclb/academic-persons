<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Fgtclb\AcademicPersons\Event;

use Fgtclb\AcademicPersons\Domain\Model\Dto\DemandInterface;

final class ModifyProfileDemandEvent
{
    private DemandInterface $demand;

    public function __construct(DemandInterface $demand)
    {
        $this->demand = $demand;
    }

    public function setDemand(DemandInterface $demand): void
    {
        $this->demand = $demand;
    }

    public function getDemand(): DemandInterface
    {
        return $this->demand;
    }
}
