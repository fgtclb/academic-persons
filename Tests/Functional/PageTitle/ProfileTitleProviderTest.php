<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\PageTitle;

use FGTCLB\AcademicPersons\Domain\Model\Profile;
use FGTCLB\AcademicPersons\PageTitle\ProfileTitleProvider;
use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

final class ProfileTitleProviderTest extends AbstractAcademicPersonsTestCase
{
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
