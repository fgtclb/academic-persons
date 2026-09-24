<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Report;

use FGTCLB\AcademicPersons\Report\LegacySettingsStatus;
use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Reports\Registry\StatusRegistry;
use TYPO3\CMS\Reports\Status;

/**
 * Three override packages next to academic_persons: one that copied the contract
 * fields the way an override had to while the files were merged per top-level
 * key, one that names only the layout keys it changes, and one that only removes
 * a field. The status report names the first one with what it removes and what
 * its copy leaves out, the third one with its removal, and says nothing about
 * the second one.
 *
 * The package order of an instance does not follow the order the extensions
 * are listed in here, so no package is asserted to be compared with another;
 * they touch different entries and give the same result in any order.
 */
final class SettingsOverrideStatusTest extends AbstractAcademicPersonsTestCase
{
    protected array $coreExtensionsToLoad = [
        'typo3/cms-install',
        'typo3/cms-rte-ckeditor',
        'typo3/cms-reports',
    ];

    protected array $testExtensionsToLoad = [
        'fgtclb/environment-state-manager',
        'fgtclb/academic-base',
        'fgtclb/academic-persons',
        'tests/test-settings-copy',
        'tests/test-public-profile-settings',
        'tests/test-settings-removal',
    ];

    #[Test]
    public function aCopiedMapIsReportedWithWhatItRemovesAndLeavesOut(): void
    {
        $statuses = $this->statuses();

        $overrides = array_values(array_filter(
            $statuses,
            static fn(Status $status): bool => $status->getValue() === 'test_settings_copy',
        ));
        $this->assertCount(1, $overrides);
        $status = $overrides[0];
        $this->assertSame('Settings overrides', $status->getTitle());
        $this->assertSame(ContextualFeedbackSeverity::NOTICE, $status->getSeverity());
        $this->assertStringContainsString('removes these entries with "~": contracts.fields.officeHours.', $status->getMessage());
        $this->assertStringContainsString('leave these entries out: contracts.fields.room.', $status->getMessage());
        $this->assertStringContainsString('academic:persons:settings:migrate --delta', $status->getMessage());
    }

    /**
     * A removal is what the package says it wants, so it is an info, not a
     * notice: there is nothing left to decide.
     */
    #[Test]
    public function aPackageThatOnlyRemovesIsReportedAsInfo(): void
    {
        $removals = array_values(array_filter(
            $this->statuses(),
            static fn(Status $status): bool => $status->getValue() === 'test_settings_removal',
        ));

        $this->assertCount(1, $removals);
        $this->assertSame(ContextualFeedbackSeverity::INFO, $removals[0]->getSeverity());
        $this->assertStringContainsString('removes these entries with "~": profile.middleName.', $removals[0]->getMessage());
        $this->assertStringNotContainsString('leave these entries out', $removals[0]->getMessage());
    }

    #[Test]
    public function aPackageThatNeitherRemovesNorOmitsIsNotReported(): void
    {
        $statuses = $this->statuses();

        $values = array_map(static fn(Status $status): string => $status->getValue(), $statuses);
        $this->assertNotContains('test_public_profile_settings', $values);
        $this->assertNotContains('academic_persons', $values, 'The shipped file is the base, not an override');
        $this->assertContains('Section maps', $values, 'No package ships a legacy key');
    }

    /**
     * The provider is registered only while EXT:reports is loaded and is not
     * public, so it is taken from the registry that collects it.
     *
     * @return Status[]
     */
    private function statuses(): array
    {
        $providers = array_filter(
            $this->get(StatusRegistry::class)->getProviders(),
            static fn(object $provider): bool => $provider instanceof LegacySettingsStatus,
        );
        $this->assertCount(1, $providers);
        return array_pop($providers)->getStatus();
    }
}
