<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Command;

use FGTCLB\AcademicPersons\Command\MigrateSettingsCommand;
use FGTCLB\AcademicPersons\Settings\AcademicPersonsSettings;
use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Yaml\Yaml;

/**
 * With a package still shipping the pre-3.0 keys - the override example of
 * the 2.x manual - the command names it, prints the four section maps the
 * overlay produces for it, and exits with 1 so a pipeline can gate on it.
 */
final class MigrateSettingsCommandTest extends AbstractAcademicPersonsTestCase
{
    protected array $testExtensionsToLoad = [
        'fgtclb/environment-state-manager',
        'fgtclb/academic-base',
        'fgtclb/academic-persons',
        'tests/test-legacy-settings',
    ];

    #[Test]
    public function theLegacyPackageIsNamedAndTheCommandFails(): void
    {
        $tester = new CommandTester($this->get(MigrateSettingsCommand::class));

        $exitCode = $tester->execute([]);

        $this->assertSame(Command::FAILURE, $exitCode);
        $output = $tester->getDisplay();
        $this->assertStringContainsString('# test_legacy_settings: Configuration/AcademicPersons/Settings.yaml', $output);
        $this->assertStringContainsString('# Legacy keys: validations', $output);
        $this->assertStringNotContainsString('academic_persons:', $output, 'The shipped file is not reported');
    }

    /**
     * The printed document is exactly the array the graph of this instance
     * was built from: the four maps, in the shipped order, with the legacy
     * flags applied - so what the integrator pastes changes nothing.
     */
    #[Test]
    public function thePrintedYamlIsTheOverlaidSectionMaps(): void
    {
        $tester = new CommandTester($this->get(MigrateSettingsCommand::class));
        $tester->execute([]);

        $printed = Yaml::parse($tester->getDisplay());

        $this->assertIsArray($printed);
        $this->assertSame(['profile', 'special', 'contracts', 'documentSections'], array_keys($printed));
        $this->assertSame(['url', 'required'], $printed['profile']['website']['validators'], 'The url flag the old shape could not express is kept');
        $this->assertArrayNotHasKey('validators', $printed['profile']['firstName']);
        $this->assertSame(['required', 'email'], $printed['contracts']['contactSections']['emailAddresses']['fields']['emailAddress']['validators']);
        $this->assertSame(['required'], $printed['documentSections']['vita']['validators']['title']);
        $this->assertArrayNotHasKey(
            'year',
            $printed['documentSections']['vita']['validators'],
            'The legacy set does not list the year, so it loses the shipped required and number flags',
        );
        $this->assertSame($this->get(AcademicPersonsSettings::class)->raw, $printed);
    }
}
