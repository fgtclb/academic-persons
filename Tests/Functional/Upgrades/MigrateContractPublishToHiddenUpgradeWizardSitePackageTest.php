<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Upgrades;

use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * A site package that wants the wizard declares its class in its own
 * `Services.yaml` with `autoconfigure: true`, exactly as the fixture extension
 * does and the Important changelog entry of academic_persons shows. The
 * `UpgradeWizard` attribute of the class then registers it under its
 * identifier, on TYPO3 v13 and v14.
 */
final class MigrateContractPublishToHiddenUpgradeWizardSitePackageTest extends AbstractAcademicPersonsTestCase
{
    protected array $testExtensionsToLoad = [
        'fgtclb/environment-state-manager',
        'fgtclb/academic-base',
        'fgtclb/academic-persons',
        'tests/test-contract-publish-wizard',
    ];

    #[Test]
    public function aSitePackageRegistersTheWizardWithItsServicesFile(): void
    {
        $this->assertTrue(MigrateContractPublishToHiddenUpgradeWizardRegistrationTest::isRegistered(
            $this,
            MigrateContractPublishToHiddenUpgradeWizardRegistrationTest::IDENTIFIER,
        ));
    }
}
