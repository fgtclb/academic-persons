<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Unit\Settings;

use FGTCLB\AcademicBase\Settings\SettingsFileLoader;
use FGTCLB\AcademicBase\Settings\ValidationNormalizer;
use FGTCLB\AcademicPersons\Settings\AcademicPersonsSettings;
use FGTCLB\AcademicPersons\Settings\AcademicPersonsSettingsFactory;
use FGTCLB\AcademicPersons\Settings\LegacySettingsMigrator;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\Package\PackageInterface;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Extbase\Validation\Validator\EmailAddressValidator;
use TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator;
use TYPO3\CMS\Extbase\Validation\Validator\UrlValidator;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * The factory owns the persons shape of the settings file: which top-level maps
 * exist, how their entries become sections and fields, and which entries are
 * dropped silently. The shipped file is the primary fixture, because it is what
 * every installation starts from and what an override has to restate.
 */
final class AcademicPersonsSettingsFactoryTest extends UnitTestCase
{
    #[Test]
    public function theShippedFileConsistsOfTheFourTopLevelMaps(): void
    {
        $this->assertSame(
            ['profile', 'special', 'contracts', 'documentSections'],
            array_keys($this->getShippedConfiguration()),
        );
    }

    /**
     * The cache entry is a var_export statement naming the classes of the graph. The
     * identifier was changed once, when the primitives moved to academic_base, and the
     * section graph keeps it: nothing was released in between.
     */
    #[Test]
    public function theSettingsAreCachedUnderTheIdentifierOfTheSharedPrimitives(): void
    {
        $cache = $this->createMock(PhpFrontend::class);
        $cache->expects($this->once())
            ->method('require')
            ->with('AcademicPersons_Settings_v3')
            ->willReturn(new AcademicPersonsSettings());

        $settings = $this->factory($cache)->get();

        $this->assertSame([], $settings->profileSections);
    }

    /**
     * A site package still shipping the pre-3.0 `validations` map - here the
     * override example of the 2.x manual, which unlocks the name fields by not
     * listing them - is mapped onto the section maps on a cache miss, before the
     * graph is built, and the legacy key does not survive into `raw`. This is the
     * wiring of the overlay; the mapping itself is covered by
     * LegacySettingsMigratorTest.
     */
    #[Test]
    public function aLegacyOverrideOfAnActivePackageIsOverlaidOnLoad(): void
    {
        $cache = $this->createMock(PhpFrontend::class);
        $cache->method('require')->willReturn(false);
        $packageManager = $this->createMock(PackageManager::class);
        $packageManager->method('getActivePackages')->willReturn([
            $this->package('academic_persons', __DIR__ . '/../../../'),
            $this->package(
                'test_legacy_settings',
                __DIR__ . '/../../Functional/Fixtures/Extensions/test_legacy_settings/',
            ),
        ]);

        $settings = $this->factory($cache, $packageManager)->get();

        $firstName = $settings->getProfileField('firstName');
        $this->assertNotNull($firstName);
        $this->assertFalse($firstName->validation->readOnly);
        $this->assertFalse($firstName->validation->disabled);
        $this->assertSame([], $firstName->validation->validatorClassNames);
        $website = $settings->getProfileField('website');
        $this->assertNotNull($website);
        $this->assertSame([NotEmptyValidator::class, UrlValidator::class], $website->validation->validatorClassNames);
        $this->assertArrayNotHasKey('validations', $settings->raw);
        $this->assertSame(['profile', 'special', 'contracts', 'documentSections'], array_keys($settings->raw));
    }

