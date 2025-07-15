<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\PageTitle;

use FGTCLB\AcademicPersons\Domain\Model\Dto\PluginControllerActionContext;
use FGTCLB\AcademicPersons\Domain\Model\Profile;
use FGTCLB\AcademicPersons\Event\ModifyProfileTitlePlaceholderReplacementEvent;
use FGTCLB\AcademicPersons\PageTitle\ProfileTitleProvider;
use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\DependencyInjection\Container;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\Locales;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;

final class ProfileTitleProviderTest extends AbstractAcademicPersonsTestCase
{
    protected function setUp(): void
    {
        $this->addTestExtension(...array_values([
            'tests/language-files',
        ]));
        parent::setUp();
    }

    public static function profileTitlePlaceholderReplacementEventIsDispatchedDataSets(): \Generator
    {
        yield '#1 upper case placeholder not getter of profile' => [
            'format' => '%%SOMEIDENTIFIER%%',
            'dispatchedPlaceholder' => 'SOMEIDENTIFIER',
        ];
        yield '#2 lower case placeholder not getter of profile' => [
            'format' => '%%someidentifier%%',
            'dispatchedPlaceholder' => 'someidentifier',
        ];
        yield '#3 colon is allowed' => [
            'format' => '%%SOME;IDENTIFIER%%',
            'dispatchedPlaceholder' => 'SOME;IDENTIFIER',
        ];
        yield '#4 double-point is allowed' => [
            'format' => '%%SOME:IDENTIFIER%%',
            'dispatchedPlaceholder' => 'SOME:IDENTIFIER',
        ];
        yield '#5 hyphen is allowed' => [
            'format' => '%%SOME-IDENTIFIER%%',
            'dispatchedPlaceholder' => 'SOME-IDENTIFIER',
        ];
        yield '#6 underscore is allowed' => [
            'format' => '%%SOME_IDENTIFIER%%',
            'dispatchedPlaceholder' => 'SOME_IDENTIFIER',
        ];
        yield '#7 dot is allowed' => [
            'format' => '%%SOME.IDENTIFIER%%',
            'dispatchedPlaceholder' => 'SOME.IDENTIFIER',
        ];
        yield '#8 space is allowed' => [
            'format' => '%%SOME IDENTIFIER%%',
            'dispatchedPlaceholder' => 'SOME IDENTIFIER',
        ];
        yield '#9 slash is allowed' => [
            'format' => '%%SOME/IDENTIFIER%%',
            'dispatchedPlaceholder' => 'SOME/IDENTIFIER',
        ];
        yield '#10 backslash is allowed' => [
            'format' => '%%SOME\IDENTIFIER%%',
            'dispatchedPlaceholder' => 'SOME\IDENTIFIER',
        ];
        yield '#11 backslash is allowed' => [
            'format' => '%%SOME\\IDENTIFIER%%',
            'dispatchedPlaceholder' => 'SOME\\IDENTIFIER',
        ];
        yield '#11 language identifier (LLL:)' => [
            'format' => '%%LLL:EXT:myext/Resources/Private/Languages/locallang.db:some.identifier%%',
            'dispatchedPlaceholder' => 'LLL:EXT:myext/Resources/Private/Languages/locallang.db:some.identifier',
        ];
        yield '#12 language identifier (LLL:) containing spaces in path' => [
            'format' => '%%LLL:EXT:myext/Resources/Private/Languages/some path with spaces/locallang.db:some.identifier%%',
            'dispatchedPlaceholder' => 'LLL:EXT:myext/Resources/Private/Languages/some path with spaces/locallang.db:some.identifier',
        ];
    }

    #[DataProvider('profileTitlePlaceholderReplacementEventIsDispatchedDataSets')]
    #[Test]
    public function profileTitlePlaceholderReplacementEventIsDispatchedForNonProfilePlaceholders(string $format, string $dispatchedPlaceholder): void
    {
        $dispatchedCount = 0;
        $dispatchedForPlaceholder = '';
        /** @var Container $container */
        $container = $this->get('service_container');
        $container->set(
            'placeholder-dispatched',
            static function (ModifyProfileTitlePlaceholderReplacementEvent $event) use (
                &$dispatchedForPlaceholder,
                &$dispatchedCount,
            ): void {
                $dispatchedForPlaceholder = $event->getPlaceholder();
                $dispatchedCount++;
            }
        );
        $listenerProvider = $container->get(ListenerProvider::class);
        $listenerProvider->addListener(ModifyProfileTitlePlaceholderReplacementEvent::class, 'placeholder-dispatched');

        $this->createSubject(new Profile(), $format)->getTitle();
        $this->assertSame(1, $dispatchedCount);
        $this->assertSame($dispatchedPlaceholder, $dispatchedForPlaceholder);
    }

    #[Test]
    public function setFromProfileKeepsUnresolvedPlaceholder(): void
    {
        $format = 'Some label containing a %%UNRESOLVEABLE_PLACEHOLDER%% to test against.';
        $this->assertSame($format, $this->createSubject(new Profile(), $format)->getTitle());
    }

