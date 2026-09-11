<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Unit\Domain\Model;

use FGTCLB\AcademicPersons\Domain\Model\Contract;
use FGTCLB\AcademicPersons\Domain\Model\FrontendUser;
use FGTCLB\AcademicPersons\Domain\Model\Profile;
use FGTCLB\AcademicPersons\Domain\Model\ProfileInformation;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * `Profile` is the only model of this package that does more than store what it is
 * handed. Two behaviours carry the weight:
 *
 * - `initializeObject()` creates the nine `ObjectStorage` properties. Extbase calls it
 *   on a freshly created, not yet mapped object, so anything the constructor did not
 *   set would be an uninitialized typed property and a `Error` on first access.
 * - The rich text setters run their value through the TYPO3 HTML sanitizer, and the
 *   `ProfileInformation` setters do the same for `title`, `bodytext` and `link` of
 *   every element of the storage. Those setters are the boundary between the frontend
 *   edit form of `EXT:academic_persons_editor` and the database, which makes what they
 *   let through a security property rather than a formatting detail.
 *
 * The functional `ProfileTest` covers persistence (slug generation), not either of
 * these, so there is no overlap.
 */
final class ProfileTest extends UnitTestCase
{
    /**
     * Every `ObjectStorage` property is a typed property without a default. Reading one
     * that `initializeObject()` forgot is a fatal error, not an empty result, so each of
     * the nine is asserted rather than a representative one.
     *
     * @param \Closure(Profile): ObjectStorage<covariant object> $getter
     */
    #[Test]
    #[DataProvider('objectStorageProperties')]
    public function everyObjectStoragePropertyIsAnEmptyStorageAfterConstruction(\Closure $getter): void
    {
        $this->assertSame(0, $getter(new Profile())->count());
    }

    /**
     * `initializeObject()` is public and Extbase may call it on an object that already
     * carries data. It is destructive by design - the point of the method is a defined
     * empty state - and that is what the mapper relies on.
     *
     * @param \Closure(Profile): ObjectStorage<covariant object> $getter
     * @param \Closure(Profile): void $filler attaches one element of the storage's own type
     */
    #[Test]
    #[DataProvider('objectStorageProperties')]
    public function initializeObjectDiscardsWhatAStorageAlreadyHeld(\Closure $getter, \Closure $filler): void
    {
        $profile = new Profile();
        $filler($profile);
        $this->assertSame(1, $getter($profile)->count());

        $profile->initializeObject();

        $this->assertSame(0, $getter($profile)->count());
    }

    /**
     * @return array<string, array{0: \Closure(Profile): ObjectStorage<covariant object>, 1: \Closure(Profile): void}>
     */
    public static function objectStorageProperties(): array
    {
        $memberships = static fn(Profile $profile): ObjectStorage => $profile->getMemberships();
        $pressMedia = static fn(Profile $profile): ObjectStorage => $profile->getPressMedia();
        $vita = static fn(Profile $profile): ObjectStorage => $profile->getVita();
        $publications = static fn(Profile $profile): ObjectStorage => $profile->getPublications();
        $scientificResearch = static fn(Profile $profile): ObjectStorage => $profile->getScientificResearch();
        $cooperation = static fn(Profile $profile): ObjectStorage => $profile->getCooperation();
        $lectures = static fn(Profile $profile): ObjectStorage => $profile->getLectures();
        // Seven of the nine storages hold the same type, so one filler serves all of them.
        $attachInformation = static fn(\Closure $getter): \Closure => static function (Profile $profile) use ($getter): void {
            $getter($profile)->attach(new ProfileInformation());
        };

        return [
            'contracts' => [
                static fn(Profile $profile): ObjectStorage => $profile->getContracts(),
                static function (Profile $profile): void {
                    $profile->getContracts()->attach(new Contract());
                },
            ],
            'frontendUsers' => [
                static fn(Profile $profile): ObjectStorage => $profile->getFrontendUsers(),
                static function (Profile $profile): void {
                    $profile->getFrontendUsers()->attach(new FrontendUser());
                },
            ],
            'memberships' => [$memberships, $attachInformation($memberships)],
            'pressMedia' => [$pressMedia, $attachInformation($pressMedia)],
            'vita' => [$vita, $attachInformation($vita)],
            'publications' => [$publications, $attachInformation($publications)],
            'scientificResearch' => [$scientificResearch, $attachInformation($scientificResearch)],
            'cooperation' => [$cooperation, $attachInformation($cooperation)],
            'lectures' => [$lectures, $attachInformation($lectures)],
        ];
    }

