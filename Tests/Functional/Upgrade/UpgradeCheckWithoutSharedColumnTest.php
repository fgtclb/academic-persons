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

/**
 * The shipped persons settings name every column once, so the upgrade check
 * reports nothing about them. The provider of `academic_persons` is active
 * here, `UpgradeCheckSharedColumnTest` shows that it is wired.
 */
final class UpgradeCheckWithoutSharedColumnTest extends AbstractAcademicPersonsTestCase
{
    #[Test]
    public function theShippedSettingsNameEveryColumnOnce(): void
    {
        $this->assertSame(
            [],
            array_values(array_filter(
                $this->get(ConfigurationChecker::class)->check(),
                static fn(ConfigurationFinding $finding): bool => $finding->kind === ConfigurationFindingKind::SharedColumn,
            )),
        );
    }

    /**
     * The same instance as `UpgradeCheckSharedColumnTest` without the second
     * field on the column of the teaching area, so the failure of the command
     * there comes from the shared column and from nothing else.
     */
    #[Test]
    public function theCommandSucceedsWithoutASharedColumn(): void
    {
        $tester = new CommandTester($this->get(UpgradeCheckCommand::class));
        $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode(), $tester->getDisplay());
        $this->assertStringNotContainsString('shared-column', $tester->getDisplay());
    }
}