    #[Test]
    public function theShippedProfileDefinesTheOrderedPublicDetailLayout(): void
    {
        $settings = $this->normalize($this->getShippedConfiguration());

        $this->assertSame(
            [
                'left' => ['menuSections'],
                'right' => [
                    'headline',
                    'position',
                    'profileImage',
                    'contact',
                    'subline',
                    'profileEntries',
                    'links',
                    'menuSectionsDatas',
                ],
            ],
            $settings->publicProfile->structure,
        );
        $this->assertSame(
            ['title', 'firstName', 'middleName', 'lastName'],
            $settings->publicProfile->details['headline'],
        );
        $this->assertSame(
            ['website', 'publicationsLink'],
            $settings->publicProfile->details['links'],
        );
        $this->assertSame(
            [
                'researchProjects' => 'scientificResearch',
                'academicCareer' => 'vita',
                'membershipsCommitteeActivities' => 'memberships',
                'networkCooperation' => 'cooperation',
                'publications' => 'publications',
                'lectures' => 'lectures',
                'pressMedia' => 'pressMedia',
            ],
            $settings->publicProfile->details['menuSectionsDatas'],
        );
    }

    /**
     * The layout lists are trimmed, de-duplicated and cleared of anything that is
     * not a string, in configured order; a column or detail without a usable
     * identifier is dropped. A detail may be a list, a map or a label reference.
     */
    #[Test]
    public function publicProfileListsAndMapsAreNormalizedWithoutChangingOrder(): void
    {
        $settings = $this->normalize([
            'profile' => [
                'structure' => [
                    'left' => [' menuSections ', '', 'menuSections', 123, ' headline '],
                    'right' => 'profileEntries',
                    0 => ['ignoredColumn'],
                ],
                'details' => [
                    'headline' => [' title ', '', 'title', false, ' firstName '],
                    'position' => ['special' => ' datasFromContracts ', 'empty' => ' ', 0 => 'ignored'],
                    'subline' => 'LLL:EXT:site/Resources/Private/Language/locallang.xlf:profile.subline',
                    'menuSectionsDatas' => [
                        'researchProjects' => ' scientificResearch ',
                        'empty' => ' ',
                        0 => 'ignored',
                        'invalid' => false,
                    ],
                    'invalid' => false,
                    '' => ['ignoredDetail'],
                ],
            ],
        ]);

        $this->assertSame(['left' => ['menuSections', 'headline']], $settings->publicProfile->structure);
        $this->assertSame(
            [
                'headline' => ['title', 'firstName'],
                'position' => ['special' => 'datasFromContracts'],
                'subline' => 'LLL:EXT:site/Resources/Private/Language/locallang.xlf:profile.subline',
                'menuSectionsDatas' => ['researchProjects' => 'scientificResearch'],
            ],
            $settings->publicProfile->details,
        );
    }

    #[Test]
    public function theShippedFileIsLoadedIntoTheCompleteSectionGraph(): void
    {
        $settings = $this->normalize($this->getShippedConfiguration());

        $this->assertSame(['information', 'aboutme'], array_keys($settings->profileSections));
        $this->assertSame(
            [
                'gender',
                'title',
                'firstName',
                'middleName',
                'lastName',
                'website',
                'publicationsLink',
                'coreCompetences',
                'supervisedThesis',
                'supervisedDoctoralThesis',
                'teachingArea',
            ],
            array_keys($settings->getProfileSection('information')?->fields ?? []),
        );
        $this->assertSame(['miscellaneous'], array_keys($settings->getProfileSection('aboutme')?->fields ?? []));
        $this->assertSame(['title', 'image', 'skipSync'], array_keys($settings->specialFields));
        $this->assertSame(
            [
                'position',
                'organisationalUnit',
                'functionType',
                'validFrom',
                'validTo',
                'location',
                'room',
                'officeHours',
                'publish',
            ],
            array_keys($settings->contractFields),
        );
        $this->assertSame(
            ['physicalAddresses', 'emailAddresses', 'phoneNumbers'],
            array_keys($settings->contractContactSections),
        );
        $this->assertSame(
            [
                'contracts',
                'cooperation',
                'lectures',
                'memberships',
                'pressMedia',
                'publications',
                'scientificResearch',
                'vita',
            ],
            array_keys($settings->documentSections),
        );
        $this->assertSame($this->getShippedConfiguration(), $settings->raw);
    }

