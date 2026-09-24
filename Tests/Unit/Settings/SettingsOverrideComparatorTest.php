<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Unit\Settings;

use FGTCLB\AcademicBase\Settings\SettingsFileLoader;
use FGTCLB\AcademicPersons\Settings\AcademicPersonsSettingsFactory;
use FGTCLB\AcademicPersons\Settings\SettingsOverride;
use FGTCLB\AcademicPersons\Settings\SettingsOverrideComparator;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Every test compares a package with the file academic_persons ships, or with
 * a small array where the shipped file has no example, and checks the one
 * property the delta exists for: merged onto the packages before it, it gives
 * the array the package's own file gives, key order included.
 */
final class SettingsOverrideComparatorTest extends UnitTestCase
{
    private SettingsFileLoader $loader;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loader = new SettingsFileLoader(
            $this->createMock(PhpFrontend::class),
            $this->createMock(PackageManager::class),
        );
    }

    #[Test]
    public function theFirstPackageIsTheBaseAndNotCompared(): void
    {
        $overrides = $this->subject()->compare([
            'academic_persons' => $this->shipped(),
            'site_package' => ['profile' => ['gender' => ['validators' => []]]],
        ]);

        $this->assertCount(1, $overrides);
        $this->assertSame('site_package', $overrides[0]->packageKey);
    }

    /**
     * The ACE-536 case written the way the merge allows: one list, nothing
     * else. It is its own delta, and nothing about it is a copy.
     */
    #[Test]
    public function aDeltaIsItsOwnDeltaAndReportsNothing(): void
    {
        $package = ['profile' => ['gender' => ['validators' => []], 'lastName' => ['validators' => ['readonly']]]];

        $override = $this->compareWithShipped($package);

        $this->assertSame($package, $override->delta);
        $this->assertSame([], $override->removedEntries);
        $this->assertSame([], $override->omittedEntries);
    }

    /**
     * The ACE-536 case as a project ships it today: all of `profile`, copied
     * to clear the gender flags, and without the middle name, which the copy
     * dropped by leaving it out and now inherits.
     */
    #[Test]
    public function aCopyShrinksToWhatItChangesAndNamesWhatItLeavesOut(): void
    {
        $profile = $this->shipped()['profile'];
        unset($profile['middleName']);
        $profile['gender']['validators'] = [];

        $override = $this->compareWithShipped(['profile' => $profile]);

        $this->assertSame(['profile' => ['gender' => ['validators' => []]]], $override->delta);
        $this->assertSame(['profile.middleName'], $override->omittedEntries);
        $this->assertSame([], $override->removedEntries);
    }

    #[Test]
    public function aCopyThatChangesNothingHasAnEmptyDelta(): void
    {
        $shipped = $this->shipped();

        $override = $this->compareWithShipped([
            'profile' => $shipped['profile'],
            'contracts' => $shipped['contracts'],
        ]);

        $this->assertSame([], $override->delta);
        $this->assertSame([], $override->omittedEntries, 'Leaving out whole top-level maps never removed them');
    }

    /**
     * A `~` removes what an earlier package has, and is kept; a `~` for a key
     * no earlier package has removes nothing, and is not.
     */
    #[Test]
    public function aRemovalIsKeptOnlyWhereAnEarlierPackageHasTheKey(): void
    {
        $override = $this->compareWithShipped([
            'profile' => ['middleName' => null, 'nickName' => null],
            'documentSections' => null,
        ]);

        $this->assertSame(['profile' => ['middleName' => null], 'documentSections' => null], $override->delta);
        $this->assertSame(['profile.middleName', 'documentSections'], $override->removedEntries);
        $this->assertSame([], $override->omittedEntries);
    }

    /**
     * A restated field that leaves out a key inherits it: the manual's old
     * recipe for unlocking the names restated them without `validators`, and
     * they stayed locked. Found at the depth where it happens.
     */
    #[Test]
    public function aRestatedFieldThatLeavesOutItsValidatorsIsReported(): void
    {
        $firstName = $this->shipped()['profile']['firstName'];
        unset($firstName['validators']);

        $override = $this->compareWithShipped(['profile' => ['firstName' => $firstName]]);

        $this->assertSame([], $override->delta);
        $this->assertSame(['profile.firstName.validators'], $override->omittedEntries);
    }

    /**
     * The drift the report exists for: a copy made before upstream added a key
     * to every field restates no field exactly, and is a copy all the same.
     */
    #[Test]
    public function aCopyMadeBeforeUpstreamAddedAKeyIsStillACopy(): void
    {
        $fields = $this->shipped()['contracts']['fields'];
        unset($fields['room']);
        foreach ($fields as $key => $field) {
            unset($fields[$key]['helptext']);
        }

        $override = $this->compareWithShipped(['contracts' => ['fields' => $fields]]);

        $this->assertContains('contracts.fields.room', $override->omittedEntries);
        $this->assertContains('contracts.fields.position.helptext', $override->omittedEntries);
        $this->assertSame([], $override->delta);
    }

    #[Test]
    public function aCopyWithItsKeysInAnotherOrderIsStillACopy(): void
    {
        $fields = $this->shipped()['contracts']['fields'];
        unset($fields['room']);
        foreach ($fields as $key => $field) {
            $fields[$key] = array_reverse($field, true);
        }

        $override = $this->compareWithShipped(['contracts' => ['fields' => $fields]]);

        $this->assertSame(['contracts.fields.room'], $override->omittedEntries);
    }

    /**
     * The shape a 3.0 development override had: the whole `contracts` map
     * restated, because a partial one lost the rest, with the contract fields
     * cut down to the position and a start date that is no longer required.
     * Everything below a copied map is a copy as well - it was replaced along
     * with it - so the fields left out are reported, and so are the keys the
     * start date lost, although neither map restates two entries unchanged.
     */
    #[Test]
    public function aMapInsideACopyIsACopyAsWell(): void
    {
        $contracts = $this->shipped()['contracts'];
        $contracts['fields'] = [
            'position' => $contracts['fields']['position'],
            'validFrom' => ['validators' => ['date']],
        ];

        $override = $this->compareWithShipped(['contracts' => $contracts]);

        $this->assertSame(
            [
                'contracts.fields.organisationalUnit',
                'contracts.fields.functionType',
                'contracts.fields.validTo',
                'contracts.fields.location',
                'contracts.fields.room',
                'contracts.fields.officeHours',
                'contracts.fields.publish',
                'contracts.fields.validFrom.fieldType',
                'contracts.fields.validFrom.renderType',
                'contracts.fields.validFrom.helptext',
            ],
            $override->omittedEntries,
        );
    }

    /**
     * A copy that keeps only a few of many entries is still a copy: what it
     * leaves out is what comes back.
     */
    #[Test]
    public function aCopyThatKeepsFewEntriesIsStillACopy(): void
    {
        $profile = array_slice($this->shipped()['profile'], 0, 6, true);

        $override = $this->compareWithShipped(['profile' => $profile]);

        $this->assertCount(8, $override->omittedEntries);
        $this->assertContains('profile.miscellaneous', $override->omittedEntries);
    }

    /**
     * Two restated entries make a copy, one does not: a delta has no reason to
     * restate any, and a single one is taken for a slip.
     */
    #[Test]
    public function twoRestatedEntriesMakeACopyAndOneDoesNot(): void
    {
        $earlier = ['map' => ['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4]];

        $this->assertSame(['map.c', 'map.d'], $this->compareWith($earlier, ['map' => ['a' => 1, 'b' => 2]])->omittedEntries);
        $this->assertSame([], $this->compareWith($earlier, ['map' => ['a' => 1, 'b' => 5]])->omittedEntries);
    }

    /**
     * A map whose keys are integers could shrink to a list, which the merge
     * takes as a value; the map is kept as the package has it instead.
     */
    #[Test]
    public function aMapWithIntegerKeysDoesNotShrinkToAList(): void
    {
        $override = $this->compareWith(
            ['map' => [0 => 'a', 1 => 'b', 'k' => 'c']],
            ['map' => [0 => 'x', 1 => 'b', 'k' => 'c']],
        );

        $this->assertSame(['map' => [0 => 'x', 1 => 'b', 'k' => 'c']], $override->delta);
    }

    #[Test]
    public function aValueThatReplacesIsKeptAsItIs(): void
    {
        $earlier = ['list' => ['a', 'b'], 'scalar' => 'x', 'map' => ['a' => 1]];
        $package = [
            'list' => ['c' => 1],
            'scalar' => ['d' => null, 'e' => 2],
            'map' => ['a', 'b'],
            'new' => ['f' => ['g' => 1]],
        ];

        $override = $this->compareWith($earlier, $package);

        $this->assertSame($package, $override->delta);
    }

    #[Test]
    public function anEmptyListOrMapIsAValueAndReplaces(): void
    {
        $override = $this->compareWithShipped(['profile' => ['gender' => [], 'structure' => ['left' => []]]]);

        $this->assertSame(['profile' => ['gender' => [], 'structure' => ['left' => []]]], $override->delta);
    }

    /**
     * A complete restatement in another order decides the order, so the delta
     * has to restate every entry as well; the entries that do not change are
     * restated as the package has them.
     */
    #[Test]
    public function aCompleteRestatementInAnotherOrderKeepsEveryEntry(): void
    {
        $fields = array_reverse($this->shipped()['contracts']['fields'], true);
        $fields['room']['validators'] = ['required'];

        $override = $this->compareWithShipped(['contracts' => ['fields' => $fields]]);

        $delta = $override->delta['contracts']['fields'] ?? [];
        $this->assertSame(array_keys($fields), array_keys($delta));
        $this->assertSame(['validators' => ['required']], $delta['room']);
        $this->assertSame($fields['position'], $delta['position']);
        $this->assertSame([], $override->omittedEntries);
    }

    /**
     * A restatement with a new entry between two others places it there; a
     * delta of the new entry alone would append it.
     */
    #[Test]
    public function aNewEntryBetweenRestatedOnesKeepsTheRestatement(): void
    {
        $override = $this->compareWith(
            ['map' => ['a' => 1, 'b' => 2]],
            ['map' => ['a' => 1, 'new' => 3, 'b' => 2]],
        );

        $this->assertSame(['map' => ['a' => 1, 'new' => 3, 'b' => 2]], $override->delta);
    }

    /**
     * A new entry between restated ones decides nothing where the map does not
     * name every earlier entry: the merge appends it anyway.
     */
    #[Test]
    public function aNewEntryInAPartialMapIsItsOwnDelta(): void
    {
        $override = $this->compareWith(
            ['map' => ['a' => 1, 'b' => 2, 'c' => 3]],
            ['map' => ['a' => 1, 'new' => 4, 'b' => 2]],
        );

        $this->assertSame(['map' => ['new' => 4]], $override->delta);
    }

    #[Test]
    public function aNewEntryAfterRestatedOnesIsItsOwnDelta(): void
    {
        $override = $this->compareWith(
            ['map' => ['a' => 1, 'b' => 2]],
            ['map' => ['a' => 1, 'b' => 2, 'new' => 3]],
        );

        $this->assertSame(['map' => ['new' => 3]], $override->delta);
    }

    /**
     * The package is compared with what the packages before it produced, not
     * with the shipped file: a project's base package and a later package
     * restating the base's value are no override of the later one.
     */
    #[Test]
    public function aPackageIsComparedWithEveryPackageBeforeIt(): void
    {
        $overrides = $this->subject()->compare([
            'academic_persons' => $this->shipped(),
            'base_package' => ['profile' => ['gender' => ['validators' => []]]],
            'site_package' => ['profile' => ['gender' => ['validators' => []], 'title' => ['validators' => ['required']]]],
        ]);

        $this->assertSame(['profile' => ['gender' => ['validators' => []]]], $overrides[0]->delta);
        $this->assertSame(['profile' => ['title' => ['validators' => ['required']]]], $overrides[1]->delta);
    }

    /**
     * @param array<string, mixed> $package
     */
    private function compareWithShipped(array $package): SettingsOverride
    {
        return $this->compareWith($this->shipped(), $package);
    }

    /**
     * @param array<string, mixed> $earlier
     * @param array<string, mixed> $package
     */
    private function compareWith(array $earlier, array $package): SettingsOverride
    {
        $overrides = $this->subject()->compare(['earlier' => $earlier, 'package' => $package]);
        $this->assertCount(1, $overrides);
        $this->assertSame(
            $this->loader->merge($earlier, $package),
            $this->loader->merge($earlier, $overrides[0]->delta),
            'The delta gives the settings the file gives, key order included',
        );
        return $overrides[0];
    }

    private function subject(): SettingsOverrideComparator
    {
        return new SettingsOverrideComparator($this->loader);
    }

    /**
     * @return array<string, mixed>
     */
    private function shipped(): array
    {
        $settings = Yaml::parseFile(__DIR__ . '/../../../' . AcademicPersonsSettingsFactory::SETTINGS_FILE);
        $this->assertIsArray($settings);
        return $settings;
    }
}
