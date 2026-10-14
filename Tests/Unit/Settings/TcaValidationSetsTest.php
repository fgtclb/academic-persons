<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Unit\Settings;

use FGTCLB\AcademicBase\Settings\Validation;
use FGTCLB\AcademicBase\Settings\ValidationNormalizer;
use FGTCLB\AcademicBase\Settings\ValidationSet;
use FGTCLB\AcademicPersons\Settings\AcademicPersonsSettings;
use FGTCLB\AcademicPersons\Settings\ContractContactSection;
use FGTCLB\AcademicPersons\Settings\DocumentSection;
use FGTCLB\AcademicPersons\Settings\ProfileField;
use FGTCLB\AcademicPersons\Settings\ProfileSection;
use FGTCLB\AcademicPersons\Settings\ProjectProfileFieldCheck;
use FGTCLB\AcademicPersons\Settings\SharedColumn;
use FGTCLB\AcademicPersons\Settings\SpecialField;
use FGTCLB\AcademicPersons\Settings\TcaValidationSets;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * A column counts as shared when two fields of one of the sets the listener
 * merges name it, both with a TCA fragment, on a column the merge reaches. The
 * functional `UpgradeCheckSharedColumnTest` covers the profile set through a
 * settings file, these cases cover the other sets, the fields the merge leaves
 * out, and a field that is replaced before a set is built, which nothing can
 * report.
 */
final class TcaValidationSetsTest extends UnitTestCase
{
    private const PROFILE_TABLE = 'tx_academicpersons_domain_model_profile';
    private const EMAIL_TABLE = 'tx_academicpersons_domain_model_email';
    private const PROFILE_INFORMATION_TABLE = 'tx_academicpersons_domain_model_profile_information';

    #[Test]
    public function twoProfileFieldsOnOneColumnAreReportedInTheOrderTheMergeAppliesThem(): void
    {
        $settings = new AcademicPersonsSettings(profileSections: [
            'information' => $this->profileSection([
                'teachingArea' => $this->validation('teachingArea', 'teaching_area'),
                'title' => $this->validation('title', 'title'),
                'teachingAreaSummary' => $this->validation('teachingAreaSummary', 'teaching_area'),
            ]),
        ]);

        $sharedColumns = $this->subject($settings)->findSharedColumns($this->tca());

        $this->assertEquals(
            [new SharedColumn(self::PROFILE_TABLE, 'teaching_area', ['teachingArea', 'teachingAreaSummary'])],
            $sharedColumns,
        );
        $this->assertSame('teachingAreaSummary', $sharedColumns[0]->getDecidingField());
        $this->assertSame(['teachingArea'], $sharedColumns[0]->getLostFields());
        $this->assertSame('tx_academicpersons_domain_model_profile.teaching_area', $sharedColumns[0]->getSubject());
    }

    /**
     * A field whose column the TCA does not have is left out of the merge, so it
     * loses nothing.
     */
    #[Test]
    public function aColumnTheTcaDoesNotHaveIsNotReported(): void
    {
        $settings = new AcademicPersonsSettings(profileSections: [
            'information' => $this->profileSection([
                'nickname' => $this->validation('nickname', 'nickname'),
                'alias' => $this->validation('alias', 'nickname'),
            ]),
        ]);

        $this->assertSame([], $this->subject($settings)->findSharedColumns($this->tca()));
    }

    /**
     * The owner's visibility switch writes the disabled column, which the merge
     * leaves alone, whoever names it.
     */
    #[Test]
    public function theDisabledColumnOfTheProfileIsNotReported(): void
    {
        $settings = new AcademicPersonsSettings(profileSections: [
            'information' => $this->profileSection([
                'hidden' => $this->validation('hidden', 'hidden'),
                'invisible' => $this->validation('invisible', 'hidden'),
            ]),
        ]);

        $this->assertSame([], $this->subject($settings)->findSharedColumns($this->tca()));
    }

    /**
     * A validation without a TCA fragment applies nothing, so it neither loses a
     * fragment nor takes one away.
     */
    #[Test]
    public function aFieldWithoutATcaFragmentDoesNotShareTheColumn(): void
    {
        $settings = new AcademicPersonsSettings(profileSections: [
            'information' => $this->profileSection([
                'teachingArea' => $this->validation('teachingArea', 'teaching_area'),
                'teachingAreaSummary' => new Validation(
                    identifier: 'teachingAreaSummary',
                    fieldName: 'teaching_area',
                    required: false,
                    disabled: false,
                    readOnly: false,
                    validatorClassNames: [],
                    tcaConfig: [],
                ),
            ]),
        ]);

        $this->assertSame([], $this->subject($settings)->findSharedColumns($this->tca()));
    }

