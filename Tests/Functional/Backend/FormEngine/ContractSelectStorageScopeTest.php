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
 * Compiles the backend form of the "selected contracts" plugin the way FormEngine does
 * when an editor opens the content element, and asserts which contracts its select
 * offers.
 *
 * The field is a FlexForm element, which is the reason the form is compiled rather than
 * `ContractItems::itemsProcFunc()` called directly: the page TSconfig a FlexForm field
 * receives travels a path of its own. It has to hold on both supported core versions,
 * which ship a data structure file each (`Core12/` and `Core13/`) registered through the
 * same `addPiFlexFormValue('*', …, $cType)` call, so the identifier is the CType either
 * way. `TcaFlexProcess` compiles every sheet through a
 * nested `FormDataCompiler` and hands it the sheet's subtree of
 * `TCEFORM.tt_content.pi_flexform.<identifier>.` as the TSconfig of the parent table, so
 * the setting is only found under the full path this test writes. Calling the handler
 * with a hand-built parameter array would assert the path this change believes in
 * instead of the one FormEngine reads.
 *
 * The fixture keeps the contracts apart by their storage page: contracts 1 and 4 on the
 * listed folder, contract 3 in its **hidden** subfolder, contract 2 on an unrelated one.
 * Their persons are named so that the order of the offered items - by last name, in PHP -
 * is neither the uid order nor its reverse.
 */
final class ContractSelectStorageScopeTest extends AbstractAcademicPersonsTestCase
{
    private const FLEX_FIELD = 'settings.selectedContracts';
    private const STORAGE_ONE = 20;

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/Fixtures/ContractSelectStorageScope/contracts.csv');
        $connection = $this->getConnectionPool()->getConnectionForTable('pages');
        $connection->update(
            'pages',
            ['TSconfig' => $this->flexFormTsConfig(['storagePids = ' . self::STORAGE_ONE])],
            ['uid' => 41],
        );
        $connection->update(
            'pages',
            [
                'TSconfig' => $this->flexFormTsConfig([
                    'storagePids = ' . self::STORAGE_ONE,
                    'recursive = 1',
                ]),
            ],
            ['uid' => 42],
        );
        $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
    }

    #[Test]
    public function everyContractIsOfferedWithoutTheSetting(): void
    {
        $this->assertSame([1, 2, 3, 4], $this->offeredContractUids(1));
    }

    /**
     * The items are ordered by the person's last name in `ContractItems`, over a result
     * the repository ordered by `uid` - Abel (2), Meyer (3), then Zeller, whose two
     * contracts 1 and 4 keep the repository's order because PHP sorts stably. Contract 4
     * has the *lower* `sorting` of the two, so this asserts `uid` and not the table's
     * manual order.
     *
     * This content element has no page TSconfig, so it goes through the unrestricted
     * early return and pins `findAll()`. The restricted query is pinned by
     * `aRestrictedSelectKeepsThatOrder()` below.
     */
    #[Test]
    public function contractsAreOfferedOrderedByTheirPersonsName(): void
    {
        $this->assertSame([2, 3, 1, 4], $this->offeredContractUidsInOrder(1));
    }

    /**
     * The restriction must not reorder what it does not remove.
     *
     * Dropping the `ORDER BY` of `findForBackendSelect()` leaves this test **green on
     * SQLite** and reddens it on PostgreSQL - the ACE-491 shape, where uid order is
     * SQLite's natural order and PostgreSQL's is whatever its planner picks. A green
     * local `-d sqlite` run therefore does not say the ordering is covered; the
     * PostgreSQL jobs of the pipeline do.
     */
    #[Test]
    public function aRestrictedSelectKeepsThatOrder(): void
    {
        $this->assertSame([2, 1, 4], $this->offeredContractUidsInOrder(2));
    }

    #[Test]
    public function onlyContractsOfTheListedPageAreOffered(): void
    {
        $this->assertNotContains(3, $this->offeredContractUids(2));
    }

    /**
     * The listed folder's subfolder is hidden in the fixture, which is the common shape
     * of a storage folder and the reason the page list is expanded with the enable
     * field check bypassed.
     */
    #[Test]
    public function contractsOfASubFolderAreOfferedWithADepth(): void
    {
        $this->assertSame([1, 3, 4], $this->offeredContractUids(3));
    }

    /**
     * Without this, `AbstractItemProvider::processSelectFieldValue()` drops the value
     * from the row - it keeps only values that are among the items - and the next save
     * of the content element removes the contract from the plugin.
     */
    #[Test]
    public function aSelectedContractOutsideTheListedPageStaysOffered(): void
    {
        $this->assertSame([1, 2, 4], $this->offeredContractUids(2));
    }

    #[Test]
    public function aSelectedContractOutsideTheListedPageStaysSelected(): void
    {
        $this->assertSame('1,2', $this->selectedContracts(2));
    }

    /**
     * @return int[]
     */
    private function offeredContractUids(int $contentUid): array
    {
        $uids = $this->offeredContractUidsInOrder($contentUid);
        sort($uids);

        return $uids;
    }

    /**
     * @return int[]
     */
    private function offeredContractUidsInOrder(int $contentUid): array
    {
        $items = $this->compile($contentUid)['processedTca']['columns']['pi_flexform']['config']['ds']
            ['sheets']['sDEF']['ROOT']['el'][self::FLEX_FIELD]['config']['items'] ?? [];
        $uids = array_map(
            static fn(array $item): int => (int)($item['value'] ?? 0),
            array_map(
                static fn($item): array => is_array($item) ? $item : $item->toArray(),
                array_values($items),
            ),
        );

        return array_values(array_filter($uids, static fn(int $uid): bool => $uid > 0));
    }

    private function selectedContracts(int $contentUid): string
    {
        $row = $this->compile($contentUid)['databaseRow']['pi_flexform']['data']['sDEF']['lDEF'][self::FLEX_FIELD]['vDEF'] ?? null;

        return is_array($row) ? implode(',', $row) : (string)$row;
    }

    /**
     * @return array<string, mixed>
     */
    private function compile(int $contentUid): array
    {
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
            // Not `$this->get()`: on TYPO3 v12 `TcaDatabaseRecord` is not a public
            // service, so the test container cannot hand it over.
            GeneralUtility::makeInstance(TcaDatabaseRecord::class),
        );
    }

    /**
     * @param string[] $lines
     */
    private function flexFormTsConfig(array $lines): string
    {
        return implode("\n", [
            'TCEFORM.tt_content.pi_flexform.academicpersons_selectedcontracts.sDEF.settings\\.selectedContracts.itemsProcFunc {',
            ...array_map(static fn(string $line): string => '  ' . $line, $lines),
            '}',
        ]);
    }
}
