<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\ViewHelpers;

use FGTCLB\AcademicPersons\Domain\Model\Contract;
use FGTCLB\AcademicPersons\Domain\Model\Email;
use FGTCLB\AcademicPersons\Domain\Model\Profile;
use FGTCLB\AcademicPersons\Settings\AcademicPersonsSettingsFactory;
use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\DateTimeAspect;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Core\View\ViewFactoryInterface;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * The shipped position and contact partials of the detail view, rendered with the settings
 * graph a site package's `Settings.yaml` produces: the position block limited to contracts
 * valid today, the contact block to the first contract. The graph comes from the settings
 * normaliser, so what is asserted is the path from the YAML keys to the rendered blocks.
 *
 * The shipped settings, which keep every contract in both blocks, are pinned by
 * {@see \FGTCLB\AcademicPersons\Tests\Functional\Plugins\AcademicPersonsContractDisplayPolicyTest}
 * through a frontend request.
 */
final class ContractsInDetailPartialsTest extends AbstractAcademicPersonsTestCase
{
    private const NOW = '2026-03-10 14:30:00';

    protected function setUp(): void
    {
        parent::setUp();
        $this->get(Context::class)->setAspect('date', new DateTimeAspect(new \DateTimeImmutable(self::NOW)));
    }

    /**
     * "Emeritus" ended last year, "Professor" and "Dean" are valid; each has an e-mail
     * address of its own.
     */
    private function profile(): Profile
    {
        /** @var ObjectStorage<Contract> $contracts */
        $contracts = new ObjectStorage();
        foreach ([
            ['Emeritus', '2025-12-31', 'emeritus@example.com'],
            ['Professor', null, 'professor@example.com'],
            ['Dean', null, 'dean@example.com'],
        ] as [$position, $validTo, $email]) {
            $contract = new Contract();
            $contract->setPosition($position);
            $contract->setValidTo($validTo === null ? null : new \DateTime($validTo . ' 00:00:00'));
            $emailAddress = new Email();
            $emailAddress->setEmail($email);
            $contract->addEmailAddress($emailAddress);
            $contracts->attach($contract);
        }
        $profile = new Profile();
        $profile->setContracts($contracts);

        return $profile;
    }

    private function renderBlocks(): \DOMXPath
    {
        $publicProfile = $this->get(AcademicPersonsSettingsFactory::class)->normalize([
            'profile' => [
                'details' => [
                    'position' => ['special' => 'datasFromContracts', 'onlyValid' => true],
                    'contact' => ['special' => 'datasFromContracts', 'contracts' => 'first'],
                ],
            ],
        ])->publicProfile;
        $view = $this->get(ViewFactoryInterface::class)->create(new ViewFactoryData(
            templateRootPaths: [__DIR__ . '/../Fixtures/Templates/ContractsViewHelper/'],
            partialRootPaths: ['EXT:academic_persons/Resources/Private/Partials/'],
            // A backend request: in a frontend one the contact block's e-mail links would need a
            // content object renderer and its heading a site language, which a page rendering
            // provides and this test does not. The selection is the same in both.
            request: (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE),
        ));
        $view->assignMultiple(['profile' => $this->profile(), 'publicProfile' => $publicProfile]);

        $document = new \DOMDocument();
        $document->loadHTML('<?xml encoding="utf-8" ?>' . $view->render('DetailPartials'), LIBXML_NOERROR);

        return new \DOMXPath($document);
    }

    /**
     * @return list<string>
     */
    private function texts(\DOMXPath $xpath, string $class): array
    {
        $texts = [];
        $query = sprintf("//*[contains(concat(' ', normalize-space(@class), ' '), ' %s ')]", $class);
        foreach ($xpath->query($query) ?: [] as $node) {
            $texts[] = trim((string)preg_replace('#\s+#u', ' ', $node->textContent));
        }
        return $texts;
    }

    #[Test]
    public function thePositionBlockShowsOnlyTheContractsValidToday(): void
    {
        $this->assertSame(['Professor', 'Dean'], $this->texts($this->renderBlocks(), 'academic-persons-detail__position'));
    }

    /**
     * The contact block shows the first contract of the three, although it has ended: the
     * validity option is the position block's, the blocks are configured one by one.
     */
    #[Test]
    public function theContactBlockShowsOnlyTheFirstContract(): void
    {
        $contacts = $this->texts($this->renderBlocks(), 'academic-persons-detail__contact-contract');

        $this->assertCount(1, $contacts);
        $this->assertStringContainsString('emeritus@example.com', $contacts[0]);
    }
}
