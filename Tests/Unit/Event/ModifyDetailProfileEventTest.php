<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Unit\Event;

use FGTCLB\AcademicPersons\Domain\Model\Dto\PluginControllerActionContextInterface;
use FGTCLB\AcademicPersons\Domain\Model\Profile;
use FGTCLB\AcademicPersons\Event\ModifyDetailProfileEvent;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\View\ViewInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ModifyDetailProfileEventTest extends UnitTestCase
{
    #[Test]
    public function getProfileReturnsInstanceSetInConstructor(): void
    {
        $profileStub = $this->createStub(Profile::class);
        $event = new ModifyDetailProfileEvent(
            $profileStub,
            $this->createStub(ViewInterface::class),
            $this->createStub(PluginControllerActionContextInterface::class),
            '',
            '',
        );
        $this->assertSame($profileStub, $event->getProfile());
    }

    #[Test]
    public function getProfileReturnsProfileSetWithSetProfile(): void
    {
        $profileStub = $this->createStub(Profile::class);
        $event = new ModifyDetailProfileEvent(
            $profileStub,
            $this->createStub(ViewInterface::class),
            $this->createStub(PluginControllerActionContextInterface::class),
            '',
            '',
        );
        $this->assertSame($profileStub, $event->getProfile());
        $newProfileStub = $this->createStub(Profile::class);
        $this->assertNotSame($newProfileStub, $profileStub);
        $event->setProfile($newProfileStub);
        $this->assertSame($newProfileStub, $event->getProfile());
    }

    #[Test]
    public function getDefaultPageTitleFormatReturnsConstructorValue(): void
    {
        $defaultPageTitleFormat = 'CONSTRUCTOR_PAGE_TITLE_FORMAT';
        $event = new ModifyDetailProfileEvent(
            $this->createStub(Profile::class),
            $this->createStub(ViewInterface::class),
            $this->createStub(PluginControllerActionContextInterface::class),
            $defaultPageTitleFormat,
            '',
        );
        $this->assertSame($defaultPageTitleFormat, $event->getDefaultPageTitleFormat());
    }

    #[Test]
    public function getDefaultPageTitleFormatReturnsValueSetWithSetDefaultPageTitleFormat(): void
    {
        $defaultPageTitleFormat = 'CONSTRUCTOR_PAGE_TITLE_FORMAT';
        $event = new ModifyDetailProfileEvent(
            $this->createStub(Profile::class),
            $this->createStub(ViewInterface::class),
            $this->createStub(PluginControllerActionContextInterface::class),
            $defaultPageTitleFormat,
            '',
        );
        $newPageTitleFormat = 'DEFAULT_PAGE_TITLE_FORMAT_SET_WITH_SETTER';
        $event->setDefaultPageTitleFormat($newPageTitleFormat);
        $this->assertNotSame($defaultPageTitleFormat, $newPageTitleFormat);
        $this->assertSame($newPageTitleFormat, $event->getDefaultPageTitleFormat());
    }

    #[Test]
    public function getSettingsPageTitleFormatReturnsConstructorValue(): void
    {
        $defaultPageTitleFormat = 'CONSTRUCTOR_PAGE_TITLE_FORMAT';
        $event = new ModifyDetailProfileEvent(
            $this->createStub(Profile::class),
            $this->createStub(ViewInterface::class),
            $this->createStub(PluginControllerActionContextInterface::class),
            '',
            $defaultPageTitleFormat,
        );
        $this->assertSame($defaultPageTitleFormat, $event->getSettingsPageTitleFormat());
    }

    #[Test]
    public function getSettingsPageTitleFormatReturnsValueSetWithSetSettingsPageTitleFormat(): void
    {
        $defaultPageTitleFormat = 'CONSTRUCTOR_PAGE_TITLE_FORMAT';
        $event = new ModifyDetailProfileEvent(
            $this->createStub(Profile::class),
            $this->createStub(ViewInterface::class),
            $this->createStub(PluginControllerActionContextInterface::class),
            '',
            $defaultPageTitleFormat,
        );
        $newPageTitleFormat = 'DEFAULT_PAGE_TITLE_FORMAT_SET_WITH_SETTER';
        $event->setSettingsPageTitleFormat($newPageTitleFormat);
        $this->assertNotSame($defaultPageTitleFormat, $newPageTitleFormat);
        $this->assertSame($newPageTitleFormat, $event->getSettingsPageTitleFormat());
    }

    public static function getPageTitleFormatToUseReturnsSettingsPageTitleFormatIfNotEmptyDataSets(): \Generator
    {
        yield '#1 SomeValue' => [
            'value' => 'some value',
        ];
        yield '#2 a single space' => [
            'value' => ' ',
        ];
    }

    #[DataProvider('getPageTitleFormatToUseReturnsSettingsPageTitleFormatIfNotEmptyDataSets')]
    #[Test]
    public function getPageTitleFormatToUseReturnsSettingsPageTitleFormatIfNotEmpty(string $value): void
    {
        $this->assertNotEmpty($value);
        $event = new ModifyDetailProfileEvent(
            $this->createStub(Profile::class),
            $this->createStub(ViewInterface::class),
            $this->createStub(PluginControllerActionContextInterface::class),
            'DEFAULT_PAGE_TITLE_FORMAT',
            $value,
        );
        $this->assertSame($value, $event->getPageTitleFormatToUse());
    }

    #[Test]
    public function getPageTitleFormatToUseReturnsDefaultPageTitleFormatIfEmpty(): void
    {
        $defaultPageTitleFormat = 'DEFAULT_PAGE_TITLE_FORMAT';
        $event = new ModifyDetailProfileEvent(
            $this->createStub(Profile::class),
            $this->createStub(ViewInterface::class),
            $this->createStub(PluginControllerActionContextInterface::class),
            $defaultPageTitleFormat,
            '',
        );
        $this->assertSame($defaultPageTitleFormat, $event->getPageTitleFormatToUse());
    }
}
