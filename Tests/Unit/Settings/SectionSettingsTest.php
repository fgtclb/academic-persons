<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Unit\Settings;

use FGTCLB\AcademicBase\Settings\Validation;
use FGTCLB\AcademicBase\Settings\ValidationSet;
use FGTCLB\AcademicPersons\Settings\AcademicPersonsSettings;
use FGTCLB\AcademicPersons\Settings\ContractContactField;
use FGTCLB\AcademicPersons\Settings\ContractField;
use FGTCLB\AcademicPersons\Settings\DocumentSection;
use FGTCLB\AcademicPersons\Settings\ProfileField;
use FGTCLB\AcademicPersons\Settings\ProfileSection;
use FGTCLB\AcademicPersons\Settings\PublicProfileSettings;
use FGTCLB\AcademicPersons\Settings\SpecialField;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * The value objects of the section graph. Each `isValid()` is the gate the factory
 * keeps an entry behind, so what it accepts is what ends up cached and what it
 * rejects is dropped without a log line; and each `__set_state()` is what restores
 * the object from the `return <var_export>;` statement of the core cache.
 */
final class SectionSettingsTest extends UnitTestCase
{
    /**
     * @return array<string, array{0: array{identifier: string, section: string, propertyName: string, fieldName: string, fieldType: string, renderType: string}, 1: bool}>
     */
    public static function profileFieldArguments(): array
    {
        $complete = [
            'identifier' => 'firstName',
            'section' => 'information',
            'propertyName' => 'firstName',
            'fieldName' => 'first_name',
            'fieldType' => 'input',
            'renderType' => 'text',
        ];
        return [
            'complete' => [$complete, true],
            'without identifier' => [['identifier' => ''] + $complete, false],
            'without section' => [['section' => ''] + $complete, false],
            'without property name' => [['propertyName' => ''] + $complete, false],
            'without field name' => [['fieldName' => ''] + $complete, false],
            'without field type' => [['fieldType' => ''] + $complete, false],
            'without render type' => [['renderType' => ''] + $complete, false],
        ];
    }

    /**
     * A profile field needs every one of its six names: the section groups it, the
     * property and column address the record, the two types render it.
     *
     * @param array{identifier: string, section: string, propertyName: string, fieldName: string, fieldType: string, renderType: string} $arguments
     */
    #[Test]
    #[DataProvider('profileFieldArguments')]
    public function aProfileFieldIsValidOnlyWithAllOfItsNames(array $arguments, bool $expected): void
    {
        $field = new ProfileField(
            identifier: $arguments['identifier'],
            section: $arguments['section'],
            propertyName: $arguments['propertyName'],
            fieldName: $arguments['fieldName'],
            fieldType: $arguments['fieldType'],
            renderType: $arguments['renderType'],
            validation: $this->validation('firstName', 'first_name'),
            position: 0,
        );

        $this->assertSame($expected, $field->isValid());
    }

    /**
     * A contact field is a profile field owned by a contact section; a contract
     * field has no section and needs no option source or help text.
     */
    #[Test]
    public function contactAndContractFieldsAreValidWithTheirNamesAndTypes(): void
    {
        $contact = new ContractContactField(
            identifier: 'emailAddress',
            section: 'emailAddresses',
            propertyName: 'email',
            fieldName: 'email',
            fieldType: 'input',
            renderType: 'email',
            validation: $this->validation('email', 'email', ['required', 'email']),
            position: 0,
            autocomplete: 'email',
        );
        $contract = new ContractField(
            identifier: 'organisationalUnit',
            propertyName: 'organisationalUnit',
            fieldName: 'organisational_unit',
            fieldType: 'select',
            renderType: 'select',
            optionSource: 'organisationalUnits',
            helptext: '',
            validation: $this->validation('organisationalUnit', 'organisational_unit'),
            position: 1,
        );

        $this->assertTrue($contact->isValid());
        $this->assertSame('email', $contact->autocomplete);
        $this->assertTrue($contract->isValid());
        $this->assertSame('', $contract->autocomplete);
        $this->assertSame('organisationalUnits', $contract->optionSource);
        $this->assertFalse((new ContractContactField('street', '', 'street', 'street', 'input', 'text', $this->validation('street', 'street'), 0))->isValid());
        $this->assertFalse((new ContractField('room', 'room', 'room', '', 'text', '', '', $this->validation('room', 'room'), 0))->isValid());
    }

