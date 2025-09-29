<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Service;

use FGTCLB\AcademicPersons\Domain\Model\Dto\Syncronizer\SyncronizerContext;

/**
 * @internal being experimental for now until implementation has been streamlined, tested and covered with tests.
 */
interface RecordSyncronizerInterface
{
    public function syncronize(
        SyncronizerContext $context,
    ): void;
}
