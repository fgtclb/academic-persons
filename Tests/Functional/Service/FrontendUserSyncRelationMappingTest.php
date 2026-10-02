<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Service;

use FGTCLB\AcademicPersons\Domain\Model\Dto\ProfileCreateCommandDto;
use FGTCLB\AcademicPersons\Domain\Model\Dto\ProfileUpdateCommandDto;
use FGTCLB\AcademicPersons\Service\ProfileCreateCommandService;
use FGTCLB\AcademicPersons\Service\ProfileUpdateCommandService;
use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * The synchronisation of the organisational unit and the function type of the
 * imported contract. `test_frontend_user_sync_relations` matches the unit by
 * the unique name in `company` and creates a missing one on page 7, and the
 * function type by the name in a column of its own, without creating one.
 *
 * The fixtures make every lookup rule observable on some database: a deleted
 * unit and a new record of a workspace carry the searched unique name at a
 * lower uid than the live unit, a translation is the only record carrying
 * another one, a hidden unit and a unit for all languages are the only match
 * of their names, and the searched function name is carried by a lower-case
 * record, then by two records written in descending uid order.
 * {@see FrontendUserSyncRelationMatchByNameTest} covers the other map: units
 * matched by name, and function types created.
 * PostgreSQL returns a tie in the order the rows were written and MySQL and
 * MariaDB compare without case, so the uid order and the exact comparison can
 * each fail there. SQLite returns the uid order and compares with case, and
 * cannot fail either of them.
 */
