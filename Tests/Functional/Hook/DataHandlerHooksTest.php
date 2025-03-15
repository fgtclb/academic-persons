<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Fgtclb\AcademicPersons\Tests\Functional\Hook;

use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class DataHandlerHooksTest extends FunctionalTestCase
{
    private DataHandler $dataHandler;

    /**
     * @var list<non-empty-string>
     */
    protected array $testExtensionsToLoad = [
        'typo3conf/ext/academic_persons',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/BeUsers.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/PageTree.csv');

        $this->setUpBackendUser(1);

        if ((new Typo3Version())->getMajorVersion() >= 12) {
            $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
        } else {
            // @todo Drop this branch when dropping TYPO3 v11 suport in 2.x.x
            $GLOBALS['LANG'] = GeneralUtility::makeInstance(LanguageService::class);
            $GLOBALS['LANG']->init('en');
        }

        $this->dataHandler = GeneralUtility::makeInstance(DataHandler::class);
    }

    /**
     * @test
     */
    public function insertingNewRecordWillGenerateAndSaveAlphaFieldValues(): void
    {
        $dataMap = [
            'tx_academicpersons_domain_model_profile' => [
                'NEW1690182935' => [
                    'pid' => 2,
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                ],
            ],
        ];

        $this->dataHandler->start($dataMap, []);
        $this->dataHandler->process_datamap();

        $records = $this->getAllRecords('tx_academicpersons_domain_model_profile', true);

        $this->assertCount(1, $records);
        $this->assertSame('j', $records[1]['first_name_alpha']);
        $this->assertSame('d', $records[1]['last_name_alpha']);
    }

    /**
     * @test
     */
    public function updatingRecordWillGenerateAndSaveNewAlphaFieldValues(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/MinimumProfile.csv');

        $dataMap = [
            'tx_academicpersons_domain_model_profile' => [
                '1' => [
                    'pid' => 2,
                    'first_name' => 'Johnny',
                    'last_name' => 'English',
                ],
            ],
        ];

        $this->dataHandler->start($dataMap, []);
        $this->dataHandler->process_datamap();

        $records = $this->getAllRecords('tx_academicpersons_domain_model_profile', true);

        $this->assertCount(1, $records);
        $this->assertSame('j', $records[1]['first_name_alpha']);
        $this->assertSame('e', $records[1]['last_name_alpha']);
    }
}
