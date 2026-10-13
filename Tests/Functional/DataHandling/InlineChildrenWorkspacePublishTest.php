<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\DataHandling;

use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\WorkspacePublishingTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * The inline children of a profile, a contract and an organisational unit keep their
 * parent when it is edited in a workspace and published, copied or translated (ACE-907).
 *
 * The first workspace edit of a parent versions its children in the same command. Core
 * rewrote the pointer of each child version to the parent's version row, as it does for
 * every relation of a new version. Published from the workspace module, core writes the
 * live parent into the children once more after the swap, but published from live, as
 * the scheduled publishing of a workspace does, the published child kept the pointer to
 * the deleted version row: it belonged to no record, and a later edit of the parent no
 * longer versioned it.
 */
final class InlineChildrenWorkspacePublishTest extends AbstractAcademicPersonsTestCase
{
    use SiteBasedTestTrait;
    use WorkspacePublishingTrait;

    private const TABLE_PROFILE = 'tx_academicpersons_domain_model_profile';
    private const TABLE_ORGANISATIONAL_UNIT = 'tx_academicpersons_domain_model_organisational_unit';
    private const TABLE_CONTRACT = 'tx_academicpersons_domain_model_contract';
    private const TABLE_EMAIL = 'tx_academicpersons_domain_model_email';
    private const TABLE_PHONE_NUMBER = 'tx_academicpersons_domain_model_phone_number';
    private const TABLE_ADDRESS = 'tx_academicpersons_domain_model_address';
    private const TABLE_PROFILE_INFORMATION = 'tx_academicpersons_domain_model_profile_information';

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
        'DE' => ['id' => 1, 'title' => 'Deutsch', 'locale' => 'de_DE.UTF8', 'iso' => 'de', 'hrefLang' => 'de-DE', 'direction' => ''],
    ];

    protected array $coreExtensionsToLoad = [
        'typo3/cms-install',
        'typo3/cms-rte-ckeditor',
        'typo3/cms-workspaces',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/InlineChildrenWorkspacePublish/profileWithChildren.csv');
        $this->writeSiteConfiguration(
            identifier: 'inline-children-workspace-publish',
            site: $this->buildSiteConfiguration(1, 'https://www.acme.com/'),
            languages: [
                $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
                $this->buildLanguageConfiguration(identifier: 'DE', base: '/de/'),
            ],
        );
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['BE_USER'], $GLOBALS['LANG']);
        GeneralUtility::rmdir($this->instancePath . '/typo3conf/sites', true);
        parent::tearDown();
    }

    /**
     * @return \Generator<string, array{string, string, array<string, string>, string, int, string}>
     */
    public static function inlineRelations(): \Generator
    {
        $contractChange = ['position' => 'Dean'];
        yield 'contract of an e-mail address' => [self::TABLE_CONTRACT, 'email_addresses', $contractChange, self::TABLE_EMAIL, 1, 'contract'];
        yield 'contract of a phone number' => [self::TABLE_CONTRACT, 'phone_numbers', $contractChange, self::TABLE_PHONE_NUMBER, 1, 'contract'];
        yield 'contract of a postal address' => [self::TABLE_CONTRACT, 'physical_addresses', $contractChange, self::TABLE_ADDRESS, 1, 'contract'];
        $profileChange = ['first_name' => 'Annabel'];
        yield 'profile of a contract' => [self::TABLE_PROFILE, 'contracts', $profileChange, self::TABLE_CONTRACT, 1, 'profile'];
        yield 'profile of a profile information record' => [self::TABLE_PROFILE, 'vita', $profileChange, self::TABLE_PROFILE_INFORMATION, 1, 'profile'];
        yield 'organisational unit of a contract' => [self::TABLE_ORGANISATIONAL_UNIT, 'contracts', ['display_text' => 'Institute of Physics'], self::TABLE_CONTRACT, 1, 'organisational_unit'];
    }

    /**
     * @return \Generator<string, array{string, string, array<string, string>, string, int, string, bool}>
     */
    public static function inlineRelationsPublishedFromBothPlaces(): \Generator
    {
        foreach (self::inlineRelations() as $name => $relation) {
            yield $name . ', published from the workspace' => [...$relation, true];
            yield $name . ', published from live' => [...$relation, false];
        }
    }

    /**
     * The version of the child points at the live parent, as every relation of a version
     * names live uids, so does the published child, and a second edit versions the child
     * with its parent again. Without the fix the version pointed at the parent's version
     * row in both places, and the published child kept that pointer when published from
     * live.
     *
     * @param array<string, string> $change
     */
    #[Test]
    #[DataProvider('inlineRelationsPublishedFromBothPlaces')]
    public function aChildKeepsItsParentWhenTheParentIsPublished(
        string $parentTable,
        string $inlineColumn,
        array $change,
        string $childTable,
        int $childUid,
        string $pointerColumn,
        bool $fromTheWorkspace,
    ): void {
        $relation = sprintf('%s.%s', $parentTable, $inlineColumn);
        $this->runDataHandlerInWorkspace(1, [$parentTable => [1 => $change]]);

        $childVersionUid = $this->getWorkspaceVersionUid($childTable, $childUid, 1);
        $this->assertGreaterThan(0, $childVersionUid, 'Expected the child to be versioned with its parent.');
        $this->assertSame(1, $this->getPointer($childTable, $childVersionUid, $pointerColumn), 'The version of the child points at another parent: ' . $relation);

        $this->publishWorkspace(1, $fromTheWorkspace);

        $this->assertSame(1, $this->getPointer($childTable, $childUid, $pointerColumn), 'The published child points at another parent: ' . $relation);

        $this->runDataHandlerInWorkspace(1, [$parentTable => [1 => [array_key_first($change) => 'Changed again']]]);

        $this->assertGreaterThan(0, $this->getWorkspaceVersionUid($childTable, $childUid, 1), 'A second edit did not version the child with its parent.');
    }

    /**
     * @param array<string, string> $change
     */
    #[Test]
    #[DataProvider('inlineRelations')]
    public function aCopiedParentGetsCopiedChildren(
        string $parentTable,
        string $inlineColumn,
        array $change,
        string $childTable,
        int $childUid,
        string $pointerColumn,
    ): void {
        $relation = sprintf('%s.%s', $parentTable, $inlineColumn);
        $dataHandler = $this->runDataHandlerInWorkspace(0, [], [$parentTable => [1 => ['copy' => 100]]]);

        $copiedParentUid = (int)($dataHandler->copyMappingArray_merged[$parentTable][1] ?? 0);
        $copiedChildUid = (int)($dataHandler->copyMappingArray_merged[$childTable][$childUid] ?? 0);
        $this->assertGreaterThan(0, $copiedParentUid, 'Expected the parent to be copied.');
        $this->assertGreaterThan(0, $copiedChildUid, 'Expected the child to be copied with its parent.');
        $this->assertSame($copiedParentUid, $this->getPointer($childTable, $copiedChildUid, $pointerColumn), $relation);
        $this->assertSame(1, $this->getPointer($childTable, $childUid, $pointerColumn), $relation);
    }

    /**
     * Translating a contract translates its addresses, and they belong to the translated
     * contract.
     */
    #[Test]
    public function translatedAddressesBelongToTheTranslatedContract(): void
    {
        $dataHandler = $this->runDataHandlerInWorkspace(0, [], [self::TABLE_CONTRACT => [1 => ['localize' => 1]]]);

        $translatedContractUid = (int)($dataHandler->copyMappingArray_merged[self::TABLE_CONTRACT][1] ?? 0);
        $this->assertGreaterThan(0, $translatedContractUid, 'Expected the contract to be translated.');
        foreach ([self::TABLE_EMAIL, self::TABLE_PHONE_NUMBER, self::TABLE_ADDRESS] as $addressTable) {
            $translatedAddressUid = (int)($dataHandler->copyMappingArray_merged[$addressTable][1] ?? 0);
            $this->assertGreaterThan(0, $translatedAddressUid, sprintf('Expected "%s:1" to be translated with its contract.', $addressTable));
            $this->assertSame($translatedContractUid, $this->getPointer($addressTable, $translatedAddressUid, 'contract'), $addressTable);
            $this->assertSame(1, $this->getPointer($addressTable, 1, 'contract'), $addressTable);
        }
    }

    /**
     * Translating a profile translates its contracts and profile information records, and
     * they belong to the translated profile. An organisational unit does not translate its
     * contracts, a contract keeps the unit of its default language record.
     */
    #[Test]
    public function translatedChildrenBelongToTheTranslatedProfile(): void
    {
        $dataHandler = $this->runDataHandlerInWorkspace(0, [], [self::TABLE_PROFILE => [1 => ['localize' => 1]]]);

        $translatedProfileUid = (int)($dataHandler->copyMappingArray_merged[self::TABLE_PROFILE][1] ?? 0);
        $this->assertGreaterThan(0, $translatedProfileUid, 'Expected the profile to be translated.');
        foreach ([self::TABLE_CONTRACT, self::TABLE_PROFILE_INFORMATION] as $childTable) {
            $translatedChildUid = (int)($dataHandler->copyMappingArray_merged[$childTable][1] ?? 0);
            $this->assertGreaterThan(0, $translatedChildUid, sprintf('Expected "%s:1" to be translated with its profile.', $childTable));
            $this->assertSame($translatedProfileUid, $this->getPointer($childTable, $translatedChildUid, 'profile'), $childTable);
            $this->assertSame(1, $this->getPointer($childTable, 1, 'profile'), $childTable);
        }
    }

    private function getPointer(string $tableName, int $uid, string $pointerColumn): int
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable($tableName);
        $queryBuilder->getRestrictions()->removeAll();
        $pointer = $queryBuilder
            ->select($pointerColumn)
            ->from($tableName)
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)))
            ->executeQuery()
            ->fetchOne();
        $this->assertNotFalse($pointer, sprintf('Record "%s:%d" does not exist.', $tableName, $uid));

        return (int)$pointer;
    }
}