final class FrontendUserSyncRelationMappingTest extends AbstractAcademicPersonsTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    private const UNIT_TABLE = 'tx_academicpersons_domain_model_organisational_unit';
    private const FUNCTION_TYPE_TABLE = 'tx_academicpersons_domain_model_function_type';
    private const CONTRACT_TABLE = 'tx_academicpersons_domain_model_contract';

    protected function setUp(): void
    {
        $this->addCoreExtension('typo3/cms-fluid-styled-content');
        $this->addTestExtension('tests/test-frontend-user-sync-relations');
        ArrayUtility::mergeRecursiveWithOverrule(
            $this->configurationToUseInTestInstance,
            [
                'EXTENSIONS' => [
                    'academic_persons' => [
                        'profile' => [
                            'autoCreateProfiles' => 1,
                            'createProfileForUserGroups' => '',
                        ],
                    ],
                ],
            ]
        );
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/FrontendUserSyncRelationMapping/records.csv');
        $this->setUpFrontendRootPage(
            pageId: 1,
            typoScriptFiles: [
                'constants' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript',
                    'EXT:academic_persons/Configuration/TypoScript/Default/constants.typoscript',
                ],
                'setup' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript',
                    'EXT:academic_persons/Configuration/TypoScript/Default/setup.typoscript',
                    'EXT:academic_persons/Tests/Functional/Service/ProfileCreateCommandService/Fixtures/TypoScript/Setup/setup.typoscript',
                ],
            ],
        );
        $this->writeSiteConfiguration(
            identifier: 'people',
            site: $this->buildSiteConfiguration(rootPageId: 1, base: 'https://people.acme.com/'),
            languages: [$this->buildDefaultLanguageConfiguration('EN', '/')],
        );
    }

    /**
     * Two new users of unit "X", which exists only as a deleted record: the
     * first one creates it on page 7, the second one gets the same unit. The
     * function type "Professor" resolves to the lowest uid carrying exactly
     * that name, and nothing creates a function type.
     */
    #[Test]
    public function createProfilesCreatesAMissingUnitOnItsStoragePageAndAssignsItToEveryUser(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/FrontendUserSyncRelationMapping/frontend-users-without-profile.csv');

        GeneralUtility::makeInstance(ProfileCreateCommandService::class)
            ->execute(new ProfileCreateCommandDto(includePids: [200], excludePids: []));

        $units = $this->unitsNamed('X');
        $this->assertCount(2, $units);
        $this->assertSame(['uid' => 5, 'pid' => 7, 'deleted' => 1, 'hidden' => 0, 'sys_language_uid' => 0, 'unit_name' => 'Deleted unit'], $units[0]);
        $createdUid = $units[1]['uid'];
        $this->assertSame(['pid' => 7, 'deleted' => 0, 'hidden' => 0, 'sys_language_uid' => 0, 'unit_name' => 'X'], array_diff_key($units[1], ['uid' => true]));
        // The slug a save would generate, so the filter routes of the list reach the unit.
        $this->assertSame(
            'x',
            $this->getConnectionPool()->getConnectionForTable(self::UNIT_TABLE)->select(['slug'], self::UNIT_TABLE, ['uid' => $createdUid])->fetchOne(),
        );
        $this->assertSame(
            [
                'fe_users:70' => ['organisational_unit' => $createdUid, 'function_type' => 11],
                'fe_users:71' => ['organisational_unit' => $createdUid, 'function_type' => 0],
            ],
            $this->relationsOfImportedContracts(),
        );
        $this->assertSame(3, $this->countRows(self::FUNCTION_TYPE_TABLE));
    }

    /**
     * The next run finds the unit the first one created and assigns it again,
     * without a second record.
     */
    #[Test]
    public function aSecondRunReusesTheCreatedUnit(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/FrontendUserSyncRelationMapping/frontend-users-without-profile.csv');
        GeneralUtility::makeInstance(ProfileCreateCommandService::class)
            ->execute(new ProfileCreateCommandDto(includePids: [200], excludePids: []));
        $createdUid = $this->unitsNamed('X')[1]['uid'];

        GeneralUtility::makeInstance(ProfileUpdateCommandService::class)
            ->execute(new ProfileUpdateCommandDto(includePids: [200], excludePids: []));

        $this->assertSame([5, $createdUid], array_column($this->unitsNamed('X'), 'uid'));
        $this->assertSame(
            [
                'fe_users:70' => ['organisational_unit' => $createdUid, 'function_type' => 11],
                'fe_users:71' => ['organisational_unit' => $createdUid, 'function_type' => 0],
            ],
            $this->relationsOfImportedContracts(),
        );
    }

    /**
     * "CHEM" is the unique name of a new record of a workspace and of the
     * live unit 21, which is the one assigned. The function type column is
     * empty now, so the function type an earlier run assigned is cleared, and
     * the employee type an editor chose stays.
     */
    #[Test]
    public function updateProfilesAssignsTheLiveUnitAndClearsAnEmptiedFunctionType(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/FrontendUserSyncRelationMapping/frontend-users-with-profile.csv');

        GeneralUtility::makeInstance(ProfileUpdateCommandService::class)
            ->execute(new ProfileUpdateCommandDto(includePids: [200], excludePids: []));

        $this->assertSame(
            ['organisational_unit' => 21, 'function_type' => 0, 'employee_type' => 1],
            $this->contractRelations(80),
        );
        $this->assertSame([4, 21], array_column($this->unitsNamed('CHEM'), 'uid'));
    }

    /**
     * "CHEMIE" is the unique name of the German translation of unit 21 only.
     * A translation never matches, so a unit of the default language is
     * created for it, and the translation stays as it is.
     */
    #[Test]
    public function updateProfilesCreatesAUnitForAValueOnlyATranslationCarries(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/FrontendUserSyncRelationMapping/frontend-users-with-profile.csv');

        GeneralUtility::makeInstance(ProfileUpdateCommandService::class)
            ->execute(new ProfileUpdateCommandDto(includePids: [200], excludePids: []));

        $units = $this->unitsNamed('CHEMIE');
        $this->assertCount(2, $units);
        $this->assertSame(['uid' => 20, 'pid' => 7, 'deleted' => 0, 'hidden' => 0, 'sys_language_uid' => 1, 'unit_name' => 'Chemie'], $units[0]);
        $this->assertSame(['pid' => 7, 'deleted' => 0, 'hidden' => 0, 'sys_language_uid' => 0, 'unit_name' => 'CHEMIE'], array_diff_key($units[1], ['uid' => true]));
        $this->assertSame(
            ['organisational_unit' => $units[1]['uid'], 'function_type' => 0, 'employee_type' => 0],
            $this->contractRelations(84),
        );
    }

    /**
     * A hidden unit is found rather than duplicated. The function type
     * "Rector" does not exist and creation is off for function types: the
     * relation is cleared and no record is created.
     */
    #[Test]
    public function updateProfilesAssignsAHiddenUnitAndClearsAFunctionTypeThatDoesNotExist(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/FrontendUserSyncRelationMapping/frontend-users-with-profile.csv');

        GeneralUtility::makeInstance(ProfileUpdateCommandService::class)
            ->execute(new ProfileUpdateCommandDto(includePids: [200], excludePids: []));

        $this->assertSame(
            ['organisational_unit' => 30, 'function_type' => 0, 'employee_type' => 1],
            $this->contractRelations(81),
        );
        $this->assertSame([30], array_column($this->unitsNamed('HIDDEN'), 'uid'));
        $this->assertSame(3, $this->countRows(self::FUNCTION_TYPE_TABLE));
    }

    /**
     * A unit for all languages carries the language -1, and matches like one
     * of the default language.
     */
    #[Test]
    public function updateProfilesAssignsAUnitForAllLanguages(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/FrontendUserSyncRelationMapping/frontend-users-with-profile.csv');

        GeneralUtility::makeInstance(ProfileUpdateCommandService::class)
            ->execute(new ProfileUpdateCommandDto(includePids: [200], excludePids: []));

        $this->assertSame(
            ['organisational_unit' => 50, 'function_type' => 0, 'employee_type' => 0],
            $this->contractRelations(85),
        );
        $this->assertSame([50], array_column($this->unitsNamed('ALL'), 'uid'));
    }

    /**
     * Frontend user 82 has two profiles, and both are written before the run
     * persists them. The unit "TWIN" is created for the first one and found
     * for the second one.
     */
    #[Test]
    public function updateProfilesCreatesOneUnitForTwoProfilesOfOneUser(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/FrontendUserSyncRelationMapping/frontend-users-with-profile.csv');

        GeneralUtility::makeInstance(ProfileUpdateCommandService::class)
            ->execute(new ProfileUpdateCommandDto(includePids: [200], excludePids: []));

        $units = $this->unitsNamed('TWIN');
        $this->assertCount(1, $units);
        $this->assertSame(['pid' => 7, 'deleted' => 0, 'hidden' => 0, 'sys_language_uid' => 0, 'unit_name' => 'TWIN'], array_diff_key($units[0], ['uid' => true]));
        $this->assertSame(
            ['organisational_unit' => $units[0]['uid'], 'function_type' => 11, 'employee_type' => 0],
            $this->contractRelations(82),
        );
        $this->assertSame(
            ['organisational_unit' => $units[0]['uid'], 'function_type' => 11, 'employee_type' => 0],
            $this->contractRelations(83),
        );
    }

    /**
     * Every unit with the unique name, deleted ones and drafts included.
     *
     * @return list<array<string, mixed>>
     */
    private function unitsNamed(string $uniqueName): array
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable(self::UNIT_TABLE);
        $queryBuilder->getRestrictions()->removeAll();
        return $queryBuilder
            ->select('uid', 'pid', 'deleted', 'hidden', 'sys_language_uid', 'unit_name')
            ->from(self::UNIT_TABLE)
            ->where($queryBuilder->expr()->eq('unique_name', $queryBuilder->createNamedParameter($uniqueName)))
            ->orderBy('uid')
            ->executeQuery()
            ->fetchAllAssociative();
    }

    /**
     * @return array<string, array{organisational_unit: mixed, function_type: mixed}>
     */
    private function relationsOfImportedContracts(): array
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable(self::CONTRACT_TABLE);
        $queryBuilder->getRestrictions()->removeAll();
        $rows = $queryBuilder
            ->select('import_identifier', 'organisational_unit', 'function_type')
            ->from(self::CONTRACT_TABLE)
            ->orderBy('import_identifier')
            ->addOrderBy('uid')
            ->executeQuery()
            ->fetchAllAssociative();
        $relations = [];
        foreach ($rows as $row) {
            $relations[(string)$row['import_identifier']] = [
                'organisational_unit' => $row['organisational_unit'],
                'function_type' => $row['function_type'],
            ];
        }
        return $relations;
    }

    /**
     * @return array<string, mixed>|false
     */
    private function contractRelations(int $contractUid): array|false
    {
        return $this->getConnectionPool()
            ->getConnectionForTable(self::CONTRACT_TABLE)
            ->select(
                ['organisational_unit', 'function_type', 'employee_type'],
                self::CONTRACT_TABLE,
                ['uid' => $contractUid],
            )
            ->fetchAssociative();
    }

    /**
     * @param non-empty-string $table
     */
    private function countRows(string $table): int
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll();
        return (int)$queryBuilder
            ->count('uid')
            ->from($table)
            ->executeQuery()
            ->fetchOne();
    }
}
