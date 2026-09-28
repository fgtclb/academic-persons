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
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Configuration\Event\AfterTcaCompilationEvent;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * What the listener does with a TCA it does not expect. The merge itself is covered
 * by the functional tests of the `Tca` folder.
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

        (new ApplySettingsToTca($this->settingsRequiringTheTitle(), new TcaValidationMerger()))($event);

        $this->assertSame($tca, $event->getTca());
    }

    private function settingsRequiringTheTitle(): AcademicPersonsSettings
    {
        $validation = (new ValidationNormalizer())->normalizeValidation(
            identifier: 'title',
            flags: ['required'],
            fieldName: 'title',
            renderType: 'text',
        );
        return new AcademicPersonsSettings(profileSections: [
            'information' => new ProfileSection(
                identifier: 'information',
                fields: ['title' => new ProfileField(
                    identifier: 'title',
                    section: 'information',
                    propertyName: 'title',
                    fieldName: 'title',
                    fieldType: 'input',
                    renderType: 'text',
                    validation: $validation,
                    position: 0,
                )],
                validationSet: new ValidationSet(identifier: 'information', validations: ['title' => $validation]),
                position: 0,
            ),
        ]);
    }
}
