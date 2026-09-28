<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Unit\EventListener;

use FGTCLB\AcademicBase\Settings\TcaValidationMerger;
use FGTCLB\AcademicBase\Settings\ValidationNormalizer;
use FGTCLB\AcademicBase\Settings\ValidationSet;
use FGTCLB\AcademicPersons\EventListener\ApplySettingsToTca;
use FGTCLB\AcademicPersons\Settings\AcademicPersonsSettings;
use FGTCLB\AcademicPersons\Settings\ProfileField;
use FGTCLB\AcademicPersons\Settings\ProfileSection;
use FGTCLB\AcademicPersons\Settings\ProjectProfileFieldCheck;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Configuration\Event\AfterTcaCompilationEvent;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * What the listener does with a TCA it does not expect, and with the project fields
 * of the settings. The merge itself is covered by the functional tests of the `Tca`
 * folder.
 */
final class ApplySettingsToTcaTest extends UnitTestCase
{
    /**
     * A table of the persons settings that is not in the TCA, removed by a site
     * package or not loaded, gets no fragment that would make it half a table.
     */
    #[Test]
    public function aTcaWithoutThePersonsTablesIsLeftAlone(): void
    {
        $tca = ['pages' => ['ctrl' => ['title' => 'Pages'], 'columns' => ['title' => ['config' => ['type' => 'input']]]]];
        $event = new AfterTcaCompilationEvent($tca);

        $this->listener($this->settingsRequiring(['title' => 'title']))($event);

        $this->assertSame($tca, $event->getTca());
    }

    #[Test]
    public function anAllowedProjectColumnGetsTheSettingsOfItsField(): void
    {
        $event = new AfterTcaCompilationEvent($this->profileTca());

        $this->listener($this->settingsRequiring(['namePrefix' => 'tx_site_prefix'], custom: true))($event);

        $this->assertTrue($event->getTca()[ProjectProfileFieldCheck::TABLE]['columns']['tx_site_prefix']['config']['required'] ?? null);
    }

    /**
     * The notice names the column, the TCA is compiled all the same and gets nothing
     * of the field.
     */
    #[Test]
    public function aRefusedProjectColumnRaisesANoticeAndGetsNothing(): void
    {
        $tca = $this->profileTca();
        $event = new AfterTcaCompilationEvent($tca);

        $notices = $this->collectDeprecationNotices(
            fn() => $this->listener($this->settingsRequiring(['namePrefix' => 'editlock'], custom: true))($event),
        );

        $this->assertSame([
            'The project field "profile.namePrefix" of the persons settings cannot use the column "editlock" of the table'
            . ' "tx_academicpersons_domain_model_profile": it is a system column of the table. The settings of the field'
            . ' are not applied to the TCA, and the frontend profile editor fails until the settings or the TCA are'
            . ' corrected.',
        ], $notices);

        $this->assertSame(
            $tca[ProjectProfileFieldCheck::TABLE]['columns']['editlock'],
            $event->getTca()[ProjectProfileFieldCheck::TABLE]['columns']['editlock'],
        );
    }

    /**
     * A regular field without a column stays silently out of the merge. A notice for
     * it would fail every installation that removed a column the settings still name.
     */
    #[Test]
    public function aRegularFieldWithoutAColumnRaisesNoNotice(): void
    {
        $tca = $this->profileTca();
        $event = new AfterTcaCompilationEvent($tca);

        $notices = $this->collectDeprecationNotices(
            fn() => $this->listener($this->settingsRequiring(['nickname' => 'nickname']))($event),
        );

        $this->assertSame([], $notices);
        $this->assertSame(
            array_keys($tca[ProjectProfileFieldCheck::TABLE]['columns']),
            array_keys($event->getTca()[ProjectProfileFieldCheck::TABLE]['columns']),
        );
    }

    /**
     * The `E_USER_DEPRECATED` notices the callback raises, taken from the test run so
     * that the test asserts them instead of the run failing on them. Every other error
     * is handed to the handler registered before, which is the one of PHPUnit. A
     * handler registered for one level only would send the others to the handler of
     * PHP instead, and a warning of the listener would no longer fail the run.
     *
     * @return list<string>
     */
    private function collectDeprecationNotices(\Closure $callback): array
    {
        $notices = [];
        $previousHandler = null;
        $previousHandler = set_error_handler(
            static function (int $level, string $message, string $file = '', int $line = 0) use (&$notices, &$previousHandler): bool {
                if ($level === E_USER_DEPRECATED) {
                    $notices[] = $message;
                    return true;
                }
                return is_callable($previousHandler) && (bool)$previousHandler($level, $message, $file, $line);
            },
        );
        try {
            $callback();
        } finally {
            restore_error_handler();
        }
        return $notices;
    }

    private function listener(AcademicPersonsSettings $settings): ApplySettingsToTca
    {
        return new ApplySettingsToTca($settings, new TcaValidationMerger(), new ProjectProfileFieldCheck());
    }

    /**
     * @return array<string, mixed>
     */
    private function profileTca(): array
    {
        return [ProjectProfileFieldCheck::TABLE => [
            'ctrl' => ['editlock' => 'editlock', 'enablecolumns' => ['disabled' => 'hidden']],
            'columns' => [
                'hidden' => ['config' => ['type' => 'check']],
                'editlock' => ['config' => ['type' => 'check']],
                'tx_site_prefix' => ['config' => ['type' => 'input']],
            ],
        ]];
    }

    /**
     * @param array<string, string> $columns required fields, identifier => column
     */
    private function settingsRequiring(array $columns, bool $custom = false): AcademicPersonsSettings
    {
        $fields = [];
        $validations = [];
        foreach ($columns as $identifier => $column) {
            $validation = (new ValidationNormalizer())->normalizeValidation(
                identifier: $identifier,
                flags: ['required'],
                fieldName: $column,
                renderType: 'text',
            );
            $fields[$identifier] = new ProfileField(
                identifier: $identifier,
                section: 'information',
                propertyName: $identifier,
                fieldName: $column,
                fieldType: 'input',
                renderType: 'text',
                validation: $validation,
                position: count($fields),
                custom: $custom,
            );
            $validations[$identifier] = $validation;
        }
        return new AcademicPersonsSettings(profileSections: [
            'information' => new ProfileSection(
                identifier: 'information',
                fields: $fields,
                validationSet: new ValidationSet(identifier: 'information', validations: $validations),
                position: 0,
            ),
        ]);
    }
}
