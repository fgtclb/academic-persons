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
 * The view mode fields of the plugins as an editor gets them: the items of the default
 * view mode, their labels, an item a project adds through page TSconfig, and the card,
 * which hides both fields.
 *
 * The form is compiled the way FormEngine compiles it when the content element is opened,
 * because the page TSconfig of a FlexForm field only reaches it on that path - see
 * {@see ContractSelectStorageScopeTest}.
 */
final class ViewModeFieldsTest extends AbstractAcademicPersonsTestCase
{
    protected function setUp(): void
    {
        $this->addTestExtension('tests/test-profile-view-modes');
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ViewModeFields/records.csv');
        $this->setUpBackendUser(1);
    }

    /**
     * @return \Generator<string, array{0: int}>
     */
    public static function viewModeElementsDataProvider(): \Generator
    {
        yield 'list' => [1];
        yield 'list and detail' => [2];
        yield 'selected profiles' => [3];
        yield 'selected contracts' => [4];
    }

    /**
     * The stored value of the tile grid stays "list", so no content element needs a
     * migration; only its label says what it renders.
     */
    #[DataProvider('viewModeElementsDataProvider')]
    #[Test]
    public function theTileGridKeepsItsValueAndIsLabelledTiles(int $contentUid): void
    {
        $this->assertSame(['list' => 'Tiles', 'table' => 'Table'], $this->viewModeItems($contentUid, 'default'));
        $this->assertSame(['list' => 'Kacheln', 'table' => 'Tabelle'], $this->viewModeItems($contentUid, 'de'));
    }

    #[Test]
    public function aProjectAddsItsModeThroughPageTsConfig(): void
    {
        $this->setPageTsConfig("@import 'EXT:test_profile_view_modes/Configuration/TSconfig/ContactViewMode.tsconfig'");

        $this->assertSame(['list' => 'Tiles', 'table' => 'Table', 'contact' => 'Contact cards'], $this->viewModeItems(1, 'default'));
        $this->assertSame(['list' => 'Tiles', 'table' => 'Table', 'contact' => 'Contact cards'], $this->viewModeItems(2, 'default'));
    }

    /**
     * The card shares the FlexForm of the list; the page TSconfig its set delivers hides
     * both view mode fields.
     */
    #[Test]
    public function theCardOffersNoViewModeField(): void
    {
        $this->setPageTsConfig("@import 'EXT:academic_persons/Configuration/TSconfig/Card/page.tsconfig'");

        $fields = $this->flexFormFields(5, 'default');

        $this->assertArrayNotHasKey('settings.viewMode.enabled', $fields);
        $this->assertArrayNotHasKey('settings.viewMode.default', $fields);
        // The list keeps them under the same page TSconfig.
        $this->assertArrayHasKey('settings.viewMode.default', $this->flexFormFields(1, 'default'));
    }

    private function setPageTsConfig(string $tsConfig): void
    {
        $this->getConnectionPool()->getConnectionForTable('pages')->update('pages', ['TSconfig' => $tsConfig], ['uid' => 1]);
    }

    /**
     * The items of the default view mode, value to label, in the order offered.
     *
     * @return array<string, string>
     */
    private function viewModeItems(int $contentUid, string $language): array
    {
        $items = $this->flexFormFields($contentUid, $language)['settings.viewMode.default']['config']['items'] ?? null;
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