    public static function setFromProfileDataSets(): \Generator
    {
        $profile = new Profile();
        $profile
            ->setTitle('SomeTitle')
            ->setFirstName('Max')
            ->setMiddleName('Augustin')
            ->setLastName('Müllermann');

        $format = '%%TITLE%% %%FIRST_NAME%% %%MIDDLE_NAME%% %%LAST_NAME%%';
        yield sprintf('#1 resolve all placeholder of pattern: "%s"', $format) => [
            'profile' => $profile,
            'format' => $format,
            'expected' => 'SomeTitle Max Augustin Müllermann',
        ];

        $format = '%%LAST_NAME%%, %%FIRST_NAME%% %%MIDDLE_NAME%%';
        yield sprintf('#2 resolve all placeholder of pattern: "%s"', $format) => [
            'profile' => $profile,
            'format' => $format,
            'expected' => 'Müllermann, Max Augustin',
        ];

        $format = ' %%TITLE%% %%FIRST_NAME%% %%MIDDLE_NAME%% %%LAST_NAME%% ';
        yield sprintf('#3 leading and trailing format spaces are removed from resolved pattern: "%s"', $format) => [
            'profile' => $profile,
            'format' => $format,
            'expected' => 'SomeTitle Max Augustin Müllermann',
        ];

        $profileLeadingTailingSpaces = new Profile();
        $profileLeadingTailingSpaces
            ->setTitle(' TitleWithLeadingSpace')
            ->setFirstName('Max')
            ->setMiddleName('Augustin')
            ->setLastName('LastnameWithEndingSpace ');
        $format = ' %%TITLE%% %%FIRST_NAME%% %%MIDDLE_NAME%% %%LAST_NAME%% ';
        yield sprintf('#4 leading and trailing spaces are removed from resolved pattern: "%s"', $format) => [
            'profile' => $profileLeadingTailingSpaces,
            'format' => $format,
            'expected' => 'TitleWithLeadingSpace Max Augustin LastnameWithEndingSpace',
        ];

        $profileWithSpaceSurroundedValues = new Profile();
        $profileWithSpaceSurroundedValues
            ->setTitle(' SomeTitle ')
            ->setFirstName(' Max ')
            ->setMiddleName(' Augustin ')
            ->setLastName(' Müllermann ');
        $format = '%%TITLE%% %%FIRST_NAME%% %%MIDDLE_NAME%% %%LAST_NAME%%';
        yield sprintf('#5 multiple spaces are removed from resolved pattern: "%s"', $format) => [
            'profile' => $profileWithSpaceSurroundedValues,
            'format' => $format,
            'expected' => 'SomeTitle Max Augustin Müllermann',
        ];
        $format = '%%LAST_NAME%%, %%FIRST_NAME%% %%MIDDLE_NAME%%';
        yield sprintf('#6 multiple spaces are removed from resolved pattern: "%s"', $format) => [
            'profile' => $profileWithSpaceSurroundedValues,
            'format' => $format,
            'expected' => 'Müllermann, Max Augustin',
        ];

        $profile = new Profile();
        $profile
            ->setTitle('SomeTitle')
            ->setFirstName('Max')
            ->setMiddleName('Augustin')
            ->setLastName('Müllermann');
        $format = 'Prefix: %%TITLE%% %%FIRST_NAME%% %%MIDDLE_NAME%% %%LAST_NAME%% | Detail';
        yield sprintf('#7 static text is kept in pattern: "%s"', $format) => [
            'profile' => $profile,
            'format' => $format,
            'expected' => 'Prefix: SomeTitle Max Augustin Müllermann | Detail',
        ];
    }

    #[DataProvider('setFromProfileDataSets')]
    #[Test]
    public function setFromProfileSetsExpectedTitle(
        Profile $profile,
        string $format,
        string $expected,
    ): void {
        $this->assertSame($expected, $this->createSubject($profile, $format)->getTitle());
    }

    private function createSubject(?Profile $profile = null, string $format = ProfileTitleProvider::DETAIL_PAGE_TITLE_FORMAT): ProfileTitleProvider
    {
        $siteLanguage = $this->createStub(SiteLanguage::class);
        $siteLanguage->method('getLanguageId')->willReturn(0);
        $siteLanguage->method('getLocale')->willReturn($this->get(Locales::class)->createLocale('en_US'));
        $site = $this->createStub(Site::class);
        $site->method('getDefaultLanguage')->willReturn($siteLanguage);
        $site->method('getAllLanguages')->willReturn([$siteLanguage]);
        $request = (new ServerRequest())
            ->withAttribute('site', $site)
            ->withAttribute('language', $siteLanguage);
        $pluginControllerActionContext = new PluginControllerActionContext($request, []);
        $subject = $this->get(ProfileTitleProvider::class);
        if ($profile !== null) {
            $subject->setFromProfile(
                $pluginControllerActionContext,
                $profile,
                $format,
            );
        }
        return $subject;
    }

