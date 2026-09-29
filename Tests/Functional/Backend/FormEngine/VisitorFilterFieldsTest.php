<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Backend\FormEngine;

use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Backend\Form\FormDataGroup\TcaDatabaseRecord;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * The two options that let visitors filter the persons list, as an editor gets them: in
 * the list and list-and-detail elements, off until switched on, labelled in English and
 * German, and hidden in the card.
 *
 * The data structure of the FlexForm differs per core version (`Core13/List.xml` and
 * `Core14/List.xml`), so this test runs against the one of the installed core. The form is
 * compiled the way FormEngine compiles it when the content element is opened, because the
 * page TSconfig of a FlexForm field only reaches it on that path - see
 * {@see ContractSelectStorageScopeTest}.
 */
final class VisitorFilterFieldsTest extends AbstractAcademicPersonsTestCase
{
    private const FIELDS = ['settings.filter.functionType', 'settings.filter.organisationalUnit'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/VisitorFilterFields/records.csv');
        $this->setUpBackendUser(1);
    }

    /**
     * @return \Generator<string, array{0: int}>
     */
    public static function listElementsDataProvider(): \Generator
    {
        yield 'list' => [1];
        yield 'list and detail' => [2];
    }

    #[DataProvider('listElementsDataProvider')]
    #[Test]
    public function theListElementsOfferBothFiltersSwitchedOff(int $contentUid): void
    {
        $fields = $this->flexFormFields($contentUid, 'default');

        foreach (self::FIELDS as $field) {
            $this->assertArrayHasKey($field, $fields);
            $this->assertSame('check', $fields[$field]['config']['type'] ?? null);
            $this->assertSame(0, (int)($fields[$field]['config']['default'] ?? null));
        }
    }

    /**
     * @return \Generator<string, array{0: string, 1: array<string, string>}>
     */
    public static function labelsDataProvider(): \Generator
    {
        yield 'English' => ['default', [
            'settings.filter.functionType' => 'Visitors may filter by function type',
            'settings.filter.organisationalUnit' => 'Visitors may filter by organisational unit',
        ]];
        yield 'German' => ['de', [
            'settings.filter.functionType' => 'Besucher dürfen nach Funktionstyp filtern',
            'settings.filter.organisationalUnit' => 'Besucher dürfen nach Organisationseinheit filtern',
        ]];
    }

    /**
     * @param array<string, string> $labels
     */
    #[DataProvider('labelsDataProvider')]
    #[Test]
    public function bothFiltersAreLabelled(string $language, array $labels): void
    {
        $fields = $this->flexFormFields(1, $language);

        foreach ($labels as $field => $label) {
            $this->assertSame($label, $fields[$field]['label'] ?? null);
            $this->assertNotSame('', trim((string)($fields[$field]['description'] ?? '')));
        }
    }

    /**
     * The card shares the FlexForm of the list, and the page TSconfig its set delivers
     * hides both filter options.
     */
    #[Test]
    public function theCardOffersNoFilter(): void
    {
        $this->getConnectionPool()->getConnectionForTable('pages')->update(
            'pages',
            ['TSconfig' => "@import 'EXT:academic_persons/Configuration/TSconfig/Card/page.tsconfig'"],
            ['uid' => 1],
        );

        $cardFields = $this->flexFormFields(3, 'default');
        $listFields = $this->flexFormFields(1, 'default');

        foreach (self::FIELDS as $field) {
            $this->assertArrayNotHasKey($field, $cardFields);
            // The list keeps them under the same page TSconfig.
            $this->assertArrayHasKey($field, $listFields);
        }
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function flexFormFields(int $contentUid, string $language): array
    {
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create($language);
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
            $this->get(TcaDatabaseRecord::class),
        );
        $fields = $result['processedTca']['columns']['pi_flexform']['config']['ds']['sheets']['sDEF']['ROOT']['el'] ?? null;
        $this->assertIsArray($fields);

        return $fields;
    }
}
