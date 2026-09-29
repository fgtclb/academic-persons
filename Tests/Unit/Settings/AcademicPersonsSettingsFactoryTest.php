<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Unit\Settings;

use FGTCLB\AcademicBase\Settings\SettingsFileLoader;
use FGTCLB\AcademicBase\Settings\ValidationNormalizer;
use FGTCLB\AcademicPersons\Settings\AcademicPersonsSettings;
use FGTCLB\AcademicPersons\Settings\AcademicPersonsSettingsFactory;
use FGTCLB\AcademicPersons\Settings\FrontendUserSyncEntry;
use FGTCLB\AcademicPersons\Settings\FrontendUserSyncRelation;
use FGTCLB\AcademicPersons\Settings\FrontendUserSyncSettings;
use FGTCLB\AcademicPersons\Settings\LegacySettingsMigrator;
use FGTCLB\AcademicPersons\Settings\ManagedFieldsSettings;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;
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
 * every installation starts from and what an override is merged onto.
 */
final class AcademicPersonsSettingsFactoryTest extends UnitTestCase
{
    #[Test]
    public function theShippedFileConsistsOfTheSixTopLevelMaps(): void
    {
        $this->assertSame(
            ['profile', 'special', 'contracts', 'documentSections', 'frontendUserSync', 'managedFields'],
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
        $this->assertSame(['profile', 'special', 'contracts', 'documentSections', 'frontendUserSync', 'managedFields'], array_keys($settings->raw));
    }

    /**
     * Two packages with the pre-3.0 `validations` map: the legacy keys are merged
     * like every other map, so the later package adds the title to the `profile`
     * set of the earlier one instead of replacing it. With a top-level fold only the
     * later map reached the overlay, and the website lost the `required` flag of
     * the earlier package.
     */
    #[Test]
    public function theLegacyMapsOfTwoPackagesAreMergedBeforeTheOverlay(): void
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
            $this->package(
                'test_second_legacy_settings',
                __DIR__ . '/../Fixtures/Packages/second_legacy_settings/',
            ),
        ]);

        $settings = $this->factory($cache, $packageManager)->get();

        $website = $settings->getProfileField('website');
        $this->assertNotNull($website);
        $this->assertSame([NotEmptyValidator::class, UrlValidator::class], $website->validation->validatorClassNames);
        $title = $settings->getProfileField('title');
        $this->assertNotNull($title);
        $this->assertSame([NotEmptyValidator::class], $title->validation->validatorClassNames);
        $firstName = $settings->getProfileField('firstName');
        $this->assertNotNull($firstName);
        $this->assertFalse($firstName->validation->readOnly, 'Neither package lists the name fields');
        $this->assertArrayNotHasKey('validations', $settings->raw);
    }

    /**
     * The override an installation writes today: a site package that names the three
     * name fields and nothing else. The recursive merge of the loader applies the
     * cleared flag lists to those fields and leaves the rest of the shipped `profile`
     * map - the public layout, the other fields, their flags - in place. With the
     * top-level merge this package replaced the whole map, and the profile consisted
     * of three fields and no layout.
     */
    #[Test]
    public function aPartialOverrideOfAnActivePackageChangesOnlyTheFieldsItNames(): void
    {
        $cache = $this->createMock(PhpFrontend::class);
        $cache->method('require')->willReturn(false);
        $packageManager = $this->createMock(PackageManager::class);
        $packageManager->method('getActivePackages')->willReturn([
            $this->package('academic_persons', __DIR__ . '/../../../'),
            $this->package(
                'test_partial_settings',
                __DIR__ . '/../Fixtures/Packages/partial_settings/',
            ),
        ]);

        $settings = $this->factory($cache, $packageManager)->get();

        $firstName = $settings->getProfileField('firstName');
        $this->assertNotNull($firstName);
        $this->assertFalse($firstName->validation->readOnly);
        $this->assertFalse($firstName->validation->disabled);
        $this->assertSame('information', $firstName->section);
        $this->assertSame('text', $firstName->renderType);
        $this->assertNotSame('', $firstName->helptext);
        $gender = $settings->getProfileField('gender');
        $this->assertNotNull($gender);
        $this->assertSame([NotEmptyValidator::class], $gender->validation->validatorClassNames);
        $website = $settings->getProfileField('website');
        $this->assertNotNull($website);
        $this->assertSame([UrlValidator::class], $website->validation->validatorClassNames);
        $this->assertSame(
            ['title', 'firstName', 'middleName', 'lastName'],
            $settings->publicProfile->details['headline'],
        );
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
                'position' => ['special' => 'datasFromContracts', 'contracts' => 'all', 'onlyValid' => false, 'fields' => ['position']],
                'subline' => 'LLL:EXT:site/Resources/Private/Language/locallang.xlf:profile.subline',
                'menuSectionsDatas' => ['researchProjects' => 'scientificResearch'],
            ],
            $settings->publicProfile->details,
        );
    }

    #[Test]
    public function theShippedContractBlocksShowEveryContract(): void
    {
        $settings = $this->normalize($this->getShippedConfiguration());

        $this->assertSame(
            ['special' => 'datasFromContracts', 'contracts' => 'all', 'onlyValid' => false, 'fields' => ['position']],
            $settings->publicProfile->details['position'],
        );
        $this->assertSame(
            ['special' => 'datasFromContracts', 'contracts' => 'all', 'onlyValid' => false],
            $settings->publicProfile->details['contact'],
        );
    }

    /**
     * @return \Generator<string, array{0: mixed, 1: list<string>}>
     */
    public static function positionFieldsDataProvider(): \Generator
    {
        yield 'not configured' => [null, ['position']];
        yield 'the shipped list' => [['position'], ['position']];
        yield 'every value, in an order of its own' => [
            ['organisationalUnit', 'functionType', 'position'],
            ['organisationalUnit', 'functionType', 'position'],
        ];
        yield 'surrounding whitespace and a repetition' => [[' functionType ', 'functionType'], ['functionType']];
        yield 'an unknown value is dropped' => [['functionType', 'room', 'Position'], ['functionType']];
        yield 'nothing but unknown values' => [['room'], ['position']];
        yield 'an empty list' => [[], ['position']];
        yield 'not a list' => ['functionType', ['position']];
    }

    /**
     * `fields` of the position line lists what it shows of each contract, in order. Only
     * `position`, `functionType` and `organisationalUnit` are accepted. A list that keeps none of
     * them is the default, the position alone, which is also what the line showed before the key
     * existed.
     *
     * @param list<string> $expected
     */
    #[Test]
    #[DataProvider('positionFieldsDataProvider')]
    public function thePositionLineCarriesTheAcceptedFields(mixed $fields, array $expected): void
    {
        $position = ['special' => 'datasFromContracts'];
        if ($fields !== null) {
            $position['fields'] = $fields;
        }
        $settings = $this->normalize(['profile' => ['details' => ['position' => $position]]]);

        $this->assertSame(
            ['special' => 'datasFromContracts', 'contracts' => 'all', 'onlyValid' => false, 'fields' => $expected],
            $settings->publicProfile->details['position'],
        );
    }

    /**
     * The contact block shows every contact row of a contract and has no `fields`, whatever
     * is configured for it.
     */
    #[Test]
    public function theContactBlockGetsNoFields(): void
    {
        $settings = $this->normalize([
            'profile' => [
                'details' => [
                    'contact' => ['special' => 'datasFromContracts', 'fields' => ['functionType']],
                ],
            ],
        ]);

        $this->assertSame(
            ['special' => 'datasFromContracts', 'contracts' => 'all', 'onlyValid' => false],
            $settings->publicProfile->details['contact'],
        );
    }

    /**
     * @return \Generator<string, array{0: array<string, mixed>, 1: string, 2: bool}>
     */
    public static function contractBlockDataProvider(): \Generator
    {
        yield 'neither key' => [[], 'all', false];
        yield 'first, valid only' => [['contracts' => 'first', 'onlyValid' => true], 'first', true];
        yield 'all, stated' => [['contracts' => 'all', 'onlyValid' => false], 'all', false];
        yield 'surrounding whitespace' => [['contracts' => ' first '], 'first', false];
        yield 'unknown display value' => [['contracts' => 'last'], 'all', false];
        yield 'display value of the wrong type' => [['contracts' => ['first']], 'all', false];
        yield 'onlyValid as a string' => [['onlyValid' => 'true'], 'all', true];
        yield 'onlyValid as a number' => [['onlyValid' => 1], 'all', true];
        yield 'onlyValid not a boolean' => [['onlyValid' => 'sometimes'], 'all', false];
        yield 'onlyValid empty' => [['onlyValid' => null], 'all', false];
    }

    /**
     * A block rendered from the contracts always carries both keys: a missing `contracts`
     * is `all`, a missing `onlyValid` is `false`, and an invalid value is dropped for the
     * default like every other invalid value of the file.
     *
     * @param array<string, mixed> $keys
     */
    #[Test]
    #[DataProvider('contractBlockDataProvider')]
    public function aContractBlockCarriesAValidSelection(array $keys, string $contracts, bool $onlyValid): void
    {
        $settings = $this->normalize([
            'profile' => [
                'details' => [
                    'contact' => array_merge(['special' => 'datasFromContracts'], $keys),
                ],
            ],
        ]);

        $this->assertSame(
            ['special' => 'datasFromContracts', 'contracts' => $contracts, 'onlyValid' => $onlyValid],
            $settings->publicProfile->details['contact'],
        );
    }

    /**
     * Only a block rendered from the contracts selects contracts; another map is left as
     * it is.
     */
    #[Test]
    public function aBlockNotRenderedFromTheContractsGetsNoSelection(): void
    {
        $settings = $this->normalize([
            'profile' => [
                'details' => [
                    'contact' => ['special' => 'somethingElse'],
                ],
            ],
        ]);

        $this->assertSame(['special' => 'somethingElse'], $settings->publicProfile->details['contact']);
    }

    /**
     * A site package that copied the shipped map before 3.0 still names the
     * removed `publish` field. The editor would show a switch it can neither
     * fill nor store, so the field is left out, and the integrator is told why
     * and what to remove.
     */
    #[Test]
    public function theRemovedPublishFieldOfACopiedMapIsLeftOutWithAWarning(): void
    {
        $configuration = $this->getShippedConfiguration();
        $configuration['contracts']['fields']['publish'] = [
            'fieldType' => 'check',
            'renderType' => 'checkbox',
        ];
        $logger = new class () extends AbstractLogger {
            /** @var list<array{level: string, message: string, context: array<string, mixed>}> */
            public array $records = [];

            public function log($level, string|\Stringable $message, array $context = []): void
            {
                $this->records[] = ['level' => (string)$level, 'message' => (string)$message, 'context' => $context];
            }
        };
        $settings = $this->factory($this->createMock(PhpFrontend::class), logger: $logger)->normalize($configuration);

        $this->assertArrayNotHasKey('publish', $settings->contractFields);
        $this->assertArrayHasKey('officeHours', $settings->contractFields);
        $this->assertCount(1, $logger->records);
        $this->assertSame(LogLevel::WARNING, $logger->records[0]['level']);
        $this->assertSame(['identifier' => 'publish', 'property' => 'publish'], $logger->records[0]['context']);
        $this->assertStringContainsString('contracts.fields.{identifier}', $logger->records[0]['message']);
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
        $this->assertSame(['title', 'image', 'skipSync', 'hidden'], array_keys($settings->specialFields));
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
            $this->assertSame(['hide', 'view', 'down', 'up', 'delete', 'edit'], $section->actions, $identifier);
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
        $this->assertSame(['hide', 'view', 'down', 'up', 'delete', 'edit'], $contracts->actions);
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
     * for the synchronisation from the frontend user. The special `skipSync` and
     * `hidden` are the special fields addressing a profile column directly, so they
     * join the profile update set; the composed title and the image do not.
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
        $this->assertTrue($settings->getSpecialField('hidden')?->hasDirectProfileProperty());
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
                'hidden',
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
     * `frontendreadonly: true` in the expanded map is the flag of the short list.
     */
    #[Test]
    public function aDocumentFieldTakesTheFrontendOnlyLockInTheExpandedMap(): void
    {
        $settings = $this->normalize([
            'documentSections' => [
                'publications' => [
                    'label' => 'Publications',
                    'type' => 'publication',
                    'fieldName' => 'publications',
                    'validators' => [
                        'title' => [
                            'required' => true,
                            'frontendreadonly' => true,
                        ],
                    ],
                ],
            ],
        ]);

        $title = $settings->getDocumentValidationSet('publications')->get('title');
        $this->assertNotNull($title);
        $this->assertSame(['required', 'frontendreadonly'], $title->flags);
        $this->assertTrue($title->readOnly);
        $this->assertFalse($title->required);
        $this->assertFalse($title->tcaConfig['readOnly']);
        $this->assertTrue($title->tcaConfig['required']);
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
     * The shipped map is what the synchronisation wrote before it existed: the
     * five profile columns, no contract property, one address, one e-mail
     * address, telephone and fax with the types of the extension configuration.
     */
    #[Test]
    public function theShippedFrontendUserSyncMapIsThePreviousSynchronisation(): void
    {
        $settings = $this->normalize($this->getShippedConfiguration());

        $this->assertEquals(
            new FrontendUserSyncSettings(
                profile: [
                    'title' => 'title',
                    'firstName' => 'first_name',
                    'middleName' => 'middle_name',
                    'lastName' => 'last_name',
                    'website' => 'www',
                ],
                contract: [],
                physicalAddresses: [
                    new FrontendUserSyncEntry(['street' => 'address', 'zip' => 'zip', 'city' => 'city', 'country' => 'country']),
                ],
                emailAddresses: [new FrontendUserSyncEntry(['email' => 'email'])],
                phoneNumbers: [
                    new FrontendUserSyncEntry(['phoneNumber' => 'telephone']),
                    new FrontendUserSyncEntry(['phoneNumber' => 'fax']),
                ],
            ),
            $settings->frontendUserSync,
        );
    }

    /**
     * A column of `''` - or `~`, which the loader removes from a map and a list
     * entry keeps - is not synchronised, and is simply absent from the map.
     * Names and types are trimmed, the order of the lists is kept.
     */
    #[Test]
    public function aFrontendUserSyncPropertyMappedToNothingIsLeftOut(): void
    {
        $settings = $this->normalize([
            'frontendUserSync' => [
                'profile' => ['firstName' => ' first_name ', 'website' => ''],
                'contract' => ['position' => 'tx_project_position', 'room' => ''],
                'physicalAddresses' => [['street' => 'address', 'zip' => null, 'city' => '']],
                'emailAddresses' => [['column' => 'tx_project_email']],
                'phoneNumbers' => [
                    ['column' => 'tx_project_mobile', 'type' => ' mobile '],
                    ['column' => 'telephone', 'type' => ''],
                ],
            ],
        ])->frontendUserSync;

        $this->assertSame(['firstName' => 'first_name'], $settings->profile);
        $this->assertSame(['position' => 'tx_project_position'], $settings->contract);
        $this->assertEquals([new FrontendUserSyncEntry(['street' => 'address'])], $settings->physicalAddresses);
        $this->assertEquals([new FrontendUserSyncEntry(['email' => 'tx_project_email'])], $settings->emailAddresses);
        $this->assertEquals(
            [
                new FrontendUserSyncEntry(['phoneNumber' => 'tx_project_mobile'], 'mobile'),
                new FrontendUserSyncEntry(['phoneNumber' => 'telephone']),
            ],
            $settings->phoneNumbers,
        );
        $this->assertSame([], $settings->problems);
    }

    /**
     * A relation entry is kept with the defaults of what it leaves out: the
     * first field it can be matched by, and no creation. Without a column, or
     * as `~`, the relation is not synchronised and is absent from the map.
     */
    #[Test]
    public function aContractRelationIsKeptWithTheDefaultsOfWhatItLeavesOut(): void
    {
        $settings = $this->normalize([
            'frontendUserSync' => [
                'contract' => [
                    'position' => 'tx_project_position',
                    'organisationalUnit' => ['column' => ' company ', 'matchBy' => 'unitName', 'create' => true, 'storagePid' => 7],
                    'functionType' => ['column' => 'tx_project_function'],
                ],
            ],
        ])->frontendUserSync;

        $this->assertSame(['position' => 'tx_project_position'], $settings->contract);
        $this->assertEquals(
            [
                'organisationalUnit' => new FrontendUserSyncRelation('company', 'unitName', true, 7),
                'functionType' => new FrontendUserSyncRelation('tx_project_function', 'functionName', false, 0),
            ],
            $settings->relations,
        );
        $this->assertSame([], $settings->problems);

        $unmapped = $this->normalize([
            'frontendUserSync' => [
                'contract' => [
                    'organisationalUnit' => ['column' => '', 'matchBy' => 'uniqueName', 'create' => false, 'storagePid' => 0],
                    'functionType' => null,
                ],
            ],
        ])->frontendUserSync;

        $this->assertSame([], $unmapped->relations);
        $this->assertFalse($unmapped->mapsContract());
        $this->assertSame([], $unmapped->problems);
    }

    /**
     * @return \Generator<string, array{array<string, mixed>, string}>
     */
    public static function invalidFrontendUserSyncMaps(): \Generator
    {
        yield 'an unknown profile property' => [
            ['profile' => ['firstname' => 'first_name']],
            '`frontendUserSync.profile.firstname` is not supported',
        ];
        yield 'a derived profile property' => [
            ['profile' => ['lastNameAlpha' => 'last_name']],
            '`frontendUserSync.profile.lastNameAlpha` is not supported',
        ];
        yield 'an unknown contract property' => [
            ['contract' => ['officeHours' => 'tx_project_hours']],
            '`frontendUserSync.contract.officeHours` is not supported',
        ];
        yield 'an unknown address property' => [
            ['physicalAddresses' => [['street' => 'address', 'postcode' => 'zip']]],
            '`frontendUserSync.physicalAddresses.0.postcode` is not supported',
        ];
        yield 'a type on an e-mail address' => [
            ['emailAddresses' => [['column' => 'email', 'type' => 'business']]],
            '`frontendUserSync.emailAddresses.0.type` is not supported',
        ];
        yield 'an unknown top-level key' => [
            ['phoneNumber' => [['column' => 'mobile']]],
            '`frontendUserSync.phoneNumber` is not a known key',
        ];
        yield 'a column that is not a string' => [
            ['profile' => ['title' => ['title']]],
            '`frontendUserSync.profile.title` must be a column name',
        ];
        yield 'a map where a list belongs' => [
            ['phoneNumbers' => ['column' => 'mobile']],
            '`frontendUserSync.phoneNumbers` must be a list',
        ];
        yield 'a type that is not a string' => [
            ['phoneNumbers' => [['column' => 'mobile', 'type' => ['mobile']]]],
            '`frontendUserSync.phoneNumbers.0.type` must be a string',
        ];
        yield 'two phone numbers named by the same column' => [
            ['phoneNumbers' => [['column' => 'mobile', 'type' => 'mobile'], ['column' => 'mobile', 'type' => 'private']]],
            '`frontendUserSync.phoneNumbers` names the column `mobile` first in more than one entry',
        ];
        yield 'two e-mail addresses named by the same column' => [
            ['emailAddresses' => [['column' => 'email'], ['column' => 'email']]],
            '`frontendUserSync.emailAddresses` names the column `email` first in more than one entry',
        ];
        yield 'two addresses named by the same first column' => [
            ['physicalAddresses' => [['street' => 'address', 'city' => 'city'], ['street' => 'address', 'city' => 'tx_city']]],
            '`frontendUserSync.physicalAddresses` names the column `address` first in more than one entry',
        ];
        yield 'an e-mail address that maps no column' => [
            ['emailAddresses' => [['column' => ''], ['column' => 'tx_project_email']]],
            '`frontendUserSync.emailAddresses.0` maps no column',
        ];
        yield 'an address whose columns are all empty' => [
            ['physicalAddresses' => [['street' => '', 'city' => null]]],
            '`frontendUserSync.physicalAddresses.0` maps no column',
        ];
        yield 'a phone number with a type and no column' => [
            ['phoneNumbers' => [['type' => 'mobile']]],
            '`frontendUserSync.phoneNumbers.0` maps no column',
        ];
        yield 'a relation that is no map' => [
            ['contract' => ['organisationalUnit' => 'company']],
            '`frontendUserSync.contract.organisationalUnit` must be a map',
        ];
        yield 'an unknown key of a relation' => [
            ['contract' => ['functionType' => ['column' => 'tx_function', 'categoryType' => 'staff']]],
            '`frontendUserSync.contract.functionType.categoryType` is not supported',
        ];
        yield 'a relation column that is not a string' => [
            ['contract' => ['functionType' => ['column' => ['tx_function']]]],
            '`frontendUserSync.contract.functionType.column` must be a column name',
        ];
        yield 'a field the organisational unit is not matched by' => [
            ['contract' => ['organisationalUnit' => ['column' => 'company', 'matchBy' => 'unique_name']]],
            '`frontendUserSync.contract.organisationalUnit.matchBy` must be one of: uniqueName, unitName',
        ];
        yield 'a field the function type is not matched by' => [
            ['contract' => ['functionType' => ['column' => 'tx_function', 'matchBy' => 'uniqueName']]],
            '`frontendUserSync.contract.functionType.matchBy` must be one of: functionName',
        ];
        yield 'a creation switch that is not a boolean' => [
            ['contract' => ['functionType' => ['column' => 'tx_function', 'create' => 'yes', 'storagePid' => 7]]],
            '`frontendUserSync.contract.functionType.create` must be true or false',
        ];
        yield 'a storage page that is no page uid' => [
            ['contract' => ['functionType' => ['column' => 'tx_function', 'create' => true, 'storagePid' => -1]]],
            '`frontendUserSync.contract.functionType.storagePid` must be a page uid',
        ];
        yield 'creation without a storage page' => [
            ['contract' => ['organisationalUnit' => ['column' => 'company', 'create' => true]]],
            '`frontendUserSync.contract.organisationalUnit` creates missing records and needs a `storagePid`',
        ];
        yield 'creation without a storage page, on a relation that is not mapped' => [
            ['contract' => ['organisationalUnit' => ['column' => '', 'create' => true, 'storagePid' => 0]]],
            '`frontendUserSync.contract.organisationalUnit` creates missing records and needs a `storagePid`',
        ];
        yield 'the column phone, the identifier of the telephone records before ACE-365' => [
            ['phoneNumbers' => [['column' => 'fax'], ['column' => 'phone', 'type' => 'mobile']]],
            '`frontendUserSync.phoneNumbers.1` cannot read the column `phone`',
        ];
    }

    /**
     * A typo in an entry is one mistake: the entry maps no column because of it,
     * and "remove the entry instead" would be the wrong advice.
     */
    #[Test]
    public function aTypoInAnEntryIsNamedOnce(): void
    {
        $settings = $this->normalize([
            'frontendUserSync' => ['emailAddresses' => [['colum' => 'email']]],
        ])->frontendUserSync;

        $this->assertCount(1, $settings->problems);
        $this->assertStringContainsString('`frontendUserSync.emailAddresses.0.colum` is not supported', $settings->problems[0]);
    }

    /**
     * A mistake in the map is named, not thrown: the graph is also built for
     * the TCA, which a typo in the synchronisation must not break. The check
     * the synchronisation runs first throws it, with the path of the entry.
     *
     * @param array<string, mixed> $frontendUserSync
     */
    #[DataProvider('invalidFrontendUserSyncMaps')]
    #[Test]
    public function aMistakeInTheFrontendUserSyncMapIsRefusedWhereTheMapIsUsed(
        array $frontendUserSync,
        string $expectedProblem,
    ): void {
        $settings = $this->normalize([
            'profile' => $this->getShippedConfiguration()['profile'],
            'frontendUserSync' => $frontendUserSync,
        ]);

        $this->assertNotSame([], $settings->profileSections);
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1790142324);
        $this->expectExceptionMessage($expectedProblem);
        $settings->frontendUserSync->assertValid();
    }

    /**
     * The shipped lists are empty: an installation that names no field keeps
     * every field of every record editable.
     */
    #[Test]
    public function theShippedFileManagesNoField(): void
    {
        $settings = $this->normalize($this->getShippedConfiguration());

        $this->assertEquals(new ManagedFieldsSettings(), $settings->managedFields);
    }

    /**
     * An entry names a field by its settings key or by the property it addresses,
     * and is kept as that field's property and column - `emailAddress` is the key
     * of the property `email`, `phoneNumberType` the key of the property `type`.
     */
    #[Test]
    public function aManagedFieldIsResolvedToItsPropertyAndColumn(): void
    {
        $settings = $this->normalize([
            ...$this->getShippedConfiguration(),
            'managedFields' => [
                'profile' => ['title', ' lastName '],
                'contracts' => ['position', 'organisationalUnit'],
                'emailAddresses' => ['emailAddress'],
                'phoneNumbers' => ['phoneNumber', 'type'],
                'physicalAddresses' => ['streetNumber'],
            ],
        ]);

        $this->assertSame(
            [
                'profile' => ['title' => 'title', 'lastName' => 'last_name'],
                'contracts' => ['position' => 'position', 'organisationalUnit' => 'organisational_unit'],
                'emailAddresses' => ['email' => 'email'],
                'phoneNumbers' => ['phoneNumber' => 'phone_number', 'type' => 'type'],
                'physicalAddresses' => ['streetNumber' => 'street_number'],
            ],
            $settings->managedFields->fields,
        );
        $this->assertSame([], $settings->managedFields->problems);
    }

    /**
     * Only the list of the record's own type applies: a table is asked for its
     * columns, and a table outside the map has none.
     */
    #[Test]
    public function theColumnsOfOneTableComeFromTheListOfItsRecordType(): void
    {
        $managedFields = $this->normalize([
            ...$this->getShippedConfiguration(),
            'managedFields' => ['contracts' => ['position', 'room'], 'emailAddresses' => ['type']],
        ])->managedFields;

        $this->assertSame(['position', 'room'], $managedFields->getColumns('tx_academicpersons_domain_model_contract'));
        $this->assertSame(['type'], $managedFields->getColumns('tx_academicpersons_domain_model_email'));
        $this->assertSame([], $managedFields->getColumns('tx_academicpersons_domain_model_phone_number'));
        $this->assertSame([], $managedFields->getColumns('tx_academicpersons_domain_model_profile'));
        $this->assertSame([], $managedFields->getColumns('pages'));
    }

    /**
     * @return \Generator<string, array{mixed, string}>
     */
    public static function invalidManagedFieldsMaps(): \Generator
    {
        yield 'a list instead of a map' => [
            ['position'],
            '`managedFields` must be a map',
        ];
        yield 'an unknown record type' => [
            ['contract' => ['position']],
            '`managedFields.contract` is not a known record type, use one of: profile, contracts, emailAddresses, phoneNumbers, physicalAddresses',
        ];
        yield 'a field the record type does not have' => [
            ['contracts' => ['position', 'office']],
            '`managedFields.contracts` names `office`, which is not a field of `contracts.fields`',
        ];
        yield 'the contract field removed in 3.0' => [
            ['contracts' => ['position', 'publish']],
            '`managedFields.contracts` names `publish`, which is not a field of `contracts.fields`',
        ];
        yield 'a database column instead of a field name' => [
            ['profile' => ['last_name']],
            '`managedFields.profile` names `last_name`, which is not a field of `profile`',
        ];
        yield 'a field of another contact section' => [
            ['emailAddresses' => ['street']],
            '`managedFields.emailAddresses` names `street`, which is not a field of `contracts.contactSections.emailAddresses.fields`',
        ];
        yield 'a map instead of a list' => [
            ['contracts' => ['position' => true]],
            '`managedFields.contracts` must be a list of field names',
        ];
        yield 'an entry that is not a name' => [
            ['contracts' => [['position']]],
            '`managedFields.contracts` must be a list of field names',
        ];
    }

    /**
     * A mistake in the map is named, not thrown, for the reason the
     * synchronisation map gives: the graph is also built for the TCA. The
     * resolver of the managed fields throws it, with the name it could not find.
     */
    #[DataProvider('invalidManagedFieldsMaps')]
    #[Test]
    public function aMistakeInTheManagedFieldsMapIsRefusedWhereTheMapIsUsed(
        mixed $managedFields,
        string $expectedProblem,
    ): void {
        $settings = $this->normalize([
            ...$this->getShippedConfiguration(),
            'managedFields' => $managedFields,
        ]);

        $this->assertNotSame([], $settings->contractFields);
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1790536034);
        $this->expectExceptionMessage($expectedProblem);
        $settings->managedFields->assertValid();
    }

    /**
     * A project field is submitted under its identifier and names its column itself,
     * whatever `propertyName` says, and a regular field keeps deriving both.
     */
    #[Test]
    public function aProjectFieldIsKeptWithItsIdentifierAndColumn(): void
    {
        $settings = $this->normalize(['profile' => [
            'namePrefix' => [
                'custom' => true,
                'section' => 'information',
                'propertyName' => 'somethingElse',
                'fieldName' => ' tx_site_prefix ',
                'fieldType' => 'input',
                'renderType' => 'text',
                'validators' => ['required'],
            ],
            'title' => ['section' => 'information', 'fieldType' => 'input', 'renderType' => 'text'],
        ]]);

        $field = $settings->getProfileField('namePrefix');
        $this->assertNotNull($field);
        $this->assertTrue($field->custom);
        $this->assertSame('namePrefix', $field->propertyName);
        $this->assertSame('tx_site_prefix', $field->fieldName);
        $this->assertSame('tx_site_prefix', $field->validation->fieldName);
        $this->assertTrue($field->validation->required);
        $this->assertSame(['namePrefix' => $field], $settings->getCustomProfileFields());
        $this->assertFalse($settings->getProfileField('title')?->custom);
        $this->assertArrayHasKey('namePrefix', $settings->getProfileUpdateValidationSet()->validations);
    }

    /**
     * Without a column the field is still kept, so the TCA listener and the editor can
     * name the mistake instead of the field disappearing without a word. Only `true`
     * declares a project field.
     */
    #[Test]
    public function aProjectFieldWithoutAColumnIsKeptWithAnEmptyOne(): void
    {
        $settings = $this->normalize(['profile' => [
            'namePrefix' => ['custom' => true, 'section' => 'information', 'fieldType' => 'input', 'renderType' => 'text'],
            'nickname' => ['custom' => 'yes', 'section' => 'information', 'fieldType' => 'input', 'renderType' => 'text'],
        ]]);

        $this->assertSame('', $settings->getProfileField('namePrefix')?->fieldName);
        $nickname = $settings->getProfileField('nickname');
        $this->assertNotNull($nickname);
        $this->assertFalse($nickname->custom);
        $this->assertSame('nickname', $nickname->fieldName);
        $this->assertSame(['namePrefix'], array_keys($settings->getCustomProfileFields()));
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

    private function factory(
        PhpFrontend $cache,
        ?PackageManager $packageManager = null,
        LoggerInterface $logger = new NullLogger(),
    ): AcademicPersonsSettingsFactory {
        return new AcademicPersonsSettingsFactory(
            new SettingsFileLoader($cache, $packageManager ?? $this->createMock(PackageManager::class)),
            new ValidationNormalizer(),
            new LegacySettingsMigrator(),
            $logger,
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
