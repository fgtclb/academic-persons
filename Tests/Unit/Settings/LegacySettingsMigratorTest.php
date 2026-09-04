<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Unit\Settings;

use FGTCLB\AcademicBase\Settings\SettingsFileLoader;
use FGTCLB\AcademicBase\Settings\ValidationNormalizer;
use FGTCLB\AcademicPersons\Settings\AcademicPersonsSettings;
use FGTCLB\AcademicPersons\Settings\AcademicPersonsSettingsFactory;
use FGTCLB\AcademicPersons\Settings\LegacySettingsMigrator;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Extbase\Validation\Validator\EmailAddressValidator;
use TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator;
use TYPO3\CMS\Extbase\Validation\Validator\UrlValidator;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * The migrator maps the two pre-3.0 keys onto the section maps. The shipped
 * file is the base every case overlays, because that is what an installation
 * that kept its 2.x override runs on: the shipped four maps plus the legacy
 * keys of the site package, folded by the top-level merge.
 */
final class LegacySettingsMigratorTest extends UnitTestCase
{
    /**
     * The override example of the 2.x manual: the three name fields are not
     * listed and are therefore editable, the listed properties get exactly
     * their listed flags, and the flags the old shape could not express -
     * `url` on the website, `email` stays because it is listed - are kept.
     */
    #[Test]
    public function legacyValidationsAreOverlaidOnTheShippedGraph(): void
    {
        $settings = $this->migrateAndNormalize($this->legacyManualExample());

        $firstName = $settings->getProfileField('firstName');
        $this->assertNotNull($firstName);
        $this->assertFalse($firstName->validation->readOnly);
        $this->assertFalse($firstName->validation->disabled);
        $this->assertFalse($firstName->validation->required);
        $this->assertFalse($firstName->validation->tcaConfig['readOnly']);
        $this->assertSame('text', $firstName->validation->inputType, 'The render type is untouched');
        $website = $settings->getProfileField('website');
        $this->assertNotNull($website);
        $this->assertTrue($website->validation->required);
        $this->assertSame([NotEmptyValidator::class, UrlValidator::class], $website->validation->validatorClassNames);
        $this->assertSame(['url', 'required'], $website->validation->flags);
        $miscellaneous = $settings->getProfileField('miscellaneous');
        $this->assertNotNull($miscellaneous);
        $this->assertTrue($miscellaneous->validation->isRichText());
        $this->assertSame(1000, $miscellaneous->validation->characterLimit);

        $email = $settings->getContractContactField('emailAddress');
        $this->assertNotNull($email);
        $this->assertSame([NotEmptyValidator::class, EmailAddressValidator::class], $email->validation->validatorClassNames);
        $this->assertSame('email', $email->validation->tcaConfig['type']);
        $emailType = $settings->getContractContactSection('emailAddresses')?->getField('emailAddressType');
        $this->assertNotNull($emailType);
        $this->assertFalse($emailType->validation->required);
        $street = $settings->getContractContactSection('physicalAddresses')?->getField('street');
        $this->assertNotNull($street);
        $this->assertTrue($street->validation->required);
        $city = $settings->getContractContactSection('physicalAddresses')?->getField('city');
        $this->assertNotNull($city);
        $this->assertFalse($city->validation->required, 'Not listed in the legacy set, so no longer required');
        $phoneNumber = $settings->getContractContactField('phoneNumber');
        $this->assertNotNull($phoneNumber);
        $this->assertTrue($phoneNumber->validation->required);
        $this->assertSame('tel', $phoneNumber->validation->inputType);

        $position = $settings->getContractField('position');
        $this->assertNotNull($position);
        $this->assertTrue($position->validation->required);
        $validFrom = $settings->getContractField('validFrom');
        $this->assertNotNull($validFrom);
        $this->assertFalse($validFrom->validation->required);
        $this->assertSame('date', $validFrom->validation->inputType);

        foreach (['cooperation', 'lecture', 'publication', 'curriculum_vitae'] as $type) {
            $validations = $settings->getDocumentValidationSetByType($type)->validations;
            $this->assertTrue($validations['title']->required, $type);
            // The legacy set does not list the year, so it loses the five flags
            // the old shape knew - here `required` and `number`, which is all the
            // shipped section declares - and no validation is left for it.
            $this->assertArrayNotHasKey('year', $validations, $type . ': the year was not listed');
            $this->assertTrue($validations['bodytext']->isRichText(), $type);
            $this->assertSame(500, $validations['bodytext']->characterLimit, $type);
        }
        $this->assertSame(
            [UrlValidator::class],
            $settings->getDocumentValidationSetByType('lecture')->validations['link']->validatorClassNames,
        );
        $this->assertTrue($settings->getDocumentValidationSet('contracts')->validations['position']->required);
    }

