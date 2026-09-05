<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Unit\Settings;

use FGTCLB\AcademicBase\Settings\Validation;
use FGTCLB\AcademicBase\Settings\ValidationSet;
use FGTCLB\AcademicPersons\Settings\AcademicPersonsSettings;
use FGTCLB\AcademicPersons\Settings\ContractContactField;
use FGTCLB\AcademicPersons\Settings\ContractContactSection;
use FGTCLB\AcademicPersons\Settings\ContractField;
use FGTCLB\AcademicPersons\Settings\DocumentSection;
use FGTCLB\AcademicPersons\Settings\ProfileField;
use FGTCLB\AcademicPersons\Settings\ProfileSection;
use FGTCLB\AcademicPersons\Settings\PublicProfileSettings;
use FGTCLB\AcademicPersons\Settings\SpecialField;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * The settings root is what `AcademicPersonsSettingsFactory` hands to the TCA files
 * and to the frontend validation. Two things have to hold: the lookups fail softly,
 * because TCA files and forms ask for identifiers an installation need not have
 * configured, and validation never falls back from one section to another - a
 * contact section is not the profile, a document section is not its neighbour.
 */
final class AcademicPersonsSettingsTest extends UnitTestCase
{
    #[Test]
    public function profileSectionsAndFieldsAreResolvedByTheirIdentifiersOrProperties(): void
    {
        $field = $this->profileField('profileWebsite', 'website', 'website');
        $section = $this->profileSection('information', [$field]);
        $subject = new AcademicPersonsSettings(profileSections: ['information' => $section]);

        $this->assertSame($section, $subject->getProfileSection('information'));
        $this->assertNull($subject->getProfileSection('aboutme'));
        $this->assertSame($field, $subject->getProfileField('profileWebsite'));
        $this->assertSame($field, $subject->getProfileField('website'));
        $this->assertNull($subject->getProfileField('unknown'));
    }

    #[Test]
    public function anEmptyRootAnswersEveryLookupSoftly(): void
    {
        $subject = new AcademicPersonsSettings();

        $this->assertSame([], $subject->publicProfile->structure);
        $this->assertSame([], $subject->publicProfile->details);
        $this->assertNull($subject->getProfileSection('information'));
        $this->assertNull($subject->getProfileField('firstName'));
        $this->assertNull($subject->getSpecialField('image'));
        $this->assertNull($subject->getContractField('position'));
        $this->assertNull($subject->getContractContactSection('emailAddresses'));
        $this->assertNull($subject->getContractContactField('email'));
        $this->assertNull($subject->getDocumentSection('vita'));
        $this->assertNull($subject->getDocumentSectionByType('curriculum_vitae'));
        $this->assertSame([], $subject->getProfileValidationSet()->validations);
        $this->assertSame([], $subject->getProfileUpdateValidationSet()->validations);
        $this->assertSame([], $subject->getDocumentValidationTcaTypesConfig());
    }

    /**
     * The empty fallback sets carry the *requested* identifier, not an empty one -
     * anything logging or comparing the returned set would otherwise attribute it to
     * the wrong record type.
     */
    #[Test]
    public function anUnknownSectionFallsBackToAnEmptySetOfTheSameIdentifier(): void
    {
        $subject = new AcademicPersonsSettings();

        $this->assertSame('aboutme', $subject->getProfileValidationSet('aboutme')->identifier);
        $this->assertSame([], $subject->getProfileValidationSet('aboutme')->validations);
        $this->assertSame('aboutme', $subject->getProfileValidationSetForFields(['x'], 'aboutme')->identifier);
        $this->assertSame('emailAddresses', $subject->getContractContactValidationSet('emailAddresses')->identifier);
        $this->assertSame('emailAddresses', $subject->getContractContactValidationSetForFields(['email'], 'emailAddresses')->identifier);
        $this->assertSame('vita', $subject->getDocumentValidationSet('vita')->identifier);
        $this->assertNull($subject->getDocumentSectionByType('curriculum_vitae'));
        $this->assertNull($subject->getDocumentValidationSet('vita')->get('title'));
    }

    #[Test]
    public function publicProfileIsTheConfiguredObjectOrAnEmptyDefault(): void
    {
        $publicProfile = new PublicProfileSettings(
            structure: ['left' => ['menuSections'], 'right' => ['headline']],
            details: ['headline' => ['firstName', 'lastName']],
        );

        $this->assertSame($publicProfile, (new AcademicPersonsSettings(publicProfile: $publicProfile))->publicProfile);
        $this->assertSame(['menuSections'], $publicProfile->structure['left']);
    }

