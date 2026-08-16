<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Unit\DemandValues;

use FGTCLB\AcademicPersons\DemandValues\DemandValuesInterface;
use FGTCLB\AcademicPersons\DemandValues\GroupByValues;
use FGTCLB\AcademicPersons\DemandValues\SortByValues;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * The two implementations differ in exactly one string: the extension configuration
 * path they read. They are used in pairs - `Tca\DemandValues` fills the `groupBy` and
 * the `sortBy` FlexForm select from them, `ProfileRepository` validates against both -
 * so swapping the two paths would not fail anywhere, it would offer the group-by
 * values as sort-by values and accept them as orderings.
 *
 * The parser they share is covered by `AbstractDemandValuesTest`.
 */
final class DemandValuesTest extends UnitTestCase
{
    #[Test]
    public function groupByValuesReadTheAllowedGroupByConfiguration(): void
    {
        $subject = new GroupByValues($this->extensionConfigurationExpecting('demand/allowedGroupByValues'));

        $this->assertInstanceOf(DemandValuesInterface::class, $subject);
        $this->assertSame(['own' => 'Own'], $subject->getAll());
    }

    #[Test]
    public function sortByValuesReadTheAllowedSortByConfiguration(): void
    {
        $subject = new SortByValues($this->extensionConfigurationExpecting('demand/allowedSortByValues'));

        $this->assertInstanceOf(DemandValuesInterface::class, $subject);
        $this->assertSame(['own' => 'Own'], $subject->getAll());
    }

    /**
     * `ExtensionConfiguration::get()` throws `ExtensionConfigurationPathDoesNotExistException`
     * for a path that `ext_conf_template.txt` never declared, and nothing here catches
     * it - both the FlexForm `itemsProcFunc` and every profile list query go through
     * these classes. The template writes the path with dots and the classes ask for it
     * with slashes, which is why a plain string comparison would not do.
     */
    #[Test]
    #[DataProvider('configurationPaths')]
    public function eachConfigurationPathIsDeclaredInTheExtensionConfigurationTemplate(string $path): void
    {
        $template = (string)file_get_contents(__DIR__ . '/../../../ext_conf_template.txt');

        $this->assertMatchesRegularExpression(
            '/^' . preg_quote(str_replace('/', '.', $path), '/') . '\s*=/m',
            $template,
            sprintf('ext_conf_template.txt does not declare "%s"', $path),
        );
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function configurationPaths(): array
    {
        return [
            'group by' => ['demand/allowedGroupByValues'],
            'sort by' => ['demand/allowedSortByValues'],
        ];
    }

    private function extensionConfigurationExpecting(string $path): ExtensionConfiguration
    {
        $extensionConfiguration = $this->createMock(ExtensionConfiguration::class);
        $extensionConfiguration->expects($this->once())
            ->method('get')
            ->with('academic_persons', $path)
            ->willReturn('own=Own');

        return $extensionConfiguration;
    }
}
