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
     * The `[required, number]` flag list of every document section of the
     * shipped `Settings.yaml` reaches the table as a `columnsOverrides`
     * fragment of that section's record type. It restates neither the range
     * nor the format, so the bounds survive the merge, and a section's
     * `required` stays with its type rather than landing on the column all
     * seven types share.
     */
    #[Test]
    public function yearColumnsCarryTheirRangeAndNoIgnoredBounds(): void
    {
        $table = $GLOBALS['TCA']['tx_academicpersons_domain_model_profile_information'];
        foreach (['year', 'year_start', 'year_end'] as $fieldName) {
            $config = $table['columns'][$fieldName]['config'];
            $this->assertSame('number', $config['type'], $fieldName);
            $this->assertSame('integer', $config['format'], $fieldName);
            $this->assertSame(['lower' => 0, 'upper' => 9999], $config['range'], $fieldName);
            $this->assertTrue($config['nullable'], $fieldName);
            $this->assertArrayNotHasKey('min', $config, $fieldName);
            $this->assertArrayNotHasKey('max', $config, $fieldName);
            $this->assertArrayNotHasKey('required', $config, $fieldName);
        }
        $override = $table['types']['publication']['columnsOverrides']['year']['config'];
        $this->assertTrue($override['required'], 'the required flag of Settings.yaml reaches the record type');
        $this->assertSame('number', $override['type'], 'the number flag keeps the column type');
        $this->assertArrayNotHasKey('range', $override, 'the record type override does not restate the bounds');
    }

    /**
     * The seven relations of a profile to its information records are part of
     * the domain model. They used to be generated from the settings file, so a
     * settings override without the entry silently lost the backend column;
     * now they are declared by the TCA file and exist whatever the settings say.
     */
    #[Test]
    public function domainRelationsRemainAvailableWithoutEditSettings(): void
    {
        $columns = $GLOBALS['TCA']['tx_academicpersons_domain_model_profile']['columns'];
        $expectedRelations = [
            'scientific_research' => 'scientific_research',
            'vita' => 'curriculum_vitae',
            'memberships' => 'membership',
            'cooperation' => 'cooperation',
            'publications' => 'publication',
            'lectures' => 'lecture',
            'press_media' => 'press_media',
        ];
        foreach ($expectedRelations as $fieldName => $recordType) {
            $this->assertSame('inline', $columns[$fieldName]['config']['type'], $fieldName);
            $this->assertSame(
                'tx_academicpersons_domain_model_profile_information',
                $columns[$fieldName]['config']['foreign_table'],
                $fieldName,
            );
            $this->assertSame($recordType, $columns[$fieldName]['config']['foreign_match_fields']['type'], $fieldName);
            $this->assertSame(
                $recordType,
                $columns[$fieldName]['config']['overrideChildTca']['columns']['type']['config']['default'],
                $fieldName,
            );
            $this->assertSame(
                'LLL:EXT:academic_persons/Resources/Private/Language/locallang_tca.xlf:tx_academicpersons_domain_model_profile.columns.' . $fieldName . '.label',
                $columns[$fieldName]['label'],
                $fieldName,
            );
        }
    }
}
