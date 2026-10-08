<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Domain\Repository;

use FGTCLB\AcademicPersons\Domain\Model\Profile;
use FGTCLB\AcademicPersons\Domain\Repository\ProfileRepository;
use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Context\VisibilityAspect;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;

/**
 * The "including hidden" lookups of `ProfileRepository` that render a profile, in a
 * translated language (ACE-857): the selected profiles and the profiles of their owner.
 * A hidden profile is returned with its hidden translation, like a visible one with its
 * visible translation.
 *
 * Every case runs in a frontend request, see `ProfileRepositoryVisibilityWindowTest` for
 * the reason, with the German language aspect Extbase builds its query settings from. The
 * frontend user relation is `l10n_mode` `exclude`, so the translations carry the relation
 * rows of their default record, as the DataHandler writes them.
 */
final class ProfileRepositoryHiddenTranslationTest extends AbstractAcademicPersonsTestCase
{
    private const FRONTEND_USER = 10;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/HiddenTranslation/profiles.csv');
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $frontendTypoScript->setSetupArray([]);
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest('https://www.acme.com/de/'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('frontend.typoscript', $frontendTypoScript);
        $this->get(Context::class)->setAspect(
            'language',
            new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON_WITH_FLOATING, []),
        );
    }

    private function subject(): ProfileRepository
    {
        return $this->get(ProfileRepository::class);
    }

    /**
     * @param iterable<Profile> $profiles
     * @return list<string>
     */
    private function lastNames(iterable $profiles): array
    {
        $lastNames = [];
        foreach ($profiles as $profile) {
            $lastNames[] = $profile->getLastName();
        }
        return $lastNames;
    }

    #[Test]
    public function selectedProfilesIncludingHiddenAreReturnedWithTheirTranslation(): void
    {
        $this->assertSame(['Sichtbar', 'Verborgen'], $this->lastNames($this->subject()->findByUids([1, 2], true)));
    }

    #[Test]
    public function frontendUserLookupOfTheOwnerReturnsTheTranslationOfAHiddenProfile(): void
    {
        $this->assertSame(
            ['Sichtbar', 'Verborgen'],
            $this->lastNames($this->subject()->findByFrontendUserIncludingHidden(self::FRONTEND_USER)),
        );
    }

    /**
     * The visibility aspect is lifted while the result is fetched on TYPO3 v13. Whatever the
     * request renders afterwards must see the aspect it had.
     */
    #[Test]
    public function selectedProfilesIncludingHiddenRestoreTheVisibilityOfTheRequest(): void
    {
        $context = $this->get(Context::class);
        $visibilityAspect = new VisibilityAspect(includeHiddenPages: true);
        $context->setAspect('visibility', $visibilityAspect);

        $this->subject()->findByUids([1, 2], true);

        $this->assertSame($visibilityAspect, $context->getAspect('visibility'));
    }
}
