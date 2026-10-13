<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Domain\Repository;

use FGTCLB\AcademicPersons\Domain\Model\Dto\ProfileDemand;
use FGTCLB\AcademicPersons\Domain\Model\Profile;
use FGTCLB\AcademicPersons\Domain\Repository\ProfileRepository;
use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;

/**
 * The ordering `ProfileRepository::findByDemand()` applies for the sort field, the group
 * field and the direction of a demand.
 *
 * The last names of the fixture are not in uid order, so a sorted result and the uid
 * fallback of ACE-482 can be told apart.
 *
 * Every case runs in a frontend request, which is what a list element renders in. Extbase
 * reads its TypoScript setup to build the query settings, an empty one is enough. It is
 * created without its constructor, which takes two arguments on v12 and four on v13.
 */
final class ProfileRepositoryOrderingTest extends AbstractAcademicPersonsTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ProfileListOrdering/profiles.csv');
        $frontendTypoScript = (new \ReflectionClass(FrontendTypoScript::class))->newInstanceWithoutConstructor();
        $frontendTypoScript->setSetupArray([]);
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest('https://www.acme.com/'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('frontend.typoscript', $frontendTypoScript);
    }

    /**
     * "None" is the value '' in the group and sort selects of a list element. A trailing
     * comma in the configured values used to add '' to the allowed values, so "none" passed
     * the allow list and the list ordered by a property without a name.
     *
     * @param int[] $expected
     */
    #[Test]
    #[DataProvider('emptyGroupAndSortFields')]
    public function anEmptyConfiguredValueNeverBecomesAnOrdering(string $groupBy, string $sortBy, array $expected): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['academic_persons']['demand']['allowedGroupByValues'] = 'lastNameAlpha=Last name,';
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['academic_persons']['demand']['allowedSortByValues'] = 'firstName=First name,lastName=Last name,';
        $demand = (new ProfileDemand())->setGroupBy($groupBy)->setSortBy($sortBy);

        $this->assertSame($expected, $this->resultUids($this->subject()->findByDemand($demand)));
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: int[]}>
     */
    public static function emptyGroupAndSortFields(): array
    {
        return [
            'neither grouped nor sorted keeps the uid order' => ['', '', [1, 2, 3]],
            'not grouped, sorted by last name' => ['', 'lastName', [2, 1, 3]],
        ];
    }

    private function subject(): ProfileRepository
    {
        return $this->get(ProfileRepository::class);
    }

    /**
     * @param QueryResultInterface<int, Profile> $result
     * @return int[]
     */
    private function resultUids(QueryResultInterface $result): array
    {
        $uids = [];
        foreach ($result as $profile) {
            $uids[] = (int)$profile->getUid();
        }
        return $uids;
    }
}