    /**
     * The seven document sections address the seven profile information types, and
     * their `year` validator addresses the integer column of that name. `from` and
     * `to` alias the start and end year and `description` the body text, so the
     * settings file speaks the editor's language. Cooperation is the one section
     * shipped without a `link` validator.
     */
    #[Test]
    public function theShippedDocumentSectionsAddressTheProfileInformationTypesAndYearProperties(): void
    {
        $settings = $this->normalize($this->getShippedConfiguration());

        $expectedTypes = [
            'cooperation' => ['cooperation', 'cooperation'],
            'lectures' => ['lecture', 'lectures'],
            'memberships' => ['membership', 'memberships'],
            'pressMedia' => ['press_media', 'press_media'],
            'publications' => ['publication', 'publications'],
            'scientificResearch' => ['scientific_research', 'scientific_research'],
            'vita' => ['curriculum_vitae', 'vita'],
        ];
        foreach ($expectedTypes as $identifier => [$type, $fieldName]) {
            $section = $settings->getDocumentSection($identifier);
            $this->assertNotNull($section, $identifier);
            $this->assertSame($type, $section->type, $identifier);
            $this->assertSame($fieldName, $section->fieldName, $identifier);
            $this->assertFalse($section->readOnly, $identifier);
            $this->assertSame(['view', 'down', 'up', 'delete', 'edit'], $section->actions, $identifier);
            $this->assertSame(
                $identifier === 'cooperation'
                    ? ['title', 'yearStart', 'yearEnd', 'year', 'bodytext']
                    : ['title', 'link', 'yearStart', 'yearEnd', 'year', 'bodytext'],
                array_keys($section->validationSet->validations),
                $identifier,
            );
            $year = $section->validationSet->get('year');
            $yearStart = $section->validationSet->get('yearStart');
            $bodytext = $section->validationSet->get('bodytext');
            $this->assertNotNull($year, $identifier);
            $this->assertNotNull($yearStart, $identifier);
            $this->assertNotNull($bodytext, $identifier);
            $this->assertSame('year', $year->fieldName, $identifier);
            $this->assertSame('year_start', $yearStart->fieldName, $identifier);
            $this->assertSame('year_end', $section->validationSet->get('yearEnd')?->fieldName, $identifier);
            $this->assertTrue($year->required, $identifier);
            $this->assertSame('number', $year->inputType, $identifier);
            $this->assertFalse($yearStart->required, $identifier);
            if ($identifier !== 'cooperation') {
                $this->assertSame([UrlValidator::class], $section->validationSet->get('link')?->validatorClassNames, $identifier);
            }
            $this->assertTrue($bodytext->isRichText(), $identifier);
            $this->assertSame(500, $bodytext->characterLimit, $identifier);
        }
        $this->assertSame(['from', 'to', 'title'], $settings->getDocumentSection('cooperation')?->rowFields);
        $this->assertSame(['year', 'from', 'to', 'title'], $settings->getDocumentSection('lectures')?->rowFields);
        $this->assertSame(['year', 'title'], $settings->getDocumentSection('publications')?->rowFields);
    }

