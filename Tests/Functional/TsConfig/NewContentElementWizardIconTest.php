<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\TsConfig;

use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * The new content element wizard item of every component carries the extension
 * icon, the same one as its CType item.
 *
 * The wizard merges the items of this page TSconfig over the items it builds from
 * TCA, and an item of both with the same default values is taken from page
 * TSconfig, so its "iconIdentifier" is the icon an editor sees there. The card,
 * selected profiles and selected contracts items used the core icon
 * "actions-user" (ACE-859).
 *
 * Each page of the fixture includes the page TSconfig of one component through
 * "pages.tsconfig_includes", the registration that reaches a page without a site
 * set.
 */
final class NewContentElementWizardIconTest extends AbstractAcademicPersonsTestCase
{
    /**
     * @return \Generator<string, array{0: int, 1: string}>
     */
    public static function componentDataProvider(): \Generator
    {
        yield 'profile list' => [2, 'academicpersons_list'];
        yield 'profile list and detail' => [3, 'academicpersons_listanddetail'];
        yield 'profile detail' => [4, 'academicpersons_detail'];
        yield 'profile card' => [5, 'academicpersons_card'];
        yield 'selected profiles' => [6, 'academicpersons_selectedprofiles'];
        yield 'selected contracts' => [7, 'academicpersons_selectedcontracts'];
    }

    #[Test]
    #[DataProvider('componentDataProvider')]
    public function wizardItemCarriesTheExtensionIcon(int $pageId, string $contentElementType): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/NewContentElementWizardIcon/pages.csv');

        $elements = BackendUtility::getPagesTSconfig($pageId)['mod.']['wizards.']['newContentElement.']
            ['wizardItems.']['academic.']['elements.'] ?? [];

        $this->assertArrayHasKey($contentElementType . '.', $elements);
        $this->assertSame('persons_icon', $elements[$contentElementType . '.']['iconIdentifier'] ?? null);
    }
}
