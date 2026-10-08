<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\DataHandling;

use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Localizing an organisational unit localizes none of its contracts (ACE-874).
 *
 * A contract belongs to its profile and is translated with it. The organisational unit
 * lists the contracts of its people as an inline relation all the same, and
 * `DataHandler::localize()` copies every inline child of a localized record: a
 * translation of a contract in the default language, one more copy of a contract that
 * is valid in all languages or in the target language already. The profile then showed
 * the contract twice, which an installation reported for a profile valid in all
 * languages on TYPO3 v13.4.35 with version 2.3.4.
 *
 * Profile 1 is valid in all languages and holds contract 1 in unit 1, with an email
 * address, and contract 2 in unit 2.
 */
final class OrganisationalUnitLocalizationTest extends AbstractAcademicPersonsTestCase
{
    use SiteBasedTestTrait;

    protected array $coreExtensionsToLoad = [
        'typo3/cms-install',
        'typo3/cms-rte-ckeditor',
    ];

    protected const LANGUAGE_PRESETS = [
        'DE' => ['id' => 0, 'title' => 'Deutsch', 'locale' => 'de_DE.UTF8', 'iso' => 'de', 'hrefLang' => 'de-DE', 'direction' => ''],
        'EN' => ['id' => 1, 'title' => 'English', 'locale' => 'en_GB.UTF8', 'iso' => 'en', 'hrefLang' => 'en-GB', 'direction' => ''],
    ];

    private const TABLE_UNIT = 'tx_academicpersons_domain_model_organisational_unit';
    private const TABLE_CONTRACT = 'tx_academicpersons_domain_model_contract';
    private const TABLE_EMAIL = 'tx_academicpersons_domain_model_email';

