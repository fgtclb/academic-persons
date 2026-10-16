<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Backend\FormEngine;

use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Backend\Form\FormDataGroup\TcaDatabaseRecord;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * The options of the shared FlexForm that do nothing in an element are not offered there
 * (ACE-923): the page title format only in the list-and-detail element, the only one that
 * renders a profile, the detail page everywhere but there, because it always links to its
 * own page, and the unit and function type restriction not in the card, which applies none.
 *
 * The data structure of the FlexForm differs per core version (`Core12/List.xml` and
 * `Core13/List.xml`), so this test runs against the one of the installed core. The form is
 * compiled the way FormEngine compiles it when the content element is opened, see
 * {@see CardFlexFormFieldsTest}.
 */
final class OptionsWithoutEffectTest extends AbstractAcademicPersonsTestCase
{
    private const LIST = 1;
    private const LIST_AND_DETAIL = 2;
    private const CARD = 3;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/OptionsWithoutEffect/records.csv');
        $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['LANG'], $GLOBALS['TYPO3_REQUEST']);
        parent::tearDown();
    }

    #[Test]
    public function onlyTheListAndDetailElementOffersThePageTitleFormat(): void
    {
        $this->assertArrayHasKey('settings.pageTitleFormat', $this->flexFormFields(self::LIST_AND_DETAIL));
        $this->assertArrayNotHasKey('settings.pageTitleFormat', $this->flexFormFields(self::LIST));
        $this->assertArrayNotHasKey('settings.pageTitleFormat', $this->flexFormFields(self::CARD));
    }

    #[Test]
    public function theListAndDetailElementOffersNoDetailPage(): void
    {
        $this->assertArrayNotHasKey('settings.detailPid', $this->flexFormFields(self::LIST_AND_DETAIL));
        $this->assertArrayHasKey('settings.detailPid', $this->flexFormFields(self::LIST));
        $this->assertArrayHasKey('settings.detailPid', $this->flexFormFields(self::CARD));
    }

    /**
     * The page TSconfig of the card hides the restriction. The list keeps both fields under
     * the same page TSconfig.
     */
    #[Test]
    public function theCardOffersNoUnitOrFunctionTypeRestriction(): void
    {
        $this->getConnectionPool()->getConnectionForTable('pages')->update(
            'pages',
            ['TSconfig' => "@import 'EXT:academic_persons/Configuration/TSconfig/Card/page.tsconfig'"],
            ['uid' => 1],
        );

        $cardFields = $this->flexFormFields(self::CARD);
        $listFields = $this->flexFormFields(self::LIST);

        foreach (['settings.organisationalUnits', 'settings.functionTypes'] as $field) {
            $this->assertArrayNotHasKey($field, $cardFields);
            $this->assertArrayHasKey($field, $listFields);
        }
    }

    /**
     * @return \Generator<string, array{0: int, 1: string}>
     */
    public static function hiddenStoredValuesDataProvider(): \Generator
    {
        yield 'page title format of the list' => [self::LIST, 'settings.pageTitleFormat'];
        yield 'page title format of the card' => [self::CARD, 'settings.pageTitleFormat'];
        yield 'detail page of the list and detail element' => [self::LIST_AND_DETAIL, 'settings.detailPid'];
    }

    /**
     * A save sends the fields the form shows. The value of a hidden field stays as stored, so
     * an element switched back to a type that offers it again finds it unchanged.
     */
    #[DataProvider('hiddenStoredValuesDataProvider')]
    #[Test]
    public function savingTheElementKeepsTheValueOfAHiddenField(int $contentUid, string $field): void
    {
        $before = $this->storedFlexFormValue($contentUid, $field);
        $this->assertNotSame('', $before);

        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([
            'tt_content' => [
                $contentUid => [
                    'pi_flexform' => [
                        'data' => ['sDEF' => ['lDEF' => ['settings.paginationEnabled' => ['vDEF' => '1']]]],
                    ],
                ],
            ],
        ], []);
        $dataHandler->process_datamap();

        $this->assertSame([], $dataHandler->errorLog, 'The DataHandler run reported errors.');
        $this->assertSame('1', $this->storedFlexFormValue($contentUid, 'settings.paginationEnabled'));
        $this->assertSame($before, $this->storedFlexFormValue($contentUid, $field));
    }

    private function storedFlexFormValue(int $contentUid, string $field): string
    {
        $row = $this->getConnectionPool()->getConnectionForTable('tt_content')
            ->select(['pi_flexform'], 'tt_content', ['uid' => $contentUid])
            ->fetchAssociative();
        $this->assertIsArray($row);
        $flexForm = GeneralUtility::xml2array((string)$row['pi_flexform']);
        $this->assertIsArray($flexForm);

        return (string)($flexForm['data']['sDEF']['lDEF'][$field]['vDEF'] ?? '');
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function flexFormFields(int $contentUid): array
    {
        $request = (new ServerRequest('https://localhost/typo3/record/edit'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $GLOBALS['TYPO3_REQUEST'] = $request;

        $result = GeneralUtility::makeInstance(FormDataCompiler::class)->compile(
            [
                'request' => $request,
                'tableName' => 'tt_content',
                'vanillaUid' => $contentUid,
                'command' => 'edit',
            ],
            // Not `$this->get()`: on TYPO3 v12 `TcaDatabaseRecord` is not a public
            // service, so the test container cannot hand it over.
            GeneralUtility::makeInstance(TcaDatabaseRecord::class),
        );
        $fields = $result['processedTca']['columns']['pi_flexform']['config']['ds']['sheets']['sDEF']['ROOT']['el'] ?? null;
        $this->assertIsArray($fields);

        return $fields;
    }
}
