<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Unit\Domain\Model\Dto\Syncronizer;

use FGTCLB\AcademicPersons\Domain\Model\Dto\Syncronizer\SynchronizerContext;
use FGTCLB\AcademicPersons\Service\RecordSynchronizerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * `SynchronizerContext::create()` is the only place that turns the configured language
 * id list of `EXT:academic_persons_edit` (`profile/allowedLanguages`, an
 * integrator-maintained comma separated string) into site languages.
 * `RecordSynchronizer::synchronizeRecord()` then loops over exactly that list and writes
 * a translation per entry, so every id that survives here becomes a database write.
 */
final class SynchronizerContextTest extends UnitTestCase
{
    /**
     * The site languages are carried as objects, not ids: the synchronizer needs
     * `getLanguageId()` off them, and resolving them once is what makes the unknown ones
     * detectable at all.
     */
    #[Test]
    public function everyConfiguredLanguageIsResolvedToItsSiteLanguage(): void
    {
        $site = $this->site();

        $subject = SynchronizerContext::create(
            $this->createMock(RecordSynchronizerInterface::class),
            $site,
            [1, 2],
            'tx_academicpersons_domain_model_profile',
            42,
        );

        $this->assertSame([1, 2], $subject->getAllowedLanguageIds());
        $this->assertSame($site->getLanguageById(1), $subject->allowedSiteLanguages[1]);
        $this->assertSame($site->getLanguageById(2), $subject->allowedSiteLanguages[2]);
    }

    /**
     * The ids reach the factory from an extension configuration string. They are already
     * `intExplode()`d by `ProfileTranslator::getAllowedLanguageIds()` today, but the
     * signature accepts strings and a second caller may not cast - a `'2'` must select
     * language 2, not fall over the `<= 0` guard.
     */
    #[Test]
    public function languageIdsAreAcceptedAsStrings(): void
    {
        $subject = SynchronizerContext::create(
            $this->createMock(RecordSynchronizerInterface::class),
            $this->site(),
            ['1', '2'],
            'tx_academicpersons_domain_model_profile',
            42,
        );

        $this->assertSame([1, 2], $subject->getAllowedLanguageIds());
    }

    /**
     * Language 0 is the source of the synchronization. Were it kept, the synchronizer
     * would look for a "translation" of the default language record, not find one, and
     * create a duplicate of the record it is copying from. `-1` ("all languages") has no
     * site language either, and anything non numeric casts to 0.
     *
     * @param array<int, string|int> $allowedLanguageIds
     */
    #[Test]
    #[DataProvider('languageIdsWithoutATranslationTarget')]
    public function anIdThatIsNoTranslationTargetIsDropped(array $allowedLanguageIds): void
    {
        $subject = SynchronizerContext::create(
            $this->createMock(RecordSynchronizerInterface::class),
            $this->site(),
            $allowedLanguageIds,
            'tx_academicpersons_domain_model_profile',
            42,
        );

        $this->assertSame([], $subject->getAllowedLanguageIds());
        $this->assertSame([], $subject->allowedSiteLanguages);
    }

    /**
     * @return \Generator<string, array{0: array<int, string|int>}>
     */
    public static function languageIdsWithoutATranslationTarget(): \Generator
    {
        yield 'nothing configured' => [[]];
        yield 'the default language' => [[0]];
        yield 'the default language as string' => [['0']];
        yield 'all languages' => [[-1]];
        yield 'an empty string' => [['']];
        yield 'a non numeric value' => [['default']];
    }

    /**
     * An integrator keeps `profile/allowedLanguages` per installation while a site
     * configuration may drop a language at any time. That must stay a skipped
     * translation, not an uncaught `\InvalidArgumentException` out of a DataHandler hook.
     */
    #[Test]
    public function aLanguageTheSiteDoesNotConfigureIsDropped(): void
    {
        $subject = SynchronizerContext::create(
            $this->createMock(RecordSynchronizerInterface::class),
            $this->site(),
            [1, 99, 2],
            'tx_academicpersons_domain_model_profile',
            42,
        );

        $this->assertSame([1, 2], $subject->getAllowedLanguageIds());
    }