    /**
     * The year properties keep their name and their `number` flag: `year` is
     * the field key of the section map, and the two range years map onto its
     * `from` and `to` aliases.
     */
    #[Test]
    public function legacyProfileInformationYearFlagsReachTheSectionMap(): void
    {
        $migrator = new LegacySettingsMigrator();
        $migration = $migrator->migrate($this->shippedWith([
            'validations' => [
                'profileInformation' => [
                    'year' => ['required', 'number'],
                    'yearStart' => ['number'],
                    'title' => ['required'],
                ],
            ],
        ]));
        $settings = $this->normalize($migration->settings);

        $validations = $settings->getDocumentValidationSetByType('cooperation')->validations;
        $this->assertSame([NotEmptyValidator::class], $validations['year']->validatorClassNames);
        $this->assertSame('number', $validations['year']->inputType);
        $this->assertSame('number', $validations['year']->tcaConfig['type']);
        $this->assertTrue($validations['year']->tcaConfig['required']);
        $this->assertSame(['number'], $validations['yearStart']->flags);
        $this->assertSame(['required', 'number'], $migration->settings['documentSections']['cooperation']['validators']['year']);
        $this->assertContains(
            'validations.profileInformation.yearStart:'
            . ' mapped onto documentSections.<section>.validators.from',
            $migration->notes,
        );
    }

    /**
     * One warning per package and legacy key it ships - not per property, not
     * per request - naming the package, the key and the command that prints
     * the replacement. The notes of the key travel with its warning.
     */
    #[Test]
    public function legacyKeysAreLoggedOncePerPackageAndKey(): void
    {
        $logger = $this->recordingLogger();
        $migrator = new LegacySettingsMigrator();
        $migrator->setLogger($logger);
        $sitePackage = [
            'validations' => [
                'profile' => ['website' => ['required'], 'nickname' => ['required']],
            ],
            'profileInformationsTypes' => ['vita' => ['label' => 'CV', 'type' => 'curriculum_vitae', 'fieldName' => 'vita']],
        ];
        $otherPackage = ['validations' => ['profile' => ['title' => ['required']]]];

        $migrator->migrate($this->shippedWith($sitePackage), [
            'site_package' => $sitePackage,
            'other_package' => $otherPackage,
            'clean_package' => ['profile' => []],
        ]);

        $this->assertCount(3, $logger->records);
        $this->assertSame([LogLevel::WARNING, LogLevel::WARNING, LogLevel::WARNING], array_column($logger->records, 'level'));
        $this->assertSame(
            [
                ['site_package', 'validations'],
                ['site_package', 'profileInformationsTypes'],
                ['other_package', 'validations'],
            ],
            array_map(static fn(array $record): array => [$record['context']['package'], $record['context']['key']], $logger->records),
        );
        $this->assertStringContainsString('academic:persons:settings:migrate', $logger->records[0]['message']);
        $this->assertStringContainsString('{package}', $logger->records[0]['message']);
        $this->assertSame(
            ' validations.profile.nickname: no field of this name in the section maps, skipped.',
            $logger->records[0]['context']['notes'],
        );
        $this->assertSame('', $logger->records[1]['context']['notes']);
    }

