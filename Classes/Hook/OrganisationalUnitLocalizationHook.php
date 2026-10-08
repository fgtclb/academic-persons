<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Hook;

use FGTCLB\AcademicBase\DataHandling\SecondaryParentLocalizationGuard;
use TYPO3\CMS\Core\DataHandling\DataHandler;

/**
 * Keeps the localization of an organisational unit from localizing the contracts it
 * lists (ACE-874). A contract belongs to its profile and is translated with it.
 *
 * See {@see SecondaryParentLocalizationGuard}. Registered in `ext_localconf.php` as a
 * `processCmdmapClass`, public in `Services.yaml` and stateless.
 */
final class OrganisationalUnitLocalizationHook
{
    public function __construct(
        private readonly SecondaryParentLocalizationGuard $secondaryParentLocalizationGuard,
    ) {}

    public function processCmdmap_afterFinish(DataHandler $dataHandler): void
    {
        $this->secondaryParentLocalizationGuard->removeChildrenLocalizedWithParent(
            $dataHandler,
            'tx_academicpersons_domain_model_organisational_unit',
            'contracts',
        );
    }
}