    /**
     * The list is keyed by language id, so a duplicated entry in the configuration
     * string cannot make the synchronizer write the same translation twice.
     */
    #[Test]
    public function theSameLanguageIsCollectedOnce(): void
    {
        $subject = SynchronizerContext::create(
            $this->createMock(RecordSynchronizerInterface::class),
            $this->site(),
            [2, '2', 2],
            'tx_academicpersons_domain_model_profile',
            42,
        );

        $this->assertSame([2], $subject->getAllowedLanguageIds());
    }

    /**
     * Only exception code 1522960188 - `Site::getLanguageById()` on an unknown id - is
     * the expected one. Catching `\InvalidArgumentException` wholesale would otherwise
     * turn an unrelated defect into a silently shortened language list, and the
     * synchronization would report success while having translated nothing.
     */
    #[Test]
    public function anUnexpectedSiteFailureIsNotSwallowed(): void
    {
        $configuration = [
            'base' => 'https://example.com/',
            'languages' => [
                ['languageId' => 0, 'locale' => 'en_US.UTF-8', 'title' => 'English', 'base' => '/'],
            ],
        ];
        $site = new class ('failing-site', 1, $configuration) extends Site {
            public function getLanguageById(int $languageId): SiteLanguage
            {
                throw new \InvalidArgumentException('Something else went wrong.', 1234567890);
            }
        };

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1234567890);

        SynchronizerContext::create(
            $this->createMock(RecordSynchronizerInterface::class),
            $site,
            [1],
            'tx_academicpersons_domain_model_profile',
            42,
        );
    }

    /**
     * The default language is what the synchronizer reads the source record in. Taking it
     * from the site rather than from the caller is what keeps it consistent with the
     * languages resolved next to it.
     */
    #[Test]
    public function theDefaultLanguageIsTakenFromTheSite(): void
    {
        $site = $this->site();

        $subject = SynchronizerContext::create(
            $this->createMock(RecordSynchronizerInterface::class),
            $site,
            [1],
            'tx_academicpersons_domain_model_profile',
            42,
        );

        $this->assertSame($site->getDefaultLanguage(), $subject->defaultLanguage);
        $this->assertSame(0, $subject->defaultLanguage->getLanguageId());
    }

    /**
     * `RecordSynchronizer` recurses into inline children with `withRecord()` while it is
     * iterating the parent's languages. A mutating implementation would rewrite the
     * context under that loop and continue the parent's work on the child's table.
     */
    #[Test]
    public function withRecordDescendsToAnotherRecordWithoutTouchingTheOriginal(): void
    {
        $recordSynchronizer = $this->createMock(RecordSynchronizerInterface::class);
        $site = $this->site();
        $subject = SynchronizerContext::create(
            $recordSynchronizer,
            $site,
            [1, 2],
            'tx_academicpersons_domain_model_profile',
            42,
        );

        $child = $subject->withRecord('tx_academicpersons_domain_model_profile_email', 7);

        $this->assertNotSame($subject, $child);
        $this->assertSame('tx_academicpersons_domain_model_profile_email', $child->tableName);
        $this->assertSame(7, $child->uid);
        $this->assertSame('tx_academicpersons_domain_model_profile', $subject->tableName);
        $this->assertSame(42, $subject->uid);

        $this->assertSame($recordSynchronizer, $child->recordSyncronizer);
        $this->assertSame($site, $child->site);
        $this->assertSame($subject->defaultLanguage, $child->defaultLanguage);
        $this->assertSame($subject->allowedSiteLanguages, $child->allowedSiteLanguages);
    }

    /**
     * A site with a default language and two translations, which is what the
     * synchronization is written for.
     */
    private function site(): Site
    {
        return new Site('academic-persons', 1, [
            'base' => 'https://example.com/',
            'languages' => [
                ['languageId' => 0, 'locale' => 'en_US.UTF-8', 'title' => 'English', 'base' => '/'],
                ['languageId' => 1, 'locale' => 'de_DE.UTF-8', 'title' => 'German', 'base' => '/de/'],
                ['languageId' => 2, 'locale' => 'fr_FR.UTF-8', 'title' => 'French', 'base' => '/fr/'],
            ],
        ]);
    }
}