    /**
     * Every help text and renderer setting of the file is on the graph, so a consumer
     * never has to read the raw array: the profile and contact fields carry theirs,
     * a document section carries a map keyed like its validators, and the image
     * carries its crop ratio. Values are trimmed, non-strings are dropped.
     */
    #[Test]
    public function helpTextsAndRendererSettingsReachTheGraph(): void
    {
        $settings = $this->normalize($this->getShippedConfiguration());

        $this->assertSame(
            'LLL:EXT:academic_persons/Resources/Private/Language/locallang.xlf:helptext.firstName',
            $settings->getProfileField('firstName')?->helptext,
        );
        $this->assertSame('', $settings->getProfileField('gender')?->helptext);
        $this->assertSame(
            'LLL:EXT:academic_persons/Resources/Private/Language/locallang.xlf:helptext.contractContact.street',
            $settings->getContractContactSection('physicalAddresses')?->getField('street')?->helptext,
        );
        $this->assertSame(
            'LLL:EXT:academic_persons/Resources/Private/Language/locallang.xlf:helptext.contracts.position',
            $settings->getContractField('position')?->helptext,
        );
        $this->assertSame(
            ['title', 'from', 'to', 'year', 'description'],
            array_keys($settings->getDocumentSection('publications')?->helptexts ?? []),
        );
        $this->assertSame(
            'LLL:EXT:academic_persons/Resources/Private/Language/locallang.xlf:helptext.documentSections.year',
            $settings->getDocumentSection('publications')?->helptexts['year'] ?? null,
        );
        $this->assertSame([], $settings->getDocumentSection('contracts')?->helptexts);
        $this->assertSame(['ratio' => '3x4'], $settings->getSpecialField('image')?->settings);
        $this->assertSame([], $settings->getSpecialField('skipSync')?->settings);

        $normalized = $this->normalize([
            'profile' => [
                'title' => ['section' => 'information', 'fieldType' => 'input', 'renderType' => 'text', 'helptext' => ' Some text '],
            ],
            'special' => [
                'image' => ['type' => 'special', 'renderType' => 'cropper', 'settings' => ['ratio' => ' 1x1 ', 'empty' => '', 0 => 'x', 'n' => 3]],
            ],
            'documentSections' => [
                'vita' => ['label' => 'Vita', 'type' => 'curriculum_vitae', 'fieldName' => 'vita', 'helptext' => ['title' => ' T ', 'x' => null]],
            ],
        ]);
        $this->assertSame('Some text', $normalized->getProfileField('title')?->helptext);
        $this->assertSame(['ratio' => '1x1'], $normalized->getSpecialField('image')?->settings);
        $this->assertSame(['title' => 'T'], $normalized->getDocumentSection('vita')?->helptexts);
    }

    /**
     * The shipped `contracts` document section is two lines - a type reference - and
     * takes the rest from the top-level `contracts` map: label, row fields, actions
     * and, for its validation, the contract fields.
     */
    #[Test]
    public function theContractsDocumentSectionIsCompletedFromTheContractsMap(): void
    {
        $settings = $this->normalize($this->getShippedConfiguration());

        $contracts = $settings->getDocumentSection('contracts');
        $this->assertNotNull($contracts);
        $this->assertTrue($contracts->isContractSection());
        $this->assertSame('contracts', $contracts->type);
        $this->assertSame('contracts', $contracts->fieldName);
        $this->assertStringStartsWith('LLL:EXT:academic_persons/', $contracts->label);
        $this->assertSame(['position'], $contracts->rowFields);
        $this->assertSame(['view', 'down', 'up', 'delete', 'edit'], $contracts->actions);
        $this->assertSame(array_keys($settings->contractFields), array_keys($contracts->validationSet->validations));
        $position = $contracts->validationSet->get('position');
        $validFrom = $contracts->validationSet->get('validFrom');
        $organisationalUnit = $settings->getContractField('organisationalUnit');
        $this->assertNotNull($position);
        $this->assertNotNull($validFrom);
        $this->assertNotNull($organisationalUnit);
        $this->assertSame($settings->getContractField('position')?->validation, $position);
        $this->assertSame([NotEmptyValidator::class], $position->validatorClassNames);
        $this->assertSame('date', $validFrom->inputType);
        $this->assertSame('valid_from', $validFrom->fieldName);
        $this->assertSame('organisationalUnits', $organisationalUnit->optionSource);
        $this->assertSame('select', $organisationalUnit->validation->inputType);
        $this->assertStringStartsWith('LLL:EXT:academic_persons/', $settings->getContractField('room')?->helptext ?? '');
    }

