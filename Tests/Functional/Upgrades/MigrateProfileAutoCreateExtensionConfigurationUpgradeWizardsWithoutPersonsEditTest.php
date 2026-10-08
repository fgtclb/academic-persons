<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Upgrades;

use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use FGTCLB\AcademicPersons\Upgrades\MigrateProfileAutoCreateExtensionConfigurationUpgradeWizards;
use PHPUnit\Framework\Attributes\Test;

/**
 * Without `academic_persons_edit` there is nothing to migrate, and the options left over
 * in the configuration of an uninstalled extension are not read.
 */
final class MigrateProfileAutoCreateExtensionConfigurationUpgradeWizardsWithoutPersonsEditTest extends AbstractAcademicPersonsTestCase
{
    #[Test]
    public function updateIsNotNecessaryWithoutAcademicPersonsEdit(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['academic_persons_edit']['profile']['autoCreateProfiles'] = '1';

        $this->assertFalse(
            $this->get(MigrateProfileAutoCreateExtensionConfigurationUpgradeWizards::class)->updateNecessary()
        );
    }
}