    protected function setUp(): void
    {
        parent::setUp();
        $this->writeSiteConfiguration(
            identifier: 'organisational-unit-localization-test',
            site: $this->buildSiteConfiguration(1, 'https://www.acme.com/'),
            languages: [
                $this->buildDefaultLanguageConfiguration(identifier: 'DE', base: '/'),
                $this->buildLanguageConfiguration(identifier: 'EN', base: '/en/', fallbackIdentifiers: ['DE']),
            ],
        );
        $this->importCSVDataSet(__DIR__ . '/Fixtures/OrganisationalUnitLocalization/profileWithContracts.csv');
        $this->setUpBackendUser(1);
        // The DataHandler reads the language service from $GLOBALS['LANG'].
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['BE_USER'], $GLOBALS['LANG']);
        GeneralUtility::rmdir($this->instancePath . '/typo3conf/sites', true);
        parent::tearDown();
    }

    /**
     * The language of contract 1 before unit 1 is localized.
     *
     * @return array<string, array{0: int}>
     */
    public static function contractLanguageProvider(): array
    {
        return [
            'contract in the default language' => [0],
            'contract valid in all languages' => [-1],
            'contract in the target language' => [1],
        ];
    }

    #[Test]
    #[DataProvider('contractLanguageProvider')]
    public function localizingAUnitLeavesTheContractsOfTheProfileAlone(int $contractLanguage): void
    {
        $this->setContractLanguage($contractLanguage);

        $unitTranslationUid = $this->runCommand([self::TABLE_UNIT => [1 => ['localize' => 1]]], self::TABLE_UNIT, 1);

        $this->assertGreaterThan(2, $unitTranslationUid);
        $this->assertSame($this->expectedContracts($contractLanguage), $this->fetchRows(self::TABLE_CONTRACT));
        $this->assertSame([], $this->fetchCreatedRows(self::TABLE_CONTRACT, 2), 'A contract was created and left behind.');
        $this->assertSame([], $this->fetchCreatedRows(self::TABLE_EMAIL, 1), 'An email address of a contract was localized and left behind.');
    }

    /**
     * Saving the unit translation, once and again, creates no contract either.
     */
    #[Test]
    #[DataProvider('contractLanguageProvider')]
    public function savingTheLocalizedUnitAgainCreatesNoContract(int $contractLanguage): void
    {
        $this->setContractLanguage($contractLanguage);
        $unitTranslationUid = $this->runCommand([self::TABLE_UNIT => [1 => ['localize' => 1]]], self::TABLE_UNIT, 1);

        $this->saveUnit($unitTranslationUid, 'Competence Center of Health (CCG)');
        $this->saveUnit($unitTranslationUid, 'Competence Center for Health (CCG)');

        $this->assertSame($this->expectedContracts($contractLanguage), $this->fetchRows(self::TABLE_CONTRACT));
        $this->assertSame([], $this->fetchCreatedRows(self::TABLE_CONTRACT, 2));
    }

    #[Test]
    public function copyingAUnitToALanguageLeavesTheContractsAlone(): void
    {
        $unitCopyUid = $this->runCommand([self::TABLE_UNIT => [1 => ['copyToLanguage' => 1]]], self::TABLE_UNIT, 1);

        $this->assertGreaterThan(2, $unitCopyUid);
        $this->assertSame($this->expectedContracts(0), $this->fetchRows(self::TABLE_CONTRACT));
        $this->assertSame([], $this->fetchCreatedRows(self::TABLE_CONTRACT, 2));
    }

    /**
     * A plain copy of a unit in its own language is not a localization and keeps copying
     * the contracts it lists, as before.
     */
    #[Test]
    public function copyingAUnitStillCopiesItsContracts(): void
    {
        $unitCopyUid = $this->runCommand([self::TABLE_UNIT => [1 => ['copy' => 100]]], self::TABLE_UNIT, 1);

        $this->assertGreaterThan(2, $unitCopyUid);
        $this->assertNotSame([], $this->fetchCreatedRows(self::TABLE_CONTRACT, 2));
    }

    private function setContractLanguage(int $languageUid): void
    {
        $this->getConnectionPool()->getConnectionForTable(self::TABLE_CONTRACT)
            ->update(self::TABLE_CONTRACT, ['sys_language_uid' => $languageUid], ['uid' => 1]);
    }

    /**
     * @return list<array<string, int>>
     */
    private function expectedContracts(int $contractLanguage): array
    {
        return [
            ['uid' => 1, 'sys_language_uid' => $contractLanguage, 'l10n_parent' => 0, 'profile' => 1, 'organisational_unit' => 1],
            ['uid' => 2, 'sys_language_uid' => 0, 'l10n_parent' => 0, 'profile' => 1, 'organisational_unit' => 2],
        ];
    }

    /**
     * @param array<string, array<int, array<string, int|string>>> $commandMap
     * @return int The uid the record was copied or localized to
     */
    private function runCommand(array $commandMap, string $tableName, int $uid): int
    {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], $commandMap);
        $dataHandler->process_cmdmap();
        $this->assertSame([], $dataHandler->errorLog, 'The DataHandler run reported errors.');

        return (int)($dataHandler->copyMappingArray_merged[$tableName][$uid] ?? 0);
    }

    private function saveUnit(int $uid, string $unitName): void
    {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([self::TABLE_UNIT => [$uid => ['unit_name' => $unitName]]], []);
        $dataHandler->process_datamap();
        $this->assertSame([], $dataHandler->errorLog, 'The DataHandler run reported errors.');
    }

    /**
     * @return list<array<string, int>> The contracts that are not deleted, in uid order.
     */
    private function fetchRows(string $tableName): array
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable($tableName);
        $queryBuilder->getRestrictions()->removeAll();
        $rows = $queryBuilder
            ->select('uid', 'sys_language_uid', 'l10n_parent', 'profile', 'organisational_unit')
            ->from($tableName)
            ->where($queryBuilder->expr()->eq('deleted', 0))
            ->orderBy('uid')
            ->executeQuery()
            ->fetchAllAssociative();
        return array_map(
            static fn(array $row): array => array_map(intval(...), $row),
            $rows,
        );
    }

    /**
     * The guard removes a created record with a soft delete on this branch, so a removed
     * one stays in the table as deleted.
     *
     * @return list<int> The uids of the rows created after the fixture that are not
     *         deleted
     */
    private function fetchCreatedRows(string $tableName, int $lastFixtureUid): array
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable($tableName);
        $queryBuilder->getRestrictions()->removeAll();
        return array_map(intval(...), $queryBuilder
            ->select('uid')
            ->from($tableName)
            ->where(
                $queryBuilder->expr()->gt('uid', $lastFixtureUid),
                $queryBuilder->expr()->eq('deleted', 0),
            )
            ->orderBy('uid')
            ->executeQuery()
            ->fetchFirstColumn());
    }
}
