<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Unit\Types;

use FGTCLB\AcademicPersons\Types\EmailAddressTypes;
use FGTCLB\AcademicPersons\Types\PhoneNumberTypes;
use FGTCLB\AcademicPersons\Types\PhysicalAddressTypes;
use FGTCLB\AcademicPersons\Types\TypesInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * The three concrete implementations differ in exactly one string: the extension
 * configuration path they read. `Tca\RecordTypes` picks the class per column, so a
 * swapped or mistyped path is not a fatal - it fills the `type` select of one table
 * with the item list of another, or lets `ExtensionConfiguration::get()` throw while
 * a backend form is being rendered. Neither is visible without asserting the paths.
 *
 * The parser they share is covered by `AbstractTypesTest`.
 */
final class TypesTest extends UnitTestCase
{
    #[Test]
    public function emailAddressTypesReadTheEmailAddressConfiguration(): void
    {
        $subject = new EmailAddressTypes($this->extensionConfigurationExpecting('types/emailAddressTypes'));

        $this->assertInstanceOf(TypesInterface::class, $subject);
        $this->assertSame(['own' => 'Own'], $subject->getAll());
    }

    #[Test]
    public function phoneNumberTypesReadThePhoneNumberConfiguration(): void
    {
        $subject = new PhoneNumberTypes($this->extensionConfigurationExpecting('types/phoneNumberTypes'));

        $this->assertInstanceOf(TypesInterface::class, $subject);
        $this->assertSame(['own' => 'Own'], $subject->getAll());
    }

    #[Test]
    public function physicalAddressTypesReadThePhysicalAddressConfiguration(): void
    {
        $subject = new PhysicalAddressTypes($this->extensionConfigurationExpecting('types/physicalAddressTypes'));

        $this->assertInstanceOf(TypesInterface::class, $subject);
        $this->assertSame(['own' => 'Own'], $subject->getAll());
    }

    /**
     * `ExtensionConfiguration::get()` throws `ExtensionConfigurationPathDoesNotExistException`
     * for a path that `ext_conf_template.txt` never declared, and nothing here catches
     * it - the TCA `itemsProcFunc` would take the whole backend form down. The template
     * writes the path with dots and the classes ask for it with slashes, which is why a
     * plain string comparison would not do.
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
            'email addresses' => ['types/emailAddressTypes'],
            'phone numbers' => ['types/phoneNumberTypes'],
            'physical addresses' => ['types/physicalAddressTypes'],
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