    /**
     * The contact sections carry the one case where the settings key, the property
     * and the column all differ: `emailAddress` addresses the `email` property and
     * column, and the three `<section>Type` keys address the `type` property of their
     * own record - unique keys for what the editor sees, without giving up the
     * property names the DTOs and the TCA use.
     */
    #[Test]
    public function theContactSectionsMapTheirKeysToTheRecordProperties(): void
    {
        $settings = $this->normalize($this->getShippedConfiguration());

        $email = $settings->getContractContactSection('emailAddresses');
        $this->assertNotNull($email);
        $emailAddress = $email->getField('emailAddress');
        $this->assertNotNull($emailAddress);
        $this->assertSame(['emailAddress', 'emailAddressType'], array_keys($email->fields));
        $this->assertSame(['email', 'type'], array_keys($email->validationSet->validations));
        $this->assertSame('email', $emailAddress->propertyName);
        $this->assertSame('email', $emailAddress->fieldName);
        $this->assertSame('email', $emailAddress->autocomplete);
        $this->assertSame(
            [NotEmptyValidator::class, EmailAddressValidator::class],
            $email->validationSet->get('email')?->validatorClassNames,
        );
        $this->assertSame('type', $email->getField('emailAddressType')?->propertyName);
        $this->assertSame('select', $email->validationSet->get('type')?->inputType);

        $phone = $settings->getContractContactSection('phoneNumbers');
        $this->assertNotNull($phone);
        $phoneNumber = $phone->validationSet->get('phoneNumber');
        $this->assertNotNull($phoneNumber);
        $this->assertSame('tel', $phoneNumber->inputType);
        $this->assertSame('phone_number', $phoneNumber->fieldName);

        $address = $settings->getContractContactSection('physicalAddresses');
        $this->assertNotNull($address);
        $country = $address->validationSet->get('country');
        $this->assertNotNull($country);
        $this->assertSame(
            ['street', 'streetNumber', 'additional', 'zip', 'city', 'state', 'country', 'physicalAddressType'],
            array_keys($address->fields),
        );
        $this->assertSame('street_number', $address->validationSet->get('streetNumber')?->fieldName);
        $this->assertSame('select', $country->inputType);
        $this->assertTrue($country->required);
        $this->assertFalse($address->validationSet->get('type')?->required);
    }

    /**
     * The three name fields are `readonly` and `disabled`, which is what keeps them
     * for the synchronisation from the frontend user. The special `skipSync` is the
     * one special field addressing a profile column directly, so it joins the
     * profile update set; the composed title and the image do not.
     */
    #[Test]
    public function theShippedProfileLocksTheNameFieldsAndAddsSkipSyncToTheUpdateSet(): void
    {
        $settings = $this->normalize($this->getShippedConfiguration());

        foreach (['firstName', 'middleName', 'lastName'] as $identifier) {
            $validation = $settings->getProfileField($identifier)?->validation;
            $this->assertNotNull($validation, $identifier);
            $this->assertTrue($validation->readOnly, $identifier);
            $this->assertTrue($validation->disabled, $identifier);
            $this->assertFalse($validation->required, $identifier);
            $this->assertSame(['readonly', 'disabled'], $validation->flags, $identifier);
        }
        $gender = $settings->getProfileField('gender');
        $website = $settings->getProfileField('website');
        $teachingArea = $settings->getProfileField('teachingArea');
        $title = $settings->getSpecialField('title');
        $this->assertNotNull($gender);
        $this->assertNotNull($website);
        $this->assertNotNull($teachingArea);
        $this->assertNotNull($title);
        $this->assertTrue($gender->validation->required);
        $this->assertSame('select', $gender->validation->inputType);
        $this->assertSame('url', $website->validation->inputType);
        $this->assertSame([UrlValidator::class], $website->validation->validatorClassNames);
        $this->assertSame('textarea', $teachingArea->validation->inputType);
        $this->assertTrue($teachingArea->validation->isRichText());
        $this->assertSame(0, $teachingArea->validation->characterLimit);
        $this->assertSame(1000, $settings->getProfileField('miscellaneous')?->validation->characterLimit);

        $this->assertTrue($settings->getSpecialField('skipSync')?->hasDirectProfileProperty());
        $this->assertFalse($title->hasDirectProfileProperty());
        $this->assertFalse($settings->getSpecialField('image')?->hasDirectProfileProperty());
        $this->assertSame(['title', 'firstName', 'middleName', 'lastName'], $title->fieldIdentifiers);
        $this->assertSame(
            [
                'gender',
                'title',
                'firstName',
                'middleName',
                'lastName',
                'website',
                'publicationsLink',
                'coreCompetences',
                'supervisedThesis',
                'supervisedDoctoralThesis',
                'teachingArea',
                'miscellaneous',
                'skipSync',
            ],
            array_keys($settings->getProfileUpdateValidationSet()->validations),
        );
        $this->assertSame('skip_sync', $settings->getProfileUpdateValidationSet()->get('skipSync')?->fieldName);
    }

