<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Tca;

use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * A contract has one visibility, `hidden`, which the core, the public views and
 * the frontend editor share. The `publish` toggle it had next to it was read by
 * no public view and is gone from 3.0 on, from the backend form as well as
 * from the database.
 */
final class ContractVisibilityTcaTest extends AbstractAcademicPersonsTestCase
{
    private const TABLE = 'tx_academicpersons_domain_model_contract';

    #[Test]
    public function hiddenIsTheOnlyVisibilityOfAContract(): void
    {
        $table = $GLOBALS['TCA'][self::TABLE];

        $this->assertSame(['disabled' => 'hidden'], $table['ctrl']['enablecolumns']);
        $this->assertArrayNotHasKey('publish', $table['columns']);
        foreach ($table['palettes'] as $paletteName => $palette) {
            $this->assertNotContains(
                'publish',
                array_map('trim', explode(',', (string)($palette['showitem'] ?? ''))),
                'palette ' . $paletteName,
            );
        }
    }

    #[Test]
    public function theContractTableHasNoPublishColumn(): void
    {
        $schemaManager = $this->getConnectionPool()->getConnectionForTable(self::TABLE)->createSchemaManager();

        $this->assertFalse($schemaManager->introspectTableByUnquotedName(self::TABLE)->hasColumn('publish'));
    }
}
