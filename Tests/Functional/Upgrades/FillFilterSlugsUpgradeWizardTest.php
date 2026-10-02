<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Upgrades;

use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use FGTCLB\AcademicPersons\Upgrades\FillFilterSlugsUpgradeWizard;
use PHPUnit\Framework\Attributes\Test;

/**
 * Function types: Professor (1) and a hidden Professor in another folder (2), both
 * without a slug, Lecturer (3) with a slug an editor chose, a deleted Dean (4) and the
 * German "Professorin" (5), a translation of the first, without a slug, and a workspace
 * version of the lecturer (6), renamed and without a slug.
 * Organisational units: "Physics & Astronomy" (1) without a slug, and Biology (2) with
 * one an editor chose.
 */
final class FillFilterSlugsUpgradeWizardTest extends AbstractAcademicPersonsTestCase
{
    private const FUNCTION_TYPE_TABLE = 'tx_academicpersons_domain_model_function_type';
    private const UNIT_TABLE = 'tx_academicpersons_domain_model_organisational_unit';

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/FillFilterSlugs/records.csv');
    }

    private function getSubject(): FillFilterSlugsUpgradeWizard
    {
        $subject = $this->get(FillFilterSlugsUpgradeWizard::class);
        $this->assertInstanceOf(FillFilterSlugsUpgradeWizard::class, $subject);
        return $subject;
    }

    /**
     * @return array<int, string>
     */
    private function slugs(string $table): array
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll();
        $rows = $queryBuilder->select('uid', 'slug')->from($table)->orderBy('uid')->executeQuery()->fetchAllAssociative();
        $slugs = [];
        foreach ($rows as $row) {
            $slugs[(int)$row['uid']] = (string)$row['slug'];
        }
        return $slugs;
    }

    #[Test]
    public function theWizardIsRegistered(): void
    {
        $this->assertTrue(MigrateContractPublishToHiddenUpgradeWizardRegistrationTest::isRegistered($this, 'academicPersons_fillFilterSlugs'));
    }

    #[Test]
    public function anUpdateIsNecessaryWhileARecordHasNoSlug(): void
    {
        $this->assertTrue($this->getSubject()->updateNecessary());
    }

    /**
     * Each record gets the slug a save generates from its name, unique in the table for
     * its language: the older of the two professors gets the plain slug, the translation
     * one from its own name. A slug an editor set, a deleted record and a workspace version
     * stay as they are.
     */
    #[Test]
    public function everyLiveRecordWithoutASlugGetsTheSlugASaveGenerates(): void
    {
        $this->assertTrue($this->getSubject()->executeUpdate());

        $this->assertSame(
            [1 => 'professor', 2 => 'professor-1', 3 => 'teaching-staff', 4 => '', 5 => 'professorin', 6 => ''],
            $this->slugs(self::FUNCTION_TYPE_TABLE),
        );
        $this->assertSame([1 => 'physics-astronomy', 2 => 'life-sciences'], $this->slugs(self::UNIT_TABLE));
    }

    #[Test]
    public function aSecondRunChangesNothing(): void
    {
        $this->getSubject()->executeUpdate();
        $functionTypes = $this->slugs(self::FUNCTION_TYPE_TABLE);
        $units = $this->slugs(self::UNIT_TABLE);

        $this->assertFalse($this->getSubject()->updateNecessary());
        $this->assertTrue($this->getSubject()->executeUpdate());
        $this->assertSame($functionTypes, $this->slugs(self::FUNCTION_TYPE_TABLE));
        $this->assertSame($units, $this->slugs(self::UNIT_TABLE));
    }

    /**
     * The wizard is offered again for a record that lost its slug after it ran, as one an
     * import writes without one, and gives it the slug it had.
     */
    #[Test]
    public function aRecordThatLostItsSlugIsOfferedAgain(): void
    {
        $this->getSubject()->executeUpdate();
        $this->getConnectionPool()->getConnectionForTable(self::UNIT_TABLE)->update(self::UNIT_TABLE, ['slug' => ''], ['uid' => 1]);

        $this->assertTrue($this->getSubject()->updateNecessary());
        $this->getSubject()->executeUpdate();
        $this->assertSame([1 => 'physics-astronomy', 2 => 'life-sciences'], $this->slugs(self::UNIT_TABLE));
    }
}