    /**
     * @return array<string, array{placeholder: string, locale: string|null, expected: string}>
     */
    public static function localizationPlaceholderDataSets(): array
    {
        return [
            // placeholder.with.dots
            '#1 placeholder.with.dots (default)' => [
                'placeholder' => 'LLL:EXT:test_language_files/Resources/Private/Language/locallang.xlf:placeholder.with.dots',
                'locale' => null,
                'expected' => 'Placeholder with dots',
            ],
            '#2 placeholder.with.dots (en_US)' => [
                'placeholder' => 'LLL:EXT:test_language_files/Resources/Private/Language/locallang.xlf:placeholder.with.dots',
                'locale' => 'en_US',
                'expected' => 'Placeholder with dots',
            ],
            '#3 placeholder.with.dots (de_DE)' => [
                'placeholder' => 'LLL:EXT:test_language_files/Resources/Private/Language/locallang.xlf:placeholder.with.dots',
                'locale' => 'de_DE',
                'expected' => 'Platzhalter mit Punkten',
            ],
            // placeholder-with-dashes
            '#4 placeholder-with-dashes (default)' => [
                'placeholder' => 'LLL:EXT:test_language_files/Resources/Private/Language/locallang.xlf:placeholder-with-dashes',
                'locale' => null,
                'expected' => 'Placeholder with dashes',
            ],
            '#5 placeholder-with-dashes (en_US)' => [
                'placeholder' => 'LLL:EXT:test_language_files/Resources/Private/Language/locallang.xlf:placeholder-with-dashes',
                'locale' => 'en_US',
                'expected' => 'Placeholder with dashes',
            ],
            '#6 placeholder-with-dashes (de_DE)' => [
                'placeholder' => 'LLL:EXT:test_language_files/Resources/Private/Language/locallang.xlf:placeholder-with-dashes',
                'locale' => 'de_DE',
                'expected' => 'Platzhalter mit Bindestrichen',
            ],
            // placeholder_with_underscores
            '#7 placeholder_with_underscores (default)' => [
                'placeholder' => 'LLL:EXT:test_language_files/Resources/Private/Language/locallang.xlf:placeholder_with_underscores',
                'locale' => null,
                'expected' => 'Placeholder with underscores',
            ],
            '#8 placeholder_with_underscores (en_US)' => [
                'placeholder' => 'LLL:EXT:test_language_files/Resources/Private/Language/locallang.xlf:placeholder_with_underscores',
                'locale' => 'en_US',
                'expected' => 'Placeholder with underscores',
            ],
            '#9 placeholder_with_underscores (de_DE)' => [
                'placeholder' => 'LLL:EXT:test_language_files/Resources/Private/Language/locallang.xlf:placeholder_with_underscores',
                'locale' => 'de_DE',
                'expected' => 'Platzhalter mit Unterstrichen',
            ],
        ];
    }

    #[DataProvider('localizationPlaceholderDataSets')]
    #[Test]
    public function processTranslationPlaceholderReturnsExpectedReplacement(
        string $placeholder,
        ?string $locale,
        string $expected,
    ): void {
        $subject = $this->get(ProfileTitleProvider::class);
        $pluginControllerActionContext = new PluginControllerActionContext($this->createRequest($locale), []);
        $reflection = new \ReflectionClass($subject);
        $reflectionMethod = $reflection->getMethod('processTranslationPlaceholder');
        $this->assertSame($expected, $reflectionMethod->invoke($subject, $pluginControllerActionContext, $placeholder, $placeholder));
    }

    #[DataProvider('localizationPlaceholderDataSets')]
    #[Test]
    public function setFromProfileWithLocalizedPrefixSetsExpectedTitle(
        string $placeholder,
        ?string $locale,
        string $expected,
    ): void {
        $profile = new Profile();
        $profile->setLastName('Müllermann');
        // Looks weired, but we need to escape each percentage with another percentage,
        // which makes four percentage signs for the two required for start/end signs.
        $format = sprintf('%%%%%s%%%%: %%%%LAST_NAME%%%%', $placeholder);
        $expectedPageTitle = sprintf('%s: Müllermann', $expected);
        $subject = $this->get(ProfileTitleProvider::class);
        $pluginControllerActionContext = new PluginControllerActionContext($this->createRequest($locale), []);
        $subject->setFromProfile($pluginControllerActionContext, $profile, $format);
        $this->assertSame($expectedPageTitle, $subject->getTitle());
    }

    private function createRequest(?string $locale = null): ServerRequestInterface
    {
        $request = (new ServerRequest());
        if ($locale !== null) {
            $siteLanguage = $this->createStub(SiteLanguage::class);
            $siteLanguage->method('getLanguageId')->willReturn(0);
            $siteLanguage->method('getLocale')->willReturn($this->get(Locales::class)->createLocale($locale));
            $site = $this->createStub(Site::class);
            $site->method('getDefaultLanguage')->willReturn($siteLanguage);
            $site->method('getAllLanguages')->willReturn([$siteLanguage]);
            $request = $request->withAttribute('site', $site)->withAttribute('language', $siteLanguage);
        }
        return $request;
    }
}