    /**
     * A field is grouped by its `section`; the sections appear in the order their
     * first field appears, and each field's position counts within its section. An
     * entry missing what a field needs is dropped, not reported.
     */
    #[Test]
    public function profileFieldsAreGroupedIntoSectionsInFileOrder(): void
    {
        $settings = $this->normalize([
            'profile' => [
                'structure' => ['left' => []],
                'details' => [],
                'miscellaneous' => ['section' => 'aboutme', 'fieldType' => 'textarea', 'renderType' => 'ckeditor'],
                'gender' => ['section' => 'information', 'fieldType' => 'select', 'renderType' => 'select'],
                'title' => ['section' => 'information', 'fieldType' => 'input', 'renderType' => 'text'],
                'withoutSection' => ['fieldType' => 'input', 'renderType' => 'text'],
                'withoutRenderType' => ['section' => 'information', 'fieldType' => 'input'],
                'notAMap' => 'text',
            ],
        ]);

        $information = $settings->getProfileSection('information');
        $this->assertNotNull($information);
        $this->assertSame(['aboutme', 'information'], array_keys($settings->profileSections));
        $this->assertSame(0, $settings->getProfileSection('aboutme')?->position);
        $this->assertSame(1, $information->position);
        $this->assertSame(['gender', 'title'], array_keys($information->fields));
        $this->assertSame(0, $settings->getProfileField('gender')?->position);
        $this->assertSame(1, $settings->getProfileField('title')?->position);
        $this->assertNull($settings->getProfileField('withoutSection'));
        $this->assertNull($settings->getProfileField('withoutRenderType'));
        $this->assertNull($settings->getProfileField('notAMap'));
    }

    /**
     * `propertyName` and `fieldName` are optional and derived from the key: an entry
     * naming them addresses a property and column that differ from its key.
     */
    #[Test]
    public function aProfileFieldMayAddressAnotherPropertyAndColumn(): void
    {
        $settings = $this->normalize([
            'profile' => [
                'profileWebsite' => [
                    'section' => 'information',
                    'propertyName' => 'website',
                    'fieldType' => 'input',
                    'renderType' => 'text',
                    'validators' => ['required'],
                ],
                'publicationsLink' => [
                    'section' => 'information',
                    'fieldName' => 'publications_url',
                    'fieldType' => 'input',
                    'renderType' => 'text',
                ],
            ],
        ]);

        $website = $settings->getProfileField('profileWebsite');
        $this->assertNotNull($website);
        $this->assertSame('website', $website->propertyName);
        $this->assertSame('website', $website->fieldName);
        $this->assertSame('website', $website->validation->identifier);
        $this->assertSame($website, $settings->getProfileField('website'));
        $this->assertSame(['website', 'publicationsLink'], array_keys($settings->getProfileValidationSet()->validations));
        $this->assertSame('publications_url', $settings->getProfileField('publicationsLink')?->validation->fieldName);
    }