    /**
     * A special field is anything of `type: special` with a renderer. Only one with
     * a `fieldType` and without composed fields stands for a profile column of its
     * own - that is what decides whether it takes part in the profile validation.
     */
    #[Test]
    public function aSpecialFieldStandsForAProfileColumnOnlyWithAFieldTypeAndNoComposedFields(): void
    {
        $title = new SpecialField('title', 'special', '', 'title', ['title', 'firstName'], $this->validation('title', 'title'), 0);
        $image = new SpecialField('image', 'special', '', 'cropper', [], $this->validation('image', 'image'), 1);
        $skipSync = new SpecialField('skipSync', 'special', 'check', 'checkbox', [], $this->validation('skipSync', 'skip_sync'), 2);
        $regular = new SpecialField('gender', 'regular', 'select', 'select', [], $this->validation('gender', 'gender'), 3);
        $withoutRenderer = new SpecialField('image', 'special', '', '', [], $this->validation('image', 'image'), 4);

        $this->assertTrue($title->isValid());
        $this->assertFalse($title->hasDirectProfileProperty());
        $this->assertTrue($image->isValid());
        $this->assertFalse($image->hasDirectProfileProperty());
        $this->assertTrue($skipSync->isValid());
        $this->assertTrue($skipSync->hasDirectProfileProperty());
        $this->assertFalse($regular->isValid());
        $this->assertFalse($withoutRenderer->isValid());
    }

    #[Test]
    public function aDocumentSectionNeedsALabelATypeAndARelation(): void
    {
        $this->assertTrue($this->documentSection('vita', 'curriculum_vitae')->isValid());
        $this->assertFalse($this->documentSection('vita', '')->isValid());
        $this->assertFalse($this->documentSection('vita', 'curriculum_vitae', label: '')->isValid());
        $this->assertFalse($this->documentSection('vita', 'curriculum_vitae', fieldName: '')->isValid());
        $this->assertFalse($this->documentSection('', 'curriculum_vitae')->isValid());
    }

    /**
     * The contracts section is recognised by its identifier or by its type, because
     * an override may keep either; its rows are contract records, not profile
     * information, which is what the TCA fragment builder branches on.
     */
    #[Test]
    public function theContractsSectionIsRecognisedByIdentifierOrType(): void
    {
        $this->assertTrue($this->documentSection('contracts', 'contracts')->isContractSection());
        $this->assertTrue($this->documentSection('employment', 'contract')->isContractSection());
        $this->assertTrue($this->documentSection('contracts', 'anything')->isContractSection());
        $this->assertFalse($this->documentSection('vita', 'curriculum_vitae')->isContractSection());
    }

    /**
     * An action is available only when listed, matched without regard to case or
     * surrounding blanks; both directions together enable drag sorting.
     */
    #[Test]
    public function documentActionsAreAnsweredFromTheConfiguredList(): void
    {
        $section = $this->documentSection('cooperation', 'cooperation', actions: ['view', 'down', 'up', 'edit']);

        $this->assertTrue($section->allowsAction('edit'));
        $this->assertTrue($section->allowsAction(' Edit '));
        $this->assertFalse($section->allowsAction('delete'));
        $this->assertTrue($section->allowsCreate());
        $this->assertTrue($section->allowsDragSorting());
        $this->assertSame(['view', 'down', 'up', 'edit'], $section->getAllowedActions());
        $this->assertFalse($this->documentSection('cooperation', 'cooperation', actions: ['view', 'up'])->allowsDragSorting());
    }

