<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersons\Tests\Functional\DataHandling;

use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * The postcode and the street number of an address are text (ACE-841).
 *
 * The shipped validation settings listed the `number` flag for both, which
 * merged a TCA `number` type over the `input` the table file declares. The
 * DataHandler then stored an integer: `01067` became `1067`, `12a` became `12`.
 * The frontend half, the input type of the editing form, is covered in
 * `academic_persons_edit`.
 */
final class AddressTextFieldsTest extends AbstractAcademicPersonsTestCase
{
    private const TABLE_ADDRESS = 'tx_academicpersons_domain_model_address';

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/BeUsers.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/PageTree.csv');
        $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
    }

    #[Test]
    public function postcodeAndStreetNumberAreRequiredTextFields(): void
    {
        $columns = $GLOBALS['TCA'][self::TABLE_ADDRESS]['columns'];

        foreach (['zip', 'street_number'] as $columnName) {
            $this->assertSame('input', $columns[$columnName]['config']['type'], $columnName);
            $this->assertTrue($columns[$columnName]['config']['required'], $columnName);
        }
    }

    #[Test]
    public function postcodeAndStreetNumberAreStoredAsEntered(): void
    {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(
            [
                self::TABLE_ADDRESS => [
                    'NEW1' => [
                        'pid' => 2,
                        'street' => 'Bahnhofstrasse',
                        'street_number' => '12a',
                        'zip' => '01067',
                        'city' => 'Dresden',
                        'country' => 'Germany',
                    ],
                    'NEW2' => [
                        'pid' => 2,
                        'street' => 'Downing Street',
                        'street_number' => '10',
                        'zip' => 'SW1A 2AA',
                        'city' => 'London',
                        'country' => 'United Kingdom',
                    ],
                ],
            ],
            [],
        );
        $dataHandler->process_datamap();

        $this->assertSame([], $dataHandler->errorLog);
        $rows = $this->getConnectionPool()->getConnectionForTable(self::TABLE_ADDRESS)
            ->select(['street_number', 'zip'], self::TABLE_ADDRESS, [], [], ['uid' => 'ASC'])
            ->fetchAllAssociative();
        $this->assertSame(
            [
                ['street_number' => '12a', 'zip' => '01067'],
                ['street_number' => '10', 'zip' => 'SW1A 2AA'],
            ],
            $rows,
        );
    }
}
