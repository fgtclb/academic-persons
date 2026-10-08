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
 * The detail link choice in the plugin options, as an editor gets it: offered by the list,
 * the card and the two selections, empty by default, and not offered by the list-and-detail
 * element, which shares the FlexForm of the list and always links.
 *
 * The form is compiled the way FormEngine compiles it when the content element is opened,
 * see {@see ViewModeFieldsTest}.
 */
final class DetailLinkFieldTest extends AbstractAcademicPersonsTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DetailLinkField/records.csv');
        $this->setUpBackendUser(1);
    }

    /**
     * @return \Generator<string, array{0: int}>
     */
    public static function elementsWithTheChoiceDataProvider(): \Generator
    {
        yield 'list' => [1];
        yield 'selected profiles' => [3];
        yield 'selected contracts' => [4];
        yield 'card' => [5];
    }

    #[DataProvider('elementsWithTheChoiceDataProvider')]
    #[Test]
    public function theElementOffersTheChoiceWithTheSiteSettingFirst(int $contentUid): void
    {
        $this->assertSame(
            ['' => 'Use the site setting', 'link' => 'Link to the detail view', 'none' => 'No link'],
            $this->detailLinkItems($contentUid, 'default'),
        );
        $this->assertSame(
            ['' => 'Einstellung der Site verwenden', 'link' => 'Mit der Detailansicht verlinken', 'none' => 'Kein Link'],
            $this->detailLinkItems($contentUid, 'de'),
        );
    }

    /**
     * No value is selected, so the form shows the first item, "Use the site setting".
     */
    #[DataProvider('elementsWithTheChoiceDataProvider')]
    #[Test]
    public function theChoiceIsEmptyForAnElementSavedBeforeIt(int $contentUid): void
    {
        $value = $this->compile($contentUid, 'default')['databaseRow']['pi_flexform']['data']['sDEF']['lDEF']['settings.detailLink']['vDEF'] ?? null;

        $this->assertIsArray($value);
        $this->assertSame([], array_values(array_filter($value, static fn(mixed $item): bool => $item !== '')));
    }

    #[Test]
    public function theListAndDetailElementOffersNoChoice(): void
    {
        $this->assertArrayNotHasKey('settings.detailLink', $this->flexFormFields(2, 'default'));
        // The list shares the data structure and keeps it.
        $this->assertArrayHasKey('settings.detailLink', $this->flexFormFields(1, 'default'));
    }

    /**
     * The card keeps the choice and the detail page under the page TSconfig of its set: it
     * renders the names of its profiles like the list does, and links them to the detail
     * page of the element. Before ACE-861 that TSconfig hid the detail page, which left
     * the site setting as the only way to set it. That the TSconfig is applied at all is
     * shown by the list fields it keeps hiding.
     */
    #[Test]
    public function theCardKeepsTheChoiceAndTheDetailPageUnderThePageTsConfigOfItsSet(): void
    {
        $this->getConnectionPool()->getConnectionForTable('pages')->update(
            'pages',
            ['TSconfig' => "@import 'EXT:academic_persons/Configuration/TSconfig/Card/page.tsconfig'"],
            ['uid' => 1],
        );

        $fields = $this->flexFormFields(5, 'default');

        $this->assertArrayHasKey('settings.detailPid', $fields);
        $this->assertArrayHasKey('settings.detailLink', $fields);
        $this->assertArrayNotHasKey('settings.demand.sortBy', $fields);
        $this->assertArrayNotHasKey('settings.paginationEnabled', $fields);
    }

    /**
     * The items of the choice, value to label, in the order offered.
     *
     * @return array<string, string>
     */
    private function detailLinkItems(int $contentUid, string $language): array
    {
        $items = $this->flexFormFields($contentUid, $language)['settings.detailLink']['config']['items'] ?? null;
        $this->assertIsArray($items);
        $labels = [];
        foreach ($items as $item) {
            $item = is_array($item) ? $item : $item->toArray();
            $labels[(string)$item['value']] = (string)$item['label'];
        }

        return $labels;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function flexFormFields(int $contentUid, string $language): array
    {
        $fields = $this->compile($contentUid, $language)['processedTca']['columns']['pi_flexform']['config']['ds']['sheets']['sDEF']['ROOT']['el'] ?? null;
        $this->assertIsArray($fields);

        return $fields;
    }

    /**
     * @return array<string, mixed>
     */
    private function compile(int $contentUid, string $language): array
    {
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create($language);
        $request = (new ServerRequest('https://localhost/typo3/record/edit'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $GLOBALS['TYPO3_REQUEST'] = $request;

        return GeneralUtility::makeInstance(FormDataCompiler::class)->compile(
            [
                'request' => $request,
                'tableName' => 'tt_content',
                'vanillaUid' => $contentUid,
                'command' => 'edit',
            ],
            $this->get(TcaDatabaseRecord::class),
        );
    }
}