    /**
     * `readonly` wins over the list: a read-only section offers viewing and
     * nothing else, whatever an override left in `actions`.
     */
    #[Test]
    public function aReadOnlyDocumentSectionKeepsOnlyItsViewCapability(): void
    {
        $section = $this->documentSection(
            'contracts',
            'contracts',
            readOnly: true,
            rowFields: ['from', 'position'],
            actions: ['view', 'down', 'up', 'delete', 'edit'],
        );

        $this->assertSame(['view'], $section->getAllowedActions());
        $this->assertTrue($section->allowsAction('view'));
        $this->assertFalse($section->allowsAction('edit'));
        $this->assertFalse($section->allowsCreate());
        $this->assertFalse($section->allowsDragSorting());
        $this->assertSame(['from', 'position'], $section->rowFields);
    }

    #[Test]
    public function sectionsResolveTheirFieldsByIdentifierOnly(): void
    {
        $validation = $this->validation('email', 'email');
        $field = new ContractContactField('emailAddress', 'emailAddresses', 'email', 'email', 'input', 'email', $validation, 0);
        $section = new \FGTCLB\AcademicPersons\Settings\ContractContactSection(
            identifier: 'emailAddresses',
            fields: ['emailAddress' => $field],
            validationSet: new ValidationSet('emailAddresses', ['email' => $validation]),
            position: 0,
        );
        $profileField = new ProfileField('firstName', 'information', 'firstName', 'first_name', 'input', 'text', $this->validation('firstName', 'first_name'), 0);
        $profileSection = new ProfileSection('information', ['firstName' => $profileField], new ValidationSet('information', []), 0);

        $this->assertSame($field, $section->getField('emailAddress'));
        $this->assertNull($section->getField('email'));
        $this->assertSame($profileField, $profileSection->getField('firstName'));
        $this->assertNull($profileSection->getField('first_name'));
    }

