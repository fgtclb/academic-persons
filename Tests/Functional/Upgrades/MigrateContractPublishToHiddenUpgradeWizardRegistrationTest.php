<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Upgrades;

use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use FGTCLB\AcademicPersons\Upgrades\MigrateContractPublishToHiddenUpgradeWizard;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Information\Typo3Version;

/**
 * The wizard is not offered unless a site package registers it: every contract
 * of an installation that gave `publish` no meaning of its own carries the
 * default "not published", and the wizard would hide all of them.
 */
final class MigrateContractPublishToHiddenUpgradeWizardRegistrationTest extends AbstractAcademicPersonsTestCase
{
    public const IDENTIFIER = 'academicPersons_migrateContractPublishToHidden';

    #[Test]
    public function theWizardIsNotRegisteredByDefault(): void
    {
        $this->assertFalse(self::isRegistered($this, self::IDENTIFIER));
        // The other wizards of the extension are, so the lookup itself works.
        $this->assertTrue(self::isRegistered($this, 'academicPersons_seedContractOrganisationalUnitSorting'));
    }

    /**
     * Once the column is gone, a registered wizard has nothing to do, and running
     * it anyway changes nothing.
     */
    #[Test]
    public function withoutTheColumnThereIsNothingToMigrate(): void
    {
        $subject = new MigrateContractPublishToHiddenUpgradeWizard($this->getConnectionPool());

        $this->assertFalse($subject->updateNecessary());
        $this->assertTrue($subject->executeUpdate());
    }

    /**
     * The registry moved from EXT:install to EXT:core in TYPO3 v14, and neither
     * version has the other's class. The expectation is the same on both, only
     * the place to ask differs.
     */
    public static function isRegistered(AbstractAcademicPersonsTestCase $testCase, string $identifier): bool
    {
        $registryClass = (new Typo3Version())->getMajorVersion() >= 14
            ? 'TYPO3\\CMS\\Core\\Upgrades\\UpgradeWizardRegistry'
            : 'TYPO3\\CMS\\Install\\Updates\\UpgradeWizardRegistry';
        $hasUpgradeWizard = [$testCase->get($registryClass), 'hasUpgradeWizard'];
        $testCase->assertIsCallable($hasUpgradeWizard);
        return (bool)$hasUpgradeWizard($identifier);
    }
}
