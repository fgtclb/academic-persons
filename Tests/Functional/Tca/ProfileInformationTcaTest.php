<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Tca;

use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\Test;

final class ProfileInformationTcaTest extends AbstractAcademicPersonsTestCase
{
    /**
     * The three year columns are four digit integers. `type=number` reads its
     * bounds from `range` alone - `min` and `max` are options of `type=input`
     * and were silently ignored here - so the range is what renders the HTML
     * bounds, what DataHandler clamps against and what makes the derived
     * column unsigned.
     *
     * The `[required, number]` flag list of the shipped `Settings.yaml` is
     * merged into the `year` column's config by the TCA file. It restates
     * neither the range nor the format, so the bounds survive the merge.
     */
    #[Test]
    public function yearColumnsCarryTheirRangeAndNoIgnoredBounds(): void
    {
        $columns = $GLOBALS['TCA']['tx_academicpersons_domain_model_profile_information']['columns'];
        foreach (['year', 'year_start', 'year_end'] as $fieldName) {
            $config = $columns[$fieldName]['config'];
            $this->assertSame('number', $config['type'], $fieldName);
            $this->assertSame('integer', $config['format'], $fieldName);
            $this->assertSame(['lower' => 0, 'upper' => 9999], $config['range'], $fieldName);
            $this->assertTrue($config['nullable'], $fieldName);
            $this->assertArrayNotHasKey('min', $config, $fieldName);
            $this->assertArrayNotHasKey('max', $config, $fieldName);
        }
        $this->assertTrue($columns['year']['config']['required'], 'the required flag of Settings.yaml reaches the column');
    }
}
