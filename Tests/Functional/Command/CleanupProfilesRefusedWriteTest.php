<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Command;

use FGTCLB\AcademicPersons\Command\CleanupProfilesCommand;
use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use FGTCLB\AcademicPersons\Tests\Functional\Command\Fixtures\Classes\RefuseDeletingProfileThirteen;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * A profile the DataHandler refuses to write is reported, the other profiles are
 * cleaned up all the same, and the run ends with an error. The fixture is the one of
 * {@see CleanupProfilesCommandTest}, and the DataHandler refuses to delete profile 13.
 */
final class CleanupProfilesRefusedWriteTest extends AbstractAcademicPersonsTestCase
{
    protected array $configurationToUseInTestInstance = [
        'SC_OPTIONS' => [
            't3lib/class.t3lib_tcemain.php' => [
                'checkModifyAccessList' => [
                    'refuseDeletingProfileThirteen' => RefuseDeletingProfileThirteen::class,
                ],
            ],
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/CleanupProfiles/profiles.csv');
    }

    #[Test]
    public function aRefusedProfileIsReportedAndTheRunFails(): void
    {
        $tester = new CommandTester($this->get(CleanupProfilesCommand::class));

        $tester->execute([]);

        $this->assertSame(Command::FAILURE, $tester->getStatusCode());
        $display = $tester->getDisplay();
        $this->assertStringContainsString('Profile 13 "Gone, Gus": not deleted.', $display);
        $this->assertStringContainsString('Profile 16 "Hiddendeleted, Hedy": deleted', $display, 'The run goes on');
        $this->assertStringContainsString('9 profile(s) changed.', $display);
        $this->assertStringContainsString('1 profile(s) could not be changed.', $display);
        $deleted = $this->getConnectionPool()
            ->getConnectionForTable('tx_academicpersons_domain_model_profile')
            ->executeQuery('SELECT uid, deleted FROM tx_academicpersons_domain_model_profile WHERE uid IN (3, 13, 16) ORDER BY uid')
            ->fetchAllKeyValue();
        $this->assertSame([3 => 1, 13 => 0, 16 => 1], array_map('intval', $deleted));
    }
}