    /**
     * A document field takes the short list or the expanded map, and the map's
     * `editor` block: `ckeditor` implies the `html` flag and carries the limit,
     * `textarea` implies `textarea` and carries none. A limit that is not a
     * non-negative integer is no limit. None of it reaches the TCA fragment.
     */
    #[Test]
    public function documentValidatorsAcceptTheShortListAndTheExpandedMap(): void
    {
        $settings = $this->normalize([
            'documentSections' => [
                'publications' => [
                    'label' => 'Publications',
                    'type' => 'publication',
                    'fieldName' => 'publications',
                    'validators' => [
                        'title' => ['required'],
                        'description' => [
                            'editor' => [
                                'type' => 'ckeditor',
                                'limit' => 100,
                            ],
                        ],
                        'from' => [
                            'validators' => ['number'],
                            'required' => true,
                        ],
                        'to' => [
                            'number' => true,
                            'editor' => [
                                'type' => 'textarea',
                                'limit' => 60,
                            ],
                        ],
                        'link' => [
                            'url' => true,
                            'editor' => [
                                'type' => 'ckeditor',
                                'limit' => 'invalid',
                            ],
                        ],
                        'notAMap' => 'required',
                    ],
                ],
            ],
        ]);

        $set = $settings->getDocumentValidationSet('publications');
        $bodytext = $set->get('bodytext');
        $yearStart = $set->get('yearStart');
        $yearEnd = $set->get('yearEnd');
        $link = $set->get('link');
        $this->assertNotNull($bodytext);
        $this->assertNotNull($yearStart);
        $this->assertNotNull($yearEnd);
        $this->assertNotNull($link);
        $this->assertSame(['title', 'bodytext', 'yearStart', 'yearEnd', 'link'], array_keys($set->validations));
        $this->assertSame([NotEmptyValidator::class], $set->get('title')?->validatorClassNames);
        $this->assertSame(['html'], $bodytext->flags);
        $this->assertTrue($bodytext->isRichText());
        $this->assertSame(100, $bodytext->characterLimit);
        $this->assertArrayNotHasKey('max', $bodytext->tcaConfig);
        $this->assertSame(['number', 'required'], $yearStart->flags);
        $this->assertTrue($yearStart->required);
        $this->assertSame('year_start', $yearStart->fieldName);
        $this->assertSame(['number', 'textarea'], $yearEnd->flags);
        $this->assertSame(0, $yearEnd->characterLimit);
        $this->assertSame(['url', 'html'], $link->flags);
        $this->assertSame(0, $link->characterLimit);
    }

    /**
     * The profile counterpart: `characterLimit` counts only on a `ckeditor` control.
     */
    #[Test]
    public function aProfileCharacterLimitCountsOnlyForARichTextControl(): void
    {
        $settings = $this->normalize([
            'profile' => [
                'miscellaneous' => [
                    'section' => 'aboutme',
                    'fieldType' => 'textarea',
                    'renderType' => 'ckeditor',
                    'characterLimit' => 500,
                    'validators' => ['html'],
                ],
                'firstName' => [
                    'section' => 'information',
                    'fieldType' => 'input',
                    'renderType' => 'text',
                    'characterLimit' => 60,
                ],
                'teachingArea' => [
                    'section' => 'information',
                    'fieldType' => 'textarea',
                    'renderType' => 'ckeditor',
                    'characterLimit' => 'invalid',
                ],
            ],
        ]);

        $miscellaneous = $settings->getProfileField('miscellaneous')?->validation;
        $this->assertNotNull($miscellaneous);
        $this->assertSame(500, $miscellaneous->characterLimit);
        $this->assertTrue($miscellaneous->isRichText());
        $this->assertArrayNotHasKey('max', $miscellaneous->tcaConfig);
        $this->assertSame(0, $settings->getProfileField('firstName')?->validation->characterLimit);
        $this->assertSame(0, $settings->getProfileField('teachingArea')?->validation->characterLimit);
    }

