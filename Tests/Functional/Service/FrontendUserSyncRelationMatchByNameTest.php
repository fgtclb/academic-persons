<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Service;

use FGTCLB\AcademicPersons\Domain\Model\Dto\ProfileCreateCommandDto;
use FGTCLB\AcademicPersons\Service\ProfileCreateCommandService;
use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * The other shape of the relation map, `test_frontend_user_sync_relations_by_name`:
 * the organisational unit is matched by its name and never created, and a
 * missing function type is created on page 8. The records are the ones of
 * {@see FrontendUserSyncRelationMappingTest}.
 */
final class FrontendUserSyncRelationMatchByNameTest extends AbstractAcademicPersonsTestCase
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
        $this->addTestExtension('tests/test-frontend-user-sync-relations-by-name');
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
     * "Chemistry" is the name of unit 21 and "CHEM" only its unique name,
     * which this map does not read: the first user gets unit 21, the second
     * one no unit, and no unit is created. The function type "Dean" does not
     * exist: the first user creates it on page 8, the second one gets it.
     */
    #[Test]
    public function createProfilesMatchesUnitsByNameAndCreatesAMissingFunctionType(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/FrontendUserSyncRelationMapping/frontend-users-by-name.csv');

        GeneralUtility::makeInstance(ProfileCreateCommandService::class)
            ->execute(new ProfileCreateCommandDto(includePids: [200], excludePids: []));

        $functionTypes = $this->functionTypesNamed('Dean');
        $this->assertCount(1, $functionTypes);
        $this->assertSame(['pid' => 8, 'deleted' => 0, 'hidden' => 0, 'sys_language_uid' => 0], array_diff_key($functionTypes[0], ['uid' => true]));
        $this->assertSame(
            [
                'fe_users:90' => ['organisational_unit' => 21, 'function_type' => $functionTypes[0]['uid']],
                'fe_users:91' => ['organisational_unit' => 0, 'function_type' => $functionTypes[0]['uid']],
            ],
            $this->relationsOfImportedContracts(),
        );
        $this->assertSame(6, $this->countRows(self::UNIT_TABLE));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function functionTypesNamed(string $functionName): array
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable(self::FUNCTION_TYPE_TABLE);
        $queryBuilder->getRestrictions()->removeAll();
        return $queryBuilder
            ->select('uid', 'pid', 'deleted', 'hidden', 'sys_language_uid')
            ->from(self::FUNCTION_TYPE_TABLE)
            ->where($queryBuilder->expr()->eq('function_name', $queryBuilder->createNamedParameter($functionName)))
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
