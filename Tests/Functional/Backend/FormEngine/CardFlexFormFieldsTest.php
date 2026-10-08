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
 * Compiles the backend form of the profile card the way FormEngine does when an
 * editor opens the content element, on a page that carries the page TSconfig of
 * the card component, and asserts which FlexForm fields it offers.
 *
 * The card links every profile to the detail view on `settings.detailPid`, so the
 * field has to be offered. The page TSconfig of the card disabled it together with
 * the list fields, which left the global constant
 * `plugin.tx_academicpersons.detailPid` as the only way to set it (ACE-833).
 *
 * The page TSconfig is included through `pages.tsconfig_includes`, the way it
 * reaches a page on TYPO3 v12 and from a static registration on v13. That it is
 * applied at all is asserted with a list field it keeps disabling.
 */
final class CardFlexFormFieldsTest extends AbstractAcademicPersonsTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/Fixtures/CardFlexFormFields/card.csv');
        $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
    }

    #[Test]
    public function cardOffersTheDetailPage(): void
    {
        $this->assertArrayHasKey('settings.detailPid', $this->offeredFields(1));
    }

    #[Test]
    public function cardKeepsTheListFieldsDisabled(): void
    {
        $fields = $this->offeredFields(1);

        $this->assertArrayHasKey('settings.demand.profileList', $fields);
        $this->assertArrayNotHasKey('settings.demand.sortBy', $fields);
        $this->assertArrayNotHasKey('settings.paginationEnabled', $fields);
    }

    /**
     * @return array<string, mixed> The fields of the sheet "sDEF", keyed by name.
     */
    private function offeredFields(int $contentUid): array
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
        $this->assertIsArray($fields, 'The FlexForm of the card has no sheet "sDEF".');

        return $fields;
    }
}
