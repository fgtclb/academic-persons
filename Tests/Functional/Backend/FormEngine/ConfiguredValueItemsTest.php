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
 * The selects whose items come from a comma separated list of the extension
 * configuration, as an editor gets them when each list ends with a comma: the sort and
 * group fields of a persons list element, and the type of an email address, a phone
 * number and an address.
 *
 * Each field declares an empty item of its own, "none" or "undefined", with the value ''.
 * The trailing comma must not add a second one without a label. The data structure of
 * the FlexForm differs per core version, so the form is compiled against the installed
 * core, the way FormEngine compiles it when the record is opened.
 */
final class ConfiguredValueItemsTest extends AbstractAcademicPersonsTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ConfiguredValueItems/records.csv');
        $this->setUpBackendUser(1);
        $configuration = &$GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['academic_persons'];
        $configuration['demand']['allowedGroupByValues'] = 'lastNameAlpha=Last name,';
        $configuration['demand']['allowedSortByValues'] = 'firstName=First name,,lastName=Last name,';
        $configuration['types']['emailAddressTypes'] = 'private=Private,business=Business,';
        $configuration['types']['phoneNumberTypes'] = 'private=Private,,mobile=Mobile';
        $configuration['types']['physicalAddressTypes'] = ',private=Private';
    }

    /**
     * @param string[] $expectedValues
     */
    #[Test]
    #[DataProvider('listElementFields')]
    public function theListElementOffersNoSecondEmptyItem(string $field, array $expectedValues): void
    {
        $result = $this->compile('tt_content', 1);
        $items = $result['processedTca']['columns']['pi_flexform']['config']['ds']['sheets']['sDEF']['ROOT']['el'][$field]['config']['items'] ?? null;
        $this->assertIsArray($items);

        $this->assertSame($expectedValues, array_column($items, 'value'));
        $this->assertNotContains('', array_column($items, 'label'));
    }

    /**
     * @return array<string, array{0: string, 1: string[]}>
     */
    public static function listElementFields(): array
    {
        return [
            'group by' => ['settings.demand.groupBy', ['', 'lastNameAlpha']],
            'sort by' => ['settings.demand.sortBy', ['', 'firstName', 'lastName']],
        ];
    }

    /**
     * @param string[] $expectedValues
     */
    #[Test]
    #[DataProvider('typeFields')]
    public function theTypeSelectOffersNoSecondEmptyItem(string $table, array $expectedValues): void
    {
        $items = $this->compile($table, 1)['processedTca']['columns']['type']['config']['items'] ?? null;
        $this->assertIsArray($items);

        $this->assertSame($expectedValues, array_column($items, 'value'));
        $this->assertNotContains('', array_column($items, 'label'));
    }

    /**
     * @return array<string, array{0: string, 1: string[]}>
     */
    public static function typeFields(): array
    {
        return [
            'email address' => ['tx_academicpersons_domain_model_email', ['', 'private', 'business']],
            'phone number' => ['tx_academicpersons_domain_model_phone_number', ['', 'private', 'mobile']],
            'address' => ['tx_academicpersons_domain_model_address', ['', 'private']],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function compile(string $table, int $uid): array
    {
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
        $request = (new ServerRequest('https://localhost/typo3/record/edit'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $GLOBALS['TYPO3_REQUEST'] = $request;

        return GeneralUtility::makeInstance(FormDataCompiler::class)->compile(
            [
                'request' => $request,
                'tableName' => $table,
                'vanillaUid' => $uid,
                'command' => 'edit',
            ],
            $this->get(TcaDatabaseRecord::class),
        );
    }
}
