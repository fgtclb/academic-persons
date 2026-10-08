<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Backend\FormEngine;

use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Backend\Form\FormDataGroup\TcaDatabaseRecord;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * The profile select of a profile information record (ACE-842).
 *
 * A translated profile information points to the translated profile, which is
 * what the DataHandler writes when it localizes the inline children of a
 * profile. The select offered the profiles of the languages -1 and 0 only, so
 * the edit form of every such translation showed `[ MISSING LABEL ("2") ]`
 * instead of the profile.
 */
final class ProfileInformationProfileSelectTest extends AbstractAcademicPersonsTestCase
{
    private const TABLE = 'tx_academicpersons_domain_model_profile_information';

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ProfileInformationProfileSelect/profileInformation.csv');
        $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
    }

    /**
     * FormEngine puts a value it finds no item for back as an item of its own,
     * labelled `[ MISSING LABEL ("<value>") ]`, so the value alone proves nothing.
     */
    #[Test]
    public function aTranslationIsOfferedTheTranslatedProfile(): void
    {
        $items = $this->offeredProfiles(2);

        $this->assertArrayHasKey(2, $items);
        $this->assertStringNotContainsString('MISSING LABEL', $items[2]);
        $this->assertStringContainsString('Abel', $items[2]);
    }

    /**
     * Profile 4 is a translation into another language. A record offers the
     * profiles of its own language and of the default language, never those of
     * a third one.
     */
    #[Test]
    public function aTranslationIsNotOfferedTheProfilesOfAnotherLanguage(): void
    {
        $this->assertSame([1, 2, 3], array_keys($this->offeredProfiles(2)));
    }

    #[Test]
    public function aDefaultLanguageRecordIsOfferedDefaultLanguageProfilesOnly(): void
    {
        $items = $this->offeredProfiles(1);

        $this->assertSame([1, 3], array_keys($items));
        $this->assertStringNotContainsString('MISSING LABEL', $items[1]);
    }

    /**
     * A new record takes the language from the field default. Without one the
     * language marker of the select is replaced by `''`, and `IN (-1,0,'')` is
     * rejected by PostgreSQL, which leaves the select empty.
     */
    #[Test]
    public function aNewRecordIsOfferedDefaultLanguageProfiles(): void
    {
        $items = $this->offeredProfiles(2, 'new');

        $this->assertSame([1, 3], array_keys($items));
        $this->assertStringContainsString('Abel', $items[1]);
        $this->assertStringContainsString('Brecht', $items[3]);
    }

    /**
     * @param int $uid the record uid, or the page uid for a new record
     * @return array<int, string> labels by profile uid
     */
    private function offeredProfiles(int $uid, string $command = 'edit'): array
    {
        $items = $this->compile($uid, $command)['processedTca']['columns']['profile']['config']['items'] ?? [];
        $labels = [];
        foreach ($items as $item) {
            $item = is_array($item) ? $item : $item->toArray();
            $labels[(int)($item['value'] ?? 0)] = (string)($item['label'] ?? '');
        }
        ksort($labels);

        return $labels;
    }

    /**
     * @return array<string, mixed>
     */
    private function compile(int $uid, string $command): array
    {
        $request = (new ServerRequest('https://localhost/typo3/record/edit'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $GLOBALS['TYPO3_REQUEST'] = $request;

        return GeneralUtility::makeInstance(FormDataCompiler::class)->compile(
            [
                'request' => $request,
                'tableName' => self::TABLE,
                'vanillaUid' => $uid,
                'command' => $command,
            ],
            // Not `$this->get()`: on TYPO3 v12 `TcaDatabaseRecord` is not a public
            // service, so the test container cannot hand it over.
            GeneralUtility::makeInstance(TcaDatabaseRecord::class),
        );
    }
}