    /**
     * The listener merges no project field whose column it refuses, so the field
     * neither loses its flags nor takes those of the other field away. A column
     * of the TCA type `input` refuses the rich text editor.
     */
    #[Test]
    public function aRefusedProjectFieldDoesNotShareTheColumn(): void
    {
        $settings = new AcademicPersonsSettings(profileSections: [
            'information' => $this->profileSectionOfFields([
                $this->projectField('siteNote', 'tx_site_note', 'text'),
                $this->projectField('siteNoteRich', 'tx_site_note', 'ckeditor'),
            ]),
        ]);
        $subject = $this->subject($settings);

        $this->assertSame(['siteNoteRich'], array_keys($subject->findRefusedProjectFields($this->tca())));
        $this->assertSame([], $subject->findSharedColumns($this->tca()));
    }

    /**
     * The special fields are applied after every profile section, so a special
     * field decides the flags of a column it shares with a profile field, whatever
     * the order of the settings file.
     */
    #[Test]
    public function aSpecialFieldOnTheColumnOfAProfileFieldIsAppliedLast(): void
    {
        $settings = new AcademicPersonsSettings(
            profileSections: [
                'information' => $this->profileSection([
                    'doNotSync' => $this->validation('doNotSync', 'skip_sync'),
                ]),
            ],
            specialFields: [
                'skipSync' => $this->specialField('skipSync', 'skip_sync'),
            ],
        );

        $this->assertEquals(
            [new SharedColumn(self::PROFILE_TABLE, 'skip_sync', ['doNotSync', 'skipSync'])],
            $this->subject($settings)->findSharedColumns($this->tca()),
        );
    }

    /**
     * A special field whose identifier is the property name of a profile field
     * replaces the validation of that field while the profile set is built. The
     * column takes the flags of the special field, the flags of the profile field
     * are lost, and no set holds both, so nothing reports it. The documentation
     * names this limit.
     */
    #[Test]
    public function aSpecialFieldReplacingAProfileFieldOfItsIdentifierIsNotReported(): void
    {
        $settings = new AcademicPersonsSettings(
            profileSections: [
                'information' => $this->profileSection([
                    'skipSync' => $this->validation('skipSync', 'skip_sync'),
                ]),
            ],
            specialFields: [
                'skipSync' => $this->specialField('skipSync', 'skip_sync'),
            ],
        );

        $this->assertSame([], $this->subject($settings)->findSharedColumns($this->tca()));
        $this->assertSame(
            $settings->specialFields['skipSync']->validation,
            $settings->getProfileUpdateValidationSet()->validations['skipSync'] ?? null,
        );
    }

    #[Test]
    public function twoFieldsOfAContactSectionOnOneColumnAreReportedForItsTable(): void
    {
        $settings = new AcademicPersonsSettings(contractContactSections: [
            'emailAddresses' => new ContractContactSection(
                identifier: 'emailAddresses',
                fields: [],
                validationSet: new ValidationSet('emailAddresses', [
                    'email' => $this->validation('email', 'email'),
                    'mail' => $this->validation('mail', 'email'),
                ]),
                position: 0,
            ),
        ]);

        $this->assertEquals(
            [new SharedColumn(self::EMAIL_TABLE, 'email', ['email', 'mail'])],
            $this->subject($settings)->findSharedColumns($this->tca()),
        );
    }

    /**
     * Two sections of one record type land in the same `columnsOverrides`, so a
     * column they both validate is shared across them. A section of another type
     * shares nothing with them, and the contract sections have their own table.
     */
    #[Test]
    public function twoDocumentSectionsOfOneRecordTypeShareTheColumnTheyBothValidate(): void
    {
        $settings = new AcademicPersonsSettings(documentSections: [
            'publications' => $this->documentSection('publications', 'publications', ['title']),
            'contracts' => $this->documentSection('contracts', 'contracts', ['title']),
            'formerContracts' => $this->documentSection('formerContracts', 'contracts', ['title']),
            'lectures' => $this->documentSection('lectures', 'lectures', ['title']),
            'morePublications' => $this->documentSection('morePublications', 'publications', ['title', 'link']),
        ]);

        $sharedColumns = $this->subject($settings)->findSharedColumns($this->tca());

        $this->assertEquals(
            [new SharedColumn(self::PROFILE_INFORMATION_TABLE, 'title', ['publications.title', 'morePublications.title'], 'publications')],
            $sharedColumns,
        );
        $this->assertSame(
            'tx_academicpersons_domain_model_profile_information.title (record type "publications")',
            $sharedColumns[0]->getSubject(),
        );
    }

