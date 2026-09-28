<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Tca;

use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Package\Cache\PackageDependentCacheIdentifier;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Schema\SchemaCollection;

/**
 * The persons settings reach the backend form after every TCA override and after
 * the TCA listener of EXT:content_blocks. The fixture extension
 * `test_tca_override_after_settings` replaces the teaching area column and the
 * publication record type, locks the profile title, makes the contract position
 * optional, and locks the website from a listener registered under the identifier
 * of the content_blocks listener. A second listener, ordered after the settings,
 * unlocks the middle name again. Its settings declare `nickname`, a field without
 * a column.
 */
final class SettingsAfterTcaOverridesTest extends AbstractAcademicPersonsTestCase
{
    protected function setUp(): void
    {
        $this->testExtensionsToLoad[] = 'tests/tca-override-after-settings';
        parent::setUp();
    }

    #[Test]
    public function aReplacedColumnKeepsWhatTheSettingsSay(): void
    {
        $column = $GLOBALS['TCA']['tx_academicpersons_domain_model_profile']['columns']['teaching_area'];

        $this->assertSame('Teaching area of the site package', $column['label']);
        $this->assertSame('minimal', $column['config']['richtextConfiguration']);
        $this->assertTrue($column['config']['required'] ?? null);
        $this->assertFalse($column['config']['readOnly']);
    }

    /**
     * The timeline sections carry their flags per record type of the profile
     * information table.
     */
    #[Test]
    public function aReplacedRecordTypeKeepsWhatTheSettingsSay(): void
    {
        $type = $GLOBALS['TCA']['tx_academicpersons_domain_model_profile_information']['types']['publication'];

        $this->assertSame('type, title, year, link, bodytext', $type['showitem']);
        $this->assertTrue($type['columnsOverrides']['title']['config']['required'] ?? null);
        $this->assertTrue($type['columnsOverrides']['year']['config']['required'] ?? null);
    }

    #[Test]
    public function theSettingsWinOverAnOverrideOfTheirKeys(): void
    {
        $this->assertFalse($GLOBALS['TCA']['tx_academicpersons_domain_model_profile']['columns']['title']['config']['readOnly']);
        $this->assertTrue($GLOBALS['TCA']['tx_academicpersons_domain_model_contract']['columns']['position']['config']['required']);
    }

    #[Test]
    public function theSettingsAreAppliedAfterTheContentBlocksListener(): void
    {
        $this->assertFalse($GLOBALS['TCA']['tx_academicpersons_domain_model_profile']['columns']['website']['config']['readOnly']);
    }

    /**
     * The way out the changelog names for a site package that has to differ in the
     * backend: a listener ordered after the settings.
     */
    #[Test]
    public function aListenerOrderedAfterTheSettingsKeepsItsChange(): void
    {
        $this->assertFalse($GLOBALS['TCA']['tx_academicpersons_domain_model_profile']['columns']['middle_name']['config']['readOnly']);
    }

    /**
     * The settings describe columns, they do not create them. A fragment for a column
     * the TCA does not have would be a column without a type.
     */
    #[Test]
    public function aFieldWithoutAColumnAddsNoColumn(): void
    {
        $this->assertArrayNotHasKey('nickname', $GLOBALS['TCA']['tx_academicpersons_domain_model_profile']['columns']);
    }

    /**
     * The core adds the soft reference to every `email` column while it prepares the
     * TCA. The e-mail column becomes one through the `email` flag of the settings,
     * which are applied after that preparation.
     */
    #[Test]
    public function theEmailColumnKeepsItsSoftReference(): void
    {
        $config = $GLOBALS['TCA']['tx_academicpersons_domain_model_email']['columns']['email']['config'];

        $this->assertSame('email', $config['type']);
        $this->assertSame('email[subst]', $config['softref']);
    }

    /**
     * The TCA a request works with comes from the cache the bootstrap wrote, not
     * from a compilation of its own. What the cache holds has to carry the settings.
     */
    #[Test]
    public function theCachedTcaCarriesTheSettings(): void
    {
        $tca = $this->get('cache.core')->require($this->cacheIdentifier('tca_base'));
        $this->assertIsArray($tca, 'The bootstrap wrote no TCA cache.');
        $columns = $tca['tca']['tx_academicpersons_domain_model_profile']['columns'];

        $this->assertTrue($columns['teaching_area']['config']['required'] ?? null);
        $this->assertFalse($columns['title']['config']['readOnly']);
        $this->assertFalse($columns['website']['config']['readOnly']);
        $this->assertSame(
            'email[subst]',
            $tca['tca']['tx_academicpersons_domain_model_email']['columns']['email']['config']['softref'],
        );
    }

    /**
     * The schema API reads its own cache, built from the compiled TCA once and
     * reused by every later request. TYPO3 v13 caches an array of schemata, v14 a
     * schema collection.
     */
    #[Test]
    public function theCachedSchemaCarriesTheSettings(): void
    {
        $schemata = $this->get('cache.core')->require($this->cacheIdentifier('TcaSchema'));
        $this->assertTrue(
            is_array($schemata) || $schemata instanceof SchemaCollection,
            'The bootstrap wrote no schema cache.',
        );
        $profile = $schemata['tx_academicpersons_domain_model_profile'];

        $this->assertTrue($profile->getField('teaching_area')->isRequired());
        $this->assertFalse($profile->getField('title')->getConfiguration()['readOnly']);
        $this->assertFalse($profile->getField('website')->getConfiguration()['readOnly']);
        $this->assertTrue($schemata['tx_academicpersons_domain_model_contract']->getField('position')->isRequired());
        $this->assertSame(
            ['email[subst]'],
            $schemata['tx_academicpersons_domain_model_email']->getField('email')->getSoftReferenceKeys(),
        );
    }

    private function cacheIdentifier(string $prefix): string
    {
        return (new PackageDependentCacheIdentifier($this->get(PackageManager::class)))
            ->withPrefix($prefix)
            ->toString();
    }
}
