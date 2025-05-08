<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Fgtclb\AcademicPersons\Tests\Functional\Hook;

use Fgtclb\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class DataHandlerHooksTest extends AbstractAcademicPersonsTestCase
{
    private DataHandler $dataHandler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/BeUsers.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/PageTree.csv');

        $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
        $this->dataHandler = GeneralUtility::makeInstance(DataHandler::class);
    }

    #[Test]
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

    #[Test]
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