    /**
     * The payload a rich text editor cannot produce but a crafted request can. The
     * sanitizer encodes rather than drops it, so the text stays visible and inert.
     *
     * @param \Closure(Profile, string): Profile $setter
     * @param \Closure(Profile): string $getter
     */
    #[Test]
    #[DataProvider('sanitizedTextProperties')]
    public function aScriptElementIsEncodedByTheSanitizingSetters(\Closure $setter, \Closure $getter): void
    {
        $profile = new Profile();
        $setter($profile, 'Hello<script>alert(1)</script>World');

        $this->assertSame('Hello&lt;script&gt;alert(1)&lt;/script&gt;World', $getter($profile));
    }

    /**
     * An event handler attribute is removed while the element it sat on is kept, so a
     * previously stored paragraph does not disappear from the rendered profile.
     *
     * @param \Closure(Profile, string): Profile $setter
     * @param \Closure(Profile): string $getter
     */
    #[Test]
    #[DataProvider('sanitizedTextProperties')]
    public function anEventHandlerAttributeIsStrippedByTheSanitizingSetters(\Closure $setter, \Closure $getter): void
    {
        $profile = new Profile();
        $setter($profile, '<p onclick="evil()">Text</p>');

        $this->assertSame('<p>Text</p>', $getter($profile));
    }

    /**
     * The counterpart of the two cases above: sanitizing is not escaping. Markup an
     * editor legitimately produces has to survive, otherwise every save of an unchanged
     * profile would degrade its text one more step.
     *
     * @param \Closure(Profile, string): Profile $setter
     * @param \Closure(Profile): string $getter
     */
    #[Test]
    #[DataProvider('sanitizedTextProperties')]
    public function permittedMarkupSurvivesTheSanitizingSetters(\Closure $setter, \Closure $getter): void
    {
        $profile = new Profile();
        $setter($profile, '<p>A <strong>bold</strong> <a href="https://example.org">link</a></p>');

        $this->assertSame(
            '<p>A <strong>bold</strong> <a href="https://example.org">link</a></p>',
            $getter($profile),
        );
    }

    /**
     * @return array<string, array{0: \Closure(Profile, string): Profile, 1: \Closure(Profile): string}>
     */
    public static function sanitizedTextProperties(): array
    {
        return [
            'coreCompetences' => [
                static fn(Profile $profile, string $value): Profile => $profile->setCoreCompetences($value),
                static fn(Profile $profile): string => $profile->getCoreCompetences(),
            ],
            'miscellaneous' => [
                static fn(Profile $profile, string $value): Profile => $profile->setMiscellaneous($value),
                static fn(Profile $profile): string => $profile->getMiscellaneous(),
            ],
            'supervisedDoctoralThesis' => [
                static fn(Profile $profile, string $value): Profile => $profile->setSupervisedDoctoralThesis($value),
                static fn(Profile $profile): string => $profile->getSupervisedDoctoralThesis(),
            ],
            'supervisedThesis' => [
                static fn(Profile $profile, string $value): Profile => $profile->setSupervisedThesis($value),
                static fn(Profile $profile): string => $profile->getSupervisedThesis(),
            ],
            'teachingArea' => [
                static fn(Profile $profile, string $value): Profile => $profile->setTeachingArea($value),
                static fn(Profile $profile): string => $profile->getTeachingArea(),
            ],
        ];
    }

    /**
     * The seven `ProfileInformation` setters sanitize three fields of every element of
     * the storage they are handed. All three are asserted at once because they are set
     * from one loop body, and dropping one of the lines is the realistic regression.
     *
     * @param \Closure(Profile, ObjectStorage<ProfileInformation>): Profile $setter
     * @param \Closure(Profile): ObjectStorage<ProfileInformation> $getter
     */
    #[Test]
    #[DataProvider('sanitizedProfileInformationProperties')]
    public function titleBodytextAndLinkOfEveryProfileInformationAreSanitized(
        \Closure $setter,
        \Closure $getter,
    ): void {
        $profile = new Profile();
        /** @var ObjectStorage<ProfileInformation> $storage */
        $storage = new ObjectStorage();
        foreach (['first', 'second'] as $marker) {
            $information = new ProfileInformation();
            $information->setTitle($marker . '<script>alert(1)</script>');
            $information->setBodytext('<p onclick="evil()">' . $marker . '</p>');
            $information->setLink('<a href="javascript:alert(1)">' . $marker . '</a>');
            $storage->attach($information);
        }

        $setter($profile, $storage);

        $sanitized = [];
        foreach ($getter($profile) as $information) {
            $sanitized[] = [
                $information->getTitle(),
                $information->getBodytext(),
                $information->getLink(),
            ];
        }
        $this->assertSame(
            [
                [
                    'first&lt;script&gt;alert(1)&lt;/script&gt;',
                    '<p>first</p>',
                    '<a>first</a>',
                ],
                [
                    'second&lt;script&gt;alert(1)&lt;/script&gt;',
                    '<p>second</p>',
                    '<a>second</a>',
                ],
            ],
            $sanitized,
        );
    }