    /**
     * Per section the set is the section's own object; aggregated, the sections are
     * folded in order and a later section replaces an earlier one's entry for the
     * same property.
     */
    #[Test]
    public function profileValidationCanBeReadPerSectionOrAggregated(): void
    {
        $gender = $this->profileField('gender', 'gender', 'gender');
        $miscellaneous = $this->profileField('miscellaneous', 'miscellaneous', 'miscellaneous', 'aboutme');
        $miscellaneousAgain = $this->profileField('miscellaneous', 'miscellaneous', 'miscellaneous', 'more');
        $information = $this->profileSection('information', [$gender]);
        $about = $this->profileSection('aboutme', [$miscellaneous]);
        $more = $this->profileSection('more', [$miscellaneousAgain]);
        $subject = new AcademicPersonsSettings(
            profileSections: ['information' => $information, 'aboutme' => $about, 'more' => $more],
        );

        $this->assertSame($information->validationSet, $subject->getProfileValidationSet('information'));
        $this->assertSame('profile', $subject->getProfileValidationSet()->identifier);
        $this->assertSame(['gender', 'miscellaneous'], array_keys($subject->getProfileValidationSet()->validations));
        $this->assertSame($miscellaneousAgain->validation, $subject->getProfileValidationSet()->get('miscellaneous'));
    }

    /**
     * A subset is keyed by property name, resolves the requested identifiers by key
     * or by property, keeps the requested order and drops what the section does not
     * have - the same section only.
     */
    #[Test]
    public function profileValidationSubsetMapsConfiguredIdentifiersToProperties(): void
    {
        $website = $this->profileField('profileWebsite', 'website', 'website');
        $title = $this->profileField('title', 'title', 'title');
        $subject = new AcademicPersonsSettings(
            profileSections: [
                'information' => $this->profileSection('information', [$website, $title]),
                'aboutme' => $this->profileSection('aboutme', [$this->profileField('miscellaneous', 'miscellaneous', 'miscellaneous', 'aboutme')]),
            ],
        );

        $subset = $subject->getProfileValidationSetForFields(['title', 'website', 'miscellaneous', 'unknown'], 'information');

        $this->assertSame('information', $subset->identifier);
        $this->assertSame(['title', 'website'], array_keys($subset->validations));
        $this->assertSame($website->validation, $subset->get('website'));
        $this->assertSame($website->validation, $subject->getProfileValidationSetForFields(['profileWebsite'], 'information')->get('website'));
        $this->assertSame([], $subject->getProfileValidationSetForFields(['website'], 'aboutme')->validations);
    }

    #[Test]
    public function contractContactsNeverFallBackToProfileFieldsOrAnotherContactSection(): void
    {
        $profileStreet = $this->profileField('street', 'street', 'street');
        $contractStreet = $this->contractContactField('street', 'physicalAddresses', 'street', 'street');
        $subject = new AcademicPersonsSettings(
            profileSections: ['information' => $this->profileSection('information', [$profileStreet])],
            contractContactSections: [
                'physicalAddresses' => $this->contractContactSection('physicalAddresses', [$contractStreet]),
            ],
        );

        $this->assertSame($profileStreet, $subject->getProfileField('street'));
        $this->assertSame($contractStreet, $subject->getContractContactField('street'));
        $this->assertSame(['street'], array_keys($subject->getContractContactValidationSet('physicalAddresses')->validations));
        $this->assertSame([], $subject->getContractContactValidationSet('emailAddresses')->validations);
        $this->assertSame([], $subject->getContractContactValidationSetForFields(['street'], 'emailAddresses')->validations);
        $this->assertSame(['street'], array_keys($subject->getContractContactValidationSetForFields(['street', 'city'], 'physicalAddresses')->validations));
    }

