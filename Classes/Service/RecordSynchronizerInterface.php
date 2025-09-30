<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Service;

use FGTCLB\AcademicPersons\Domain\Model\Dto\Syncronizer\SynchronizerContext;

/**
 * @internal being experimental for now until implementation has been streamlined, tested and covered with tests.
 */
interface RecordSynchronizerInterface
{
    public function synchronize(
        SynchronizerContext $context,
    ): void;
}