    /**
     * Sanitizing happens on the objects the caller passed in, not on copies, and the
     * storage itself is stored by reference. A caller that keeps its own handle on a
     * `ProfileInformation` therefore observes the sanitized value - which is what makes
     * a second `setVita($sameStorage)` idempotent instead of double-encoding.
     */
    #[Test]
    public function theProfileInformationObjectsAreSanitizedInPlace(): void
    {
        $profile = new Profile();
        $information = new ProfileInformation();
        $information->setTitle('<script>alert(1)</script>');
        /** @var ObjectStorage<ProfileInformation> $storage */
        $storage = new ObjectStorage();
        $storage->attach($information);

        $profile->setVita($storage);

        $this->assertSame('&lt;script&gt;alert(1)&lt;/script&gt;', $information->getTitle());
        $this->assertSame($storage, $profile->getVita());
    }

    /**
     * Feeding the result back in must not encode it a second time. It does not, because
     * the sanitizer parses `&lt;` as an entity rather than as text - without that the
     * value would grow on every save of an unchanged record.
     */
    #[Test]
    public function sanitizingAnAlreadySanitizedValueChangesNothing(): void
    {
        $profile = new Profile();
        $profile->setTeachingArea('Hello<script>alert(1)</script>World');
        $once = $profile->getTeachingArea();

        $profile->setTeachingArea($once);

        $this->assertSame($once, $profile->getTeachingArea());
    }

    /**
     * `link` holds a URL, but it is run through the *HTML* sanitizer like the two text
     * fields next to it. A query string separator is therefore stored HTML encoded.
     *
     * This test documents the current behaviour rather than endorsing it - see the
     * finding reported with this change.
     */
    #[Test]
    public function anAmpersandInAProfileInformationLinkIsHtmlEncoded(): void
    {
        $profile = new Profile();
        $information = new ProfileInformation();
        $information->setLink('https://example.org/?page=1&sort=name');
        /** @var ObjectStorage<ProfileInformation> $storage */
        $storage = new ObjectStorage();
        $storage->attach($information);

        $profile->setPublications($storage);

        $this->assertSame('https://example.org/?page=1&amp;sort=name', $information->getLink());
    }

    /**
     * @return array<string, array{0: \Closure(Profile, ObjectStorage<ProfileInformation>): Profile, 1: \Closure(Profile): ObjectStorage<ProfileInformation>}>
     */
    public static function sanitizedProfileInformationProperties(): array
    {
        return [
            'cooperation' => [
                static fn(Profile $profile, ObjectStorage $storage): Profile => $profile->setCooperation($storage),
                static fn(Profile $profile): ObjectStorage => $profile->getCooperation(),
            ],
            'lectures' => [
                static fn(Profile $profile, ObjectStorage $storage): Profile => $profile->setLectures($storage),
                static fn(Profile $profile): ObjectStorage => $profile->getLectures(),
            ],
            'memberships' => [
                static fn(Profile $profile, ObjectStorage $storage): Profile => $profile->setMemberships($storage),
                static fn(Profile $profile): ObjectStorage => $profile->getMemberships(),
            ],
            'pressMedia' => [
                static fn(Profile $profile, ObjectStorage $storage): Profile => $profile->setPressMedia($storage),
                static fn(Profile $profile): ObjectStorage => $profile->getPressMedia(),
            ],
            'publications' => [
                static fn(Profile $profile, ObjectStorage $storage): Profile => $profile->setPublications($storage),
                static fn(Profile $profile): ObjectStorage => $profile->getPublications(),
            ],
            'scientificResearch' => [
                static fn(Profile $profile, ObjectStorage $storage): Profile => $profile->setScientificResearch($storage),
                static fn(Profile $profile): ObjectStorage => $profile->getScientificResearch(),
            ],
            'vita' => [
                static fn(Profile $profile, ObjectStorage $storage): Profile => $profile->setVita($storage),
                static fn(Profile $profile): ObjectStorage => $profile->getVita(),
            ],
        ];
    }

