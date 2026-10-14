<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Upgrade;

use FGTCLB\AcademicBase\Command\UpgradeCheckCommand;
use FGTCLB\AcademicBase\Upgrade\ConfigurationChecker;
use FGTCLB\AcademicBase\Upgrade\ConfigurationFinding;
use FGTCLB\AcademicBase\Upgrade\ConfigurationFindingKind;
use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;

/**
 * A site package declares a second profile field on the column of the shipped
 * teaching area field. The TCA merge gives the column the flags of the field
 * applied later and drops those of the shipped one, which is the documented
 * behaviour, and the upgrade check of `academic_base` names the column.
 *
 * `academic_base` cannot read the persons settings, so the finding comes from
 * the provider `academic_persons` registers. The test therefore lives here and
 * asks the checker of `academic_base`, which is what the command and the status
 * report entry of the upgrade check ask as well.
 */
final class UpgradeCheckSharedColumnTest extends AbstractAcademicPersonsTestCase
{
    protected array $testExtensionsToLoad = [
        'fgtclb/environment-state-manager',
        'fgtclb/academic-base',
        'fgtclb/academic-persons',
        'tests/test-settings-shared-column',
    ];

    /**
     * What the finding describes: the field applied later decides the flags,
     * the shipped field has none that would make the column required.
     */
    #[Test]
    public function theColumnTakesTheFlagsOfTheFieldAppliedLater(): void
    {
        $this->assertTrue(
            $GLOBALS['TCA']['tx_academicpersons_domain_model_profile']['columns']['teaching_area']['config']['required'] ?? null,
        );
    }

    #[Test]
    public function theColumnIsReportedAsAWarningNamingTableColumnAndBothFields(): void
    {
        $findings = $this->sharedColumnFindings();

        $this->assertCount(1, $findings);
        $finding = $findings[0];
        $this->assertSame(ContextualFeedbackSeverity::WARNING, $finding->severity);
        $this->assertSame('tx_academicpersons_domain_model_profile.teaching_area', $finding->subject);
        $this->assertStringContainsString(
            'The fields "teachingArea", "teachingAreaSummary" of the persons settings all name the column'
            . ' "teaching_area" of the table "tx_academicpersons_domain_model_profile".',
            $finding->message,
        );
        $this->assertStringContainsString(
            'The column takes the backend flags of "teachingAreaSummary", the field applied last, and the flags'
            . ' of "teachingArea" are lost.',
            $finding->message,
        );
    }

    /**
     * A warning is a problem, so the command fails the run.
     */
    #[Test]
    public function theCommandListsTheColumnAndFails(): void
    {
        $tester = new CommandTester($this->get(UpgradeCheckCommand::class));
        $tester->execute([]);

        $this->assertSame(Command::FAILURE, $tester->getStatusCode());
        $this->assertMatchesRegularExpression(
            '/! shared-column +tx_academicpersons_domain_model_profile\.teaching_area/',
            $tester->getDisplay(),
        );
    }

    /**
     * @return list<ConfigurationFinding>
     */
    private function sharedColumnFindings(): array
    {
        return array_values(array_filter(
            $this->get(ConfigurationChecker::class)->check(),
            static fn(ConfigurationFinding $finding): bool => $finding->kind === ConfigurationFindingKind::SharedColumn,
        ));
    }
}
