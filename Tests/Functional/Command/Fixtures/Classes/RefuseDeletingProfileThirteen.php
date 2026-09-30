<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Command\Fixtures\Classes;

use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\DataHandling\DataHandlerCheckModifyAccessListHookInterface;

/**
 * Refuses the run that deletes profile 13 of the cleanup fixture, so that the
 * DataHandler reports an error for that profile only. The hook is asked for admins
 * too.
 */
final class RefuseDeletingProfileThirteen implements DataHandlerCheckModifyAccessListHookInterface
{
    public function checkModifyAccessList(&$accessAllowed, $table, DataHandler $parent): void
    {
        if ($table === 'tx_academicpersons_domain_model_profile'
            && isset($parent->cmdmap['tx_academicpersons_domain_model_profile'][13])
        ) {
            $accessAllowed = false;
        }
    }
}