    /**
     * The three contact sections all carry a `type` property under a section specific
     * key. Resolved through the section the right one comes back; resolved through
     * the root by property name, the first section wins - which is why the root
     * lookup documents that the section lookup is the one to use for `type`.
     */
    #[Test]
    public function contactFieldsAreResolvedByKeyOrPropertyAcrossSections(): void
    {
        $emailType = $this->contractContactField('emailAddressType', 'emailAddresses', 'type', 'type');
        $phoneType = $this->contractContactField('phoneNumberType', 'phoneNumbers', 'type', 'type');
        $subject = new AcademicPersonsSettings(
            contractContactSections: [
                'emailAddresses' => $this->contractContactSection('emailAddresses', [$emailType]),
                'phoneNumbers' => $this->contractContactSection('phoneNumbers', [$phoneType]),
            ],
        );

        $this->assertSame($phoneType, $subject->getContractContactField('phoneNumberType'));
        $this->assertSame($emailType, $subject->getContractContactField('type'));
        $this->assertSame($phoneType, $subject->getContractContactSection('phoneNumbers')?->getField('phoneNumberType'));
        $this->assertSame($phoneType->validation, $subject->getContractContactValidationSetForFields(['phoneNumberType'], 'phoneNumbers')->get('type'));
    }

    #[Test]
    public function contractFieldsAreResolvedByKeyOrProperty(): void
    {
        $position = new ContractField('jobPosition', 'position', 'position', 'input', 'text', '', '', $this->validation('position', 'position'), 0);
        $subject = new AcademicPersonsSettings(contractFields: ['jobPosition' => $position]);

        $this->assertSame($position, $subject->getContractField('jobPosition'));
        $this->assertSame($position, $subject->getContractField('position'));
        $this->assertNull($subject->getContractField('room'));
    }

    /**
     * The update set is what the profile TCA merges and what a profile form is
     * validated against: every profile section plus the special fields addressing a
     * profile column - `skipSync` - and not the composed ones.
     */
    #[Test]
    public function profileUpdateValidationAddsOnlyDirectSpecialProperties(): void
    {
        $profileField = $this->profileField('gender', 'gender', 'gender', 'information', ['required' => true]);
        $title = new SpecialField('title', 'special', '', 'title', ['firstName', 'lastName'], $this->validation('title', 'title'), 0);
        $skipSync = new SpecialField('skipSync', 'special', 'check', 'checkbox', [], $this->validation('skipSync', 'skip_sync', ['readOnly' => false]), 1);
        $subject = new AcademicPersonsSettings(
            profileSections: ['information' => $this->profileSection('information', [$profileField])],
            specialFields: ['title' => $title, 'skipSync' => $skipSync],
        );

        $updateSet = $subject->getProfileUpdateValidationSet();

        $this->assertSame('profileUpdate', $updateSet->identifier);
        $this->assertSame(['gender', 'skipSync'], array_keys($updateSet->validations));
        $this->assertSame($skipSync->validation, $updateSet->get('skipSync'));
        $this->assertSame(['gender'], array_keys($subject->getProfileValidationSet()->validations));
    }

    #[Test]
    public function documentSectionsAreResolvedByIdentifierAndRecordType(): void
    {
        $section = $this->documentSection('publications', 'publication');
        $subject = new AcademicPersonsSettings(documentSections: ['publications' => $section]);

        $this->assertSame($section, $subject->getDocumentSection('publications'));
        $this->assertSame($section, $subject->getDocumentSectionByType('publication'));
        $this->assertNull($subject->getDocumentSection('publication'));
        $this->assertNull($subject->getDocumentSectionByType('publications'));
    }

    #[Test]
    public function documentValidationNeverFallsBackToAnotherSection(): void
    {
        $publication = $this->documentSection('publications', 'publication');
        $subject = new AcademicPersonsSettings(documentSections: ['publications' => $publication]);

        $this->assertSame($publication->validationSet, $subject->getDocumentValidationSet('publications'));
        $this->assertSame($publication->validationSet, $subject->getDocumentSectionByType('publication')?->validationSet);
        $this->assertSame([], $subject->getDocumentValidationSet('lectures')->validations);
        $this->assertNull($subject->getDocumentSectionByType('lecture'));
    }

    /**
     * The profile information table is one table with a `type` column, so a section's
     * fragment goes into `types.<type>.columnsOverrides` and never onto the column
     * itself - `required` for publications must not make the title of a lecture
     * required. Validations without a TCA fragment contribute nothing, and the
     * contracts section has a table of its own and is left out.
     */
    #[Test]
    public function documentTcaConfigurationStaysAttachedToItsRecordType(): void
    {
        $publication = $this->documentSection('publications', 'publication', ['required' => true]);
        $lecture = $this->documentSection('lectures', 'lecture', ['readOnly' => true]);
        $vita = $this->documentSection('vita', 'curriculum_vitae');
        $contracts = $this->documentSection('contracts', 'contracts', ['required' => true]);
        $subject = new AcademicPersonsSettings(
            documentSections: [
                'contracts' => $contracts,
                'publications' => $publication,
                'lectures' => $lecture,
                'vita' => $vita,
            ],
        );

        $this->assertSame(
            [
                'types' => [
                    'publication' => ['columnsOverrides' => ['title' => ['config' => ['required' => true]]]],
                    'lecture' => ['columnsOverrides' => ['title' => ['config' => ['readOnly' => true]]]],
                ],
            ],
            $subject->getDocumentValidationTcaTypesConfig(),
        );
    }