    #[Test]
    public function aTcaWithoutThePersonsTablesHasNoSharedColumn(): void
    {
        $settings = new AcademicPersonsSettings(
            profileSections: [
                'information' => $this->profileSection([
                    'teachingArea' => $this->validation('teachingArea', 'teaching_area'),
                    'teachingAreaSummary' => $this->validation('teachingAreaSummary', 'teaching_area'),
                ]),
            ],
            documentSections: [
                'publications' => $this->documentSection('publications', 'publications', ['title']),
                'morePublications' => $this->documentSection('morePublications', 'publications', ['title']),
            ],
        );

        $this->assertSame([], $this->subject($settings)->findSharedColumns([]));
    }

    private function subject(AcademicPersonsSettings $settings): TcaValidationSets
    {
        return new TcaValidationSets($settings, new ProjectProfileFieldCheck());
    }

    private function validation(string $identifier, string $fieldName): Validation
    {
        return (new ValidationNormalizer())->normalizeValidation(
            identifier: $identifier,
            flags: ['required'],
            fieldName: $fieldName,
        );
    }

    /**
     * @param array<string, Validation> $validations
     */
    private function profileSection(array $validations): ProfileSection
    {
        return new ProfileSection(
            identifier: 'information',
            fields: [],
            validationSet: new ValidationSet('information', $validations),
            position: 0,
        );
    }

    /**
     * @param list<ProfileField> $fields
     */
    private function profileSectionOfFields(array $fields): ProfileSection
    {
        $fieldsByIdentifier = [];
        $validations = [];
        foreach ($fields as $field) {
            $fieldsByIdentifier[$field->identifier] = $field;
            $validations[$field->propertyName] = $field->validation;
        }
        return new ProfileSection(
            identifier: 'information',
            fields: $fieldsByIdentifier,
            validationSet: new ValidationSet('information', $validations),
            position: 0,
        );
    }

    /**
     * A project field names its column and is keyed by its identifier, as the
     * settings factory builds it.
     */
    private function projectField(string $identifier, string $column, string $renderType): ProfileField
    {
        return new ProfileField(
            identifier: $identifier,
            section: 'information',
            propertyName: $identifier,
            fieldName: $column,
            fieldType: 'input',
            renderType: $renderType,
            validation: (new ValidationNormalizer())->normalizeValidation(
                identifier: $identifier,
                flags: ['required'],
                fieldName: $column,
                renderType: $renderType,
            ),
            position: 0,
            custom: true,
        );
    }

    /**
     * A special field with a field type and no fields addresses a profile column
     * directly, as `skipSync` does.
     */
    private function specialField(string $identifier, string $column): SpecialField
    {
        return new SpecialField(
            identifier: $identifier,
            type: 'special',
            fieldType: 'check',
            renderType: 'checkbox',
            fieldIdentifiers: [],
            validation: $this->validation($identifier, $column),
            position: 0,
        );
    }

    /**
     * @param list<string> $requiredProperties
     */
    private function documentSection(string $identifier, string $type, array $requiredProperties): DocumentSection
    {
        $validations = [];
        foreach ($requiredProperties as $property) {
            $validations[$property] = (new ValidationNormalizer())->normalizeValidation($property, ['required']);
        }
        return new DocumentSection(
            identifier: $identifier,
            label: '',
            type: $type,
            fieldName: '',
            readOnly: false,
            validationSet: new ValidationSet($identifier, $validations),
            position: 0,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function tca(): array
    {
        return [
            self::PROFILE_TABLE => [
                'ctrl' => ['enablecolumns' => ['disabled' => 'hidden']],
                'columns' => [
                    'hidden' => ['config' => ['type' => 'check']],
                    'title' => ['config' => ['type' => 'input']],
                    'teaching_area' => ['config' => ['type' => 'text']],
                    'skip_sync' => ['config' => ['type' => 'check']],
                    'tx_site_note' => ['config' => ['type' => 'input']],
                ],
            ],
            self::EMAIL_TABLE => [
                'columns' => ['email' => ['config' => ['type' => 'email']]],
            ],
            self::PROFILE_INFORMATION_TABLE => [
                'columns' => ['title' => ['config' => ['type' => 'input']]],
                'types' => ['publications' => ['showitem' => 'title'], 'lectures' => ['showitem' => 'title']],
            ],
        ];
    }
}
