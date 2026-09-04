<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Command;

use FGTCLB\AcademicPersons\Command\MigrateSettingsCommand;
use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * An installation on the shipped file alone has nothing to migrate: the
 * command says so in one line, prints no document and exits with 0.
 */
final class MigrateSettingsCommandWithoutLegacyKeysTest extends AbstractAcademicPersonsTestCase
{
    #[Test]
    public function nothingIsPrintedAndTheCommandSucceeds(): void
    {
        $tester = new CommandTester($this->get(MigrateSettingsCommand::class));

        $exitCode = $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertSame(
            'No active package ships the legacy keys "validations" or "profileInformationsTypes"'
            . ' in Configuration/AcademicPersons/Settings.yaml.' . "\n",
            $tester->getDisplay(),
        );
    }
}