    /**
     * The profile's own URL properties are *not* sanitized, while `link` of a nested
     * `ProfileInformation` is. Pinning the asymmetry means a future change to either
     * side is a decision instead of an accident.
     */
    #[Test]
    public function theProfilesOwnUrlPropertiesAreStoredUnsanitized(): void
    {
        $profile = new Profile();
        $profile->setWebsite('https://example.org/?page=1&sort=name');
        $profile->setPublicationsLink('https://example.org/?page=1&sort=name');

        $this->assertSame('https://example.org/?page=1&sort=name', $profile->getWebsite());
        $this->assertSame('https://example.org/?page=1&sort=name', $profile->getPublicationsLink());
    }

    /**
     * A record without a uid was never persisted, so there is nothing it could be a
     * translation of - whatever else is set on it.
     */
    #[Test]
    public function aProfileThatWasNeverPersistedIsNotATranslation(): void
    {
        $this->assertFalse((new Profile())->getIsTranslation());
    }

    /**
     * Until the object is inserted, `_languageUid` is not the language of any row:
     * `Backend::insertObject()` writes `0` onto the object when it carries none, and leaves a
     * language it does carry untouched although the inserted row does not get it. `_isNew()`
     * keeps the answer from depending on that state.
     */
    #[Test]
    public function anUnpersistedProfileCarryingALanguageIsNotATranslation(): void
    {
        $profile = new Profile();
        $profile->_setProperty('_languageUid', 1);

        $this->assertFalse($profile->getIsTranslation());
    }

    /**
     * What Extbase maps for a record in the default language.
     */
    #[Test]
    public function aRecordInTheDefaultLanguageIsNotATranslation(): void
    {
        $profile = new Profile();
        $profile->_setProperty('uid', 42);
        $profile->_setProperty('_localizedUid', 42);
        $profile->_setProperty('_languageUid', 0);

        $this->assertFalse($profile->getIsTranslation());
    }

    /**
     * What persisting a model built in PHP leaves behind: the extbase backend assigns `uid`
     * and touches neither `_languageUid` nor `_localizedUid`, both of which only the data
     * mapper writes. The row it wrote is a default language one, so the model has to say so
     * rather than read its own unset properties as a translation (ACE-610).
     */
    #[Test]
    public function aPersistedRecordThatWasNeverMappedIsNotATranslation(): void
    {
        $profile = new Profile();
        $profile->_setProperty('uid', 42);

        $this->assertFalse($profile->getIsTranslation());
    }

    /**
     * What Extbase maps for an overlaid record: `uid` stays the default language row,
     * `_localizedUid` is the translated row that was actually read, and `_languageUid` is
     * the language that row is in.
     */
    #[Test]
    public function aRecordReadAsLanguageOverlayIsATranslation(): void
    {
        $profile = new Profile();
        $profile->_setProperty('uid', 42);
        $profile->_setProperty('_localizedUid', 43);
        $profile->_setProperty('_languageUid', 1);

        $this->assertTrue($profile->getIsTranslation());
    }

    /**
     * A record kept in every language is not a translation of anything either, and `-1`
     * would pass a "differs from the default language" test.
     */
    #[Test]
    public function aRecordForAllLanguagesIsNotATranslation(): void
    {
        $profile = new Profile();
        $profile->_setProperty('uid', 42);
        $profile->_setProperty('_localizedUid', 42);
        $profile->_setProperty('_languageUid', -1);

        $this->assertFalse($profile->getIsTranslation());
    }

    /**
     * `_languageUid` is nullable while the getter is not, so an unmapped record has to
     * come out as the default language rather than raise on the cast.
     */
    #[Test]
    public function getLanguageUidReportsTheDefaultLanguageForAnUnmappedProfile(): void
    {
        $this->assertSame(0, (new Profile())->getLanguageUid());
    }

    /**
     * `-1` is a value the property legitimately carries ("all languages"), so the cast
     * must not clamp it.
     */
    #[Test]
    public function getLanguageUidReportsTheMappedLanguage(): void
    {
        $profile = new Profile();
        $profile->_setProperty('_languageUid', -1);
        $this->assertSame(-1, $profile->getLanguageUid());

        $profile->_setProperty('_languageUid', 3);
        $this->assertSame(3, $profile->getLanguageUid());
    }
}