    /**
     * Row fields and actions are validated against the vocabulary of the section
     * kind, lower-cased, de-duplicated and kept in order; a `readonly` section keeps
     * the lists and answers the capability questions from the flag.
     */
    #[Test]
    public function documentRowFieldsAndActionsAreFilteredByTheSectionKind(): void
    {
        $settings = $this->normalize([
            'documentSections' => [
                'contracts' => [
                    'label' => 'Contracts',
                    'type' => 'contracts',
                    'fieldName' => 'contracts',
                    'rowFields' => ['position', 'title', 'from', 'position'],
                    'actions' => ['View', 'edit', 'rename'],
                ],
                'vita' => [
                    'label' => 'Vita',
                    'type' => 'curriculum_vitae',
                    'fieldName' => 'vita',
                    'readonly' => true,
                    'rowFields' => [' year ', 'position', 'title', 5],
                    'actions' => ['view', 'down', 'up', 'delete', 'edit'],
                ],
            ],
        ]);

        $contracts = $settings->getDocumentSection('contracts');
        $this->assertNotNull($contracts);
        $this->assertSame(['position', 'from'], $contracts->rowFields);
        $this->assertSame(['view', 'edit'], $contracts->actions);
        $this->assertSame(['view', 'edit'], $contracts->getAllowedActions());
        $this->assertFalse($contracts->allowsDragSorting());

        $vita = $settings->getDocumentSection('vita');
        $this->assertNotNull($vita);
        $this->assertSame(['year', 'title'], $vita->rowFields);
        $this->assertTrue($vita->readOnly);
        $this->assertSame(['view'], $vita->getAllowedActions());
        $this->assertFalse($vita->allowsCreate());
    }

    /**
     * A settings file without one of the maps is an installation that overrides
     * another map only - the graph is empty there, never wrong.
     */
    #[Test]
    public function missingOrMalformedMapsYieldEmptyParts(): void
    {
        $settings = $this->normalize([
            'profile' => 'not a map',
            'special' => ['image' => 'not a map', 'other' => ['type' => 'regular', 'renderType' => 'text']],
            'contracts' => ['fields' => 'not a map', 'contactSections' => ['emailAddresses' => ['fields' => []]]],
            'documentSections' => ['lectures' => ['type' => 'lecture']],
        ]);

        $this->assertSame([], $settings->profileSections);
        $this->assertSame([], $settings->specialFields);
        $this->assertSame([], $settings->contractFields);
        $this->assertSame([], $settings->contractContactSections);
        $this->assertSame([], $settings->documentSections);
        $this->assertSame([], $settings->publicProfile->structure);
        $this->assertSame([], $this->normalize([])->documentSections);
    }

    /**
     * What the loader writes to the core cache is `return <var_export>;`, so the
     * complete graph built from the shipped file has to come back from that
     * statement equal - every value object's `__set_state()` is on this path.
     */
    #[Test]
    public function theShippedGraphSurvivesThePhpCacheRoundTrip(): void
    {
        $settings = $this->normalize($this->getShippedConfiguration());

        $restored = eval('return ' . var_export($settings, true) . ';');

        $this->assertInstanceOf(AcademicPersonsSettings::class, $restored);
        $this->assertEquals($settings, $restored);
        $this->assertNotSame($settings, $restored);
    }

    /**
     * @return array<string, mixed>
     */
    private function getShippedConfiguration(): array
    {
        $configuration = Yaml::parseFile(__DIR__ . '/../../../Configuration/AcademicPersons/Settings.yaml');
        $this->assertIsArray($configuration);
        return $configuration;
    }

    /**
     * @param array<string, mixed> $configuration
     */
    private function normalize(array $configuration): AcademicPersonsSettings
    {
        return $this->factory($this->createMock(PhpFrontend::class))->normalize($configuration);
    }

    private function factory(PhpFrontend $cache, ?PackageManager $packageManager = null): AcademicPersonsSettingsFactory
    {
        return new AcademicPersonsSettingsFactory(
            new SettingsFileLoader($cache, $packageManager ?? $this->createMock(PackageManager::class)),
            new ValidationNormalizer(),
            new LegacySettingsMigrator(),
        );
    }

    private function package(string $packageKey, string $packagePath): PackageInterface
    {
        $package = $this->createMock(PackageInterface::class);
        $package->method('getPackageKey')->willReturn($packageKey);
        $package->method('getPackagePath')->willReturn($packagePath);
        return $package;
    }
}