    /**
     * Nothing is attributed and therefore nothing is logged when the caller
     * passes no packages, and a settings array without a legacy key is
     * returned as it was, without a note.
     */
    #[Test]
    public function withoutPackagesOrLegacyKeysNothingIsLogged(): void
    {
        $logger = $this->recordingLogger();
        $migrator = new LegacySettingsMigrator();
        $migrator->setLogger($logger);

        $untouched = $migrator->migrate($this->shippedConfiguration(), ['academic_persons' => $this->shippedConfiguration()]);
        $migrated = $migrator->migrate($this->shippedWith($this->legacyManualExample()));

        $this->assertSame([], $logger->records);
        $this->assertSame($this->shippedConfiguration(), $untouched->settings);
        $this->assertFalse($untouched->hasMigratedKeys());
        $this->assertSame([], $untouched->notes);
        $this->assertSame(['validations'], $migrated->migratedKeys);
        $this->assertSame([], $migrator->getLegacyKeys($migrated->settings));
        $this->assertSame(['profile', 'special', 'contracts', 'documentSections'], array_keys($migrated->settings));
    }

    /**
     * A property the section maps do not know - a project's own column, a
     * typo - is skipped with a note, and a set name that never existed is
     * skipped the same way; neither stops the rest of the mapping.
     */
    #[Test]
    public function unknownLegacyPropertiesAndSetsAreSkippedNotFatal(): void
    {
        $migrator = new LegacySettingsMigrator();
        $migration = $migrator->migrate($this->shippedWith([
            'validations' => [
                'profile' => ['nickname' => ['required'], 'structure' => ['required'], 'lastName' => ['readonly']],
                'physicalAddress' => ['type' => ['required'], 'floor' => ['required']],
                'profileInformation' => ['bodytext' => ['required'], 'isbn' => ['required']],
                'award' => ['title' => ['required']],
            ],
        ]));
        $settings = $this->normalize($migration->settings);

        $this->assertSame(
            [
                'validations.profile.nickname: no field of this name in the section maps, skipped',
                'validations.profile.structure: no field of this name in the section maps, skipped',
                'validations.physicalAddress.type: mapped onto the field "physicalAddressType"',
                'validations.physicalAddress.floor: no field of this name in the section maps, skipped',
                'validations.profileInformation.bodytext: mapped onto documentSections.<section>.validators.description',
                'validations.profileInformation.isbn: no timeline entry field of this name, skipped',
                'validations.award: not a validation set of the previous shape, skipped',
            ],
            $migration->notes,
        );
        $this->assertArrayNotHasKey('nickname', $migration->settings['profile']);
        $this->assertArrayNotHasKey('validators', $migration->settings['profile']['structure']);
        $lastName = $settings->getProfileField('lastName');
        $this->assertNotNull($lastName);
        $this->assertTrue($lastName->validation->readOnly);
        $this->assertFalse($lastName->validation->disabled);
        $addressType = $settings->getContractContactSection('physicalAddresses')?->getField('physicalAddressType');
        $this->assertNotNull($addressType);
        $this->assertTrue($addressType->validation->required);
        $description = $settings->getDocumentValidationSetByType('publication')->validations['bodytext'];
        $this->assertTrue($description->required);
        $this->assertTrue($description->isRichText());
        $this->assertSame(500, $description->characterLimit);
        $this->assertSame(
            ['editor' => ['limit' => 500, 'type' => 'ckeditor'], 'validators' => ['required']],
            $migration->settings['documentSections']['publications']['validators']['description'],
        );
    }

    /**
     * The seven shipped types refine their document section - a project's
     * label wins - and an eighth type declared through the settings alone is
     * reported as not migratable: it needs a profile relation and a TCA
     * column the settings never created, so no section is invented for it.
     */
    #[Test]
    public function anEighthProfileInformationTypeIsReportedAsNotMigratable(): void
    {
        $migrator = new LegacySettingsMigrator();
        $migration = $migrator->migrate($this->shippedWith([
            'profileInformationsTypes' => [
                'vita' => ['label' => 'Curriculum vitae', 'type' => 'curriculum_vitae', 'fieldName' => 'vita'],
                'awards' => ['label' => 'Awards', 'type' => 'award', 'fieldName' => 'awards'],
            ],
        ]));
        $settings = $this->normalize($migration->settings);

        $this->assertSame(
            [
                'profileInformationsTypes.awards: no document section and no profile relation of this name - a'
                . ' timeline type added through the settings alone cannot be migrated; declare its column in a'
                . ' TCA override of the profile table and its section below documentSections',
            ],
            $migration->notes,
        );
        $this->assertNull($settings->getDocumentSection('awards'));
        $this->assertNull($settings->getDocumentSectionByType('award'));
        $vita = $settings->getDocumentSection('vita');
        $this->assertNotNull($vita);
        $this->assertSame('Curriculum vitae', $vita->label);
        $this->assertSame('curriculum_vitae', $vita->type);
        $this->assertSame(['from', 'title'], $vita->rowFields, 'The rows and actions of the section are untouched');
        $this->assertArrayNotHasKey('profileInformationsTypes', $migration->settings);
        $this->assertSame(['profileInformationsTypes'], $migration->migratedKeys);
    }

