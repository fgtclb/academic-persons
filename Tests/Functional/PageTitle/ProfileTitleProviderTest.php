<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\PageTitle;

use FGTCLB\AcademicPersons\Domain\Model\Profile;
use FGTCLB\AcademicPersons\Event\ProfileTitlePlaceholderReplacementEvent;
use FGTCLB\AcademicPersons\PageTitle\ProfileTitleProvider;
use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\DependencyInjection\Container;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;

final class ProfileTitleProviderTest extends AbstractAcademicPersonsTestCase
{
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
            static function (ProfileTitlePlaceholderReplacementEvent $event) use (
                &$dispatchedForPlaceholder,
                &$dispatchedCount,
            ): void {
                $dispatchedForPlaceholder = $event->getPlaceholder();
                $dispatchedCount++;
            }
        );
        $listenerProvider = $container->get(ListenerProvider::class);
        $listenerProvider->addListener(ProfileTitlePlaceholderReplacementEvent::class, 'placeholder-dispatched');

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
        $subject = $this->get(ProfileTitleProvider::class);
        if ($profile !== null) {
            $subject->setFromProfile($profile, $format);
        }
        return $subject;
    }
}