    /**
     * The column name of the fragment is the validation's `fieldName`, not the
     * property the set is keyed by - `yearStart` addresses `year_start`.
     */
    #[Test]
    public function documentTcaConfigurationUsesTheDatabaseColumnNames(): void
    {
        $yearStart = $this->validation('yearStart', 'year_start', ['required' => true]);
        $section = new DocumentSection(
            identifier: 'cooperation',
            label: 'Cooperation',
            type: 'cooperation',
            fieldName: 'cooperation',
            readOnly: false,
            validationSet: new ValidationSet('cooperation', ['yearStart' => $yearStart]),
            position: 0,
        );
        $subject = new AcademicPersonsSettings(documentSections: ['cooperation' => $section]);

        $this->assertSame(
            ['types' => ['cooperation' => ['columnsOverrides' => ['year_start' => ['config' => ['required' => true]]]]]],
            $subject->getDocumentValidationTcaTypesConfig(),
        );
    }

    private function contractContactField(
        string $identifier,
        string $section,
        string $propertyName,
        string $fieldName,
    ): ContractContactField {
        return new ContractContactField(
            identifier: $identifier,
            section: $section,
            propertyName: $propertyName,
            fieldName: $fieldName,
            fieldType: 'input',
            renderType: 'text',
            validation: $this->validation($propertyName, $fieldName),
            position: 0,
        );
    }

    /**
     * @param list<ContractContactField> $fields
     */
    private function contractContactSection(string $identifier, array $fields): ContractContactSection
    {
        $indexedFields = [];
        $validations = [];
        foreach ($fields as $field) {
            $indexedFields[$field->identifier] = $field;
            $validations[$field->propertyName] = $field->validation;
        }
        return new ContractContactSection(
            identifier: $identifier,
            fields: $indexedFields,
            validationSet: new ValidationSet(identifier: $identifier, validations: $validations),
            position: 0,
        );
    }

    /**
     * @param array<string, mixed> $tcaConfig
     */
    private function profileField(
        string $identifier,
        string $propertyName,
        string $fieldName,
        string $section = 'information',
        array $tcaConfig = [],
    ): ProfileField {
        return new ProfileField(
            identifier: $identifier,
            section: $section,
            propertyName: $propertyName,
            fieldName: $fieldName,
            fieldType: 'input',
            renderType: 'text',
            validation: $this->validation($propertyName, $fieldName, $tcaConfig),
            position: 0,
        );
    }

    /**
     * @param list<ProfileField> $fields
     */
    private function profileSection(string $identifier, array $fields): ProfileSection
    {
        $indexedFields = [];
        $validations = [];
        foreach ($fields as $field) {
            $indexedFields[$field->identifier] = $field;
            $validations[$field->propertyName] = $field->validation;
        }
        return new ProfileSection(
            identifier: $identifier,
            fields: $indexedFields,
            validationSet: new ValidationSet(identifier: $identifier, validations: $validations),
            position: 0,
        );
    }

    /**
     * @param array<string, mixed> $tcaConfig
     */
    private function documentSection(string $identifier, string $type, array $tcaConfig = []): DocumentSection
    {
        return new DocumentSection(
            identifier: $identifier,
            label: ucfirst($identifier),
            type: $type,
            fieldName: $identifier,
            readOnly: false,
            validationSet: new ValidationSet($identifier, ['title' => $this->validation('title', 'title', $tcaConfig)]),
            position: 0,
        );
    }

    /**
     * @param array<string, mixed> $tcaConfig
     */
    private function validation(string $identifier, string $fieldName, array $tcaConfig = []): Validation
    {
        return new Validation(
            identifier: $identifier,
            fieldName: $fieldName,
            required: false,
            disabled: false,
            readOnly: false,
            validatorClassNames: [],
            tcaConfig: $tcaConfig,
        );
    }
}