    /**
     * A 2.4 override of the record type or the relation field of a timeline
     * type is reported, not applied. Until 2.4 the two generated the inline
     * column of the profile table; since 3.0 the seven relations are TCA, so
     * mapping the override would move the frontend selection alone and hide
     * every record it creates from the backend.
     */
    #[Test]
    public function anOverriddenTimelineRecordTypeIsReportedAndNotApplied(): void
    {
        $migrator = new LegacySettingsMigrator();
        $migration = $migrator->migrate($this->shippedWith([
            'profileInformationsTypes' => [
                'vita' => ['label' => 'Career', 'type' => 'career', 'fieldName' => 'career'],
            ],
        ]));
        $settings = $this->normalize($migration->settings);

        $this->assertSame(
            [
                'profileInformationsTypes.vita.type: "career" is not mapped - the profile relation of a timeline'
                . ' type is declared in the TCA of the profile table since 3.0 and is no longer generated from the'
                . ' settings, so the section keeps "curriculum_vitae"; a type of your own needs its own column in a'
                . ' TCA override of the profile table',
                'profileInformationsTypes.vita.fieldName: "career" is not mapped - the profile relation of a'
                . ' timeline type is declared in the TCA of the profile table since 3.0 and is no longer generated'
                . ' from the settings, so the section keeps "vita"; a type of your own needs its own column in a TCA'
                . ' override of the profile table',
            ],
            $migration->notes,
        );
        $vita = $settings->getDocumentSection('vita');
        $this->assertNotNull($vita);
        $this->assertSame('Career', $vita->label, 'The label is still mapped.');
        $this->assertSame('curriculum_vitae', $vita->type);
        $this->assertSame('vita', $vita->fieldName);
        $this->assertNull($settings->getDocumentSectionByType('career'));
    }

    /**
     * @return array<string, mixed>
     */
    private function legacyManualExample(): array
    {
        return [
            'validations' => [
                'profile' => ['website' => ['required']],
                'contract' => ['position' => ['required']],
                'emailAddress' => ['email' => ['required', 'email']],
                'phoneNumber' => ['phoneNumber' => ['required']],
                'physicalAddress' => ['street' => ['required']],
                'profileInformation' => ['title' => ['required']],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $legacy
     */
    private function migrateAndNormalize(array $legacy): AcademicPersonsSettings
    {
        return $this->normalize((new LegacySettingsMigrator())->migrate($this->shippedWith($legacy))->settings);
    }

    /**
     * The merged array of an installation whose site package ships the legacy
     * keys: the shipped four maps, and the legacy keys folded in on top.
     *
     * @param array<string, mixed> $legacy
     * @return array<string, mixed>
     */
    private function shippedWith(array $legacy): array
    {
        return array_merge($this->shippedConfiguration(), $legacy);
    }

    /**
     * @return array<string, mixed>
     */
    private function shippedConfiguration(): array
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
        $factory = new AcademicPersonsSettingsFactory(
            new SettingsFileLoader($this->createMock(PhpFrontend::class), $this->createMock(PackageManager::class)),
            new ValidationNormalizer(),
            new LegacySettingsMigrator(),
        );
        return $factory->normalize($configuration);
    }

    /**
     * @return AbstractLogger&object{records: list<array{level: string, message: string, context: array<string, mixed>}>}
     */
    private function recordingLogger(): AbstractLogger
    {
        return new class () extends AbstractLogger {
            /** @var list<array{level: string, message: string, context: array<string, mixed>}> */
            public array $records = [];

            public function log($level, string|\Stringable $message, array $context = []): void
            {
                $this->records[] = ['level' => (string)$level, 'message' => (string)$message, 'context' => $context];
            }
        };
    }
}