    /**
     * Every value object of the graph, nested as the factory nests them, through
     * `var_export()` and back. A property missing from a `__set_state()` is lost
     * on every request but the first - the defect only the cached path shows.
     */
    #[Test]
    public function theCompleteSectionGraphSurvivesTheCacheRoundTrip(): void
    {
        $miscellaneous = $this->validation('miscellaneous', 'miscellaneous', ['html'], characterLimit: 1000);
        $subject = new AcademicPersonsSettings(
            profileSections: [
                'aboutme' => new ProfileSection(
                    identifier: 'aboutme',
                    fields: [
                        'miscellaneous' => new ProfileField('miscellaneous', 'aboutme', 'miscellaneous', 'miscellaneous', 'textarea', 'ckeditor', $miscellaneous, 0, 'LLL:helptext.miscellaneous'),
                    ],
                    validationSet: new ValidationSet('aboutme', ['miscellaneous' => $miscellaneous]),
                    position: 0,
                ),
            ],
            specialFields: [
                'skipSync' => new SpecialField('skipSync', 'special', 'check', 'checkbox', [], $this->validation('skipSync', 'skip_sync'), 0),
                'title' => new SpecialField('title', 'special', '', 'title', ['title', 'firstName'], $this->validation('title', 'title'), 1),
                'image' => new SpecialField('image', 'special', '', 'cropper', [], $this->validation('image', 'image'), 2, ['ratio' => '3x4']),
            ],
            contractFields: [
                'position' => new ContractField('position', 'position', 'position', 'input', 'text', '', 'LLL:position', $this->validation('position', 'position', ['required']), 0, 'organization-title'),
            ],
            contractContactSections: [
                'emailAddresses' => new \FGTCLB\AcademicPersons\Settings\ContractContactSection(
                    identifier: 'emailAddresses',
                    fields: [
                        'emailAddress' => new ContractContactField('emailAddress', 'emailAddresses', 'email', 'email', 'input', 'email', $this->validation('email', 'email', ['required', 'email']), 0, 'email', 'LLL:helptext.emailAddress'),
                    ],
                    validationSet: new ValidationSet('emailAddresses', ['email' => $this->validation('email', 'email', ['required', 'email'])]),
                    position: 0,
                ),
            ],
            documentSections: [
                'publications' => $this->documentSection('publications', 'publication', rowFields: ['year', 'title'], actions: ['view', 'down', 'up', 'delete', 'edit'], helptexts: ['title' => 'LLL:helptext.title', 'from' => 'LLL:helptext.from']),
            ],
            publicProfile: new PublicProfileSettings(
                structure: ['left' => ['menuSections'], 'right' => ['headline', 'profileEntries']],
                details: [
                    'headline' => ['title', 'firstName', 'lastName'],
                    'subline' => 'LLL:EXT:academic_persons/Resources/Private/Language/locallang.xlf:detail.subline',
                    'menuSectionsDatas' => ['publications' => 'publications'],
                ],
            ),
            raw: ['profile' => ['miscellaneous' => ['section' => 'aboutme']]],
        );

        $restored = eval('return ' . var_export($subject, true) . ';');

        $this->assertInstanceOf(AcademicPersonsSettings::class, $restored);
        $this->assertEquals($subject, $restored);
        $this->assertNotSame($subject, $restored);
        $restoredField = $restored->getProfileField('miscellaneous');
        $restoredSection = $restored->getDocumentSection('publications');
        $this->assertNotNull($restoredField);
        $this->assertNotNull($restoredSection);
        $this->assertSame('ckeditor', $restoredField->renderType);
        $this->assertSame(1000, $restoredField->validation->characterLimit);
        $this->assertSame('LLL:helptext.miscellaneous', $restoredField->helptext);
        $restoredTitle = $restored->getSpecialField('title');
        $restoredEmail = $restored->getContractContactField('emailAddress');
        $this->assertNotNull($restoredTitle);
        $this->assertNotNull($restoredEmail);
        $this->assertSame(['ratio' => '3x4'], $restored->getSpecialField('image')?->settings);
        $this->assertSame([], $restoredTitle->settings);
        $this->assertSame(['title', 'firstName'], $restoredTitle->fieldIdentifiers);
        $this->assertSame('LLL:helptext.emailAddress', $restoredEmail->helptext);
        $this->assertSame('email', $restoredEmail->autocomplete);
        $this->assertSame(['title' => 'LLL:helptext.title', 'from' => 'LLL:helptext.from'], $restoredSection->helptexts);
        $this->assertSame('organization-title', $restored->getContractField('position')?->autocomplete);
        $this->assertSame(['year', 'title'], $restoredSection->rowFields);
        $this->assertSame(['view', 'down', 'up', 'delete', 'edit'], $restoredSection->actions);
        $this->assertSame(['headline', 'profileEntries'], $restored->publicProfile->structure['right']);
    }

    /**
     * @param list<string> $flags
     */
    private function validation(string $identifier, string $fieldName, array $flags = [], int $characterLimit = 0): Validation
    {
        return new Validation(
            identifier: $identifier,
            fieldName: $fieldName,
            required: in_array('required', $flags, true),
            disabled: false,
            readOnly: false,
            validatorClassNames: [],
            tcaConfig: in_array('required', $flags, true) ? ['required' => true] : [],
            inputType: 'text',
            flags: $flags,
            characterLimit: $characterLimit,
        );
    }

    /**
     * @param list<string> $rowFields
     * @param list<string> $actions
     * @param array<string, string> $helptexts
     */
    private function documentSection(
        string $identifier,
        string $type,
        string $label = 'Label',
        string $fieldName = 'relation',
        bool $readOnly = false,
        array $rowFields = [],
        array $actions = [],
        array $helptexts = [],
    ): DocumentSection {
        return new DocumentSection(
            identifier: $identifier,
            label: $label,
            type: $type,
            fieldName: $fieldName,
            readOnly: $readOnly,
            validationSet: new ValidationSet($identifier, []),
            position: 0,
            rowFields: $rowFields,
            actions: $actions,
            helptexts: $helptexts,
        );
    }
}
