<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Imaging;

use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\ColourSchemeAwareIconsTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * Pins the icon of every content element this extension ships, in the two places that name
 * it: the TCA CType item, which `TcaManipulator::addContentElementPlugin()` also writes into
 * `tt_content.ctrl.typeicon_classes` for the page module, and the new content element wizard
 * entry in the page TSconfig of the component. Nothing ties the two together at runtime, so
 * they drifted before: three elements showed a core icon in the wizard and, with an empty
 * TCA icon, the `tt_content` default in the page module (ACE-859). The selected contracts
 * element shows the icon of the selected profiles element.
 *
 * The wizard entry is read from the page TSconfig of a page that includes the component file
 * through "Page TSconfig", one page per component - the same file the site set delivers.
 */
final class ContentElementIconsTest extends AbstractAcademicPersonsTestCase
{
    use ColourSchemeAwareIconsTrait;

    /**
     * @return \Generator<string, array{0: string, 1: string, 2: int}>
     */
    public static function contentElementIcons(): \Generator
    {
        yield 'profile list' => ['academicpersons_list', 'tx-academicpersons-plugin-persons', 2];
        yield 'profile list and detail' => ['academicpersons_listanddetail', 'tx-academicpersons-plugin-persons', 3];
        yield 'profile detail' => ['academicpersons_detail', 'tx-academicpersons-plugin-persons', 4];
        yield 'profile card' => ['academicpersons_card', 'tx-academicpersons-plugin-card', 5];
        yield 'selected profiles' => ['academicpersons_selectedprofiles', 'tx-academicpersons-plugin-selected-profiles', 6];
        yield 'selected contracts' => ['academicpersons_selectedcontracts', 'tx-academicpersons-plugin-selected-profiles', 7];
    }

    /**
     * @return \Generator<string, array{0: string, 1: string}>
     */
    public static function contentElementTypeIcons(): \Generator
    {
        foreach (self::contentElementIcons() as $label => [$contentElementType, $identifier]) {
            yield $label => [$contentElementType, $identifier];
        }
    }

    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function pluginIconIdentifiers(): \Generator
    {
        $identifiers = [];
        foreach (self::contentElementIcons() as [, $identifier]) {
            $identifiers[$identifier] = $identifier;
        }
        foreach ($identifiers as $identifier) {
            yield $identifier => [$identifier];
        }
    }

    #[Test]
    #[DataProvider('contentElementTypeIcons')]
    public function contentElementTypeItemCarriesItsIcon(string $contentElementType, string $identifier): void
    {
        $items = array_values(array_filter(
            $GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items'] ?? [],
            static fn(array $item): bool => ($item['value'] ?? null) === $contentElementType,
        ));

        $this->assertCount(1, $items, sprintf('The CType item "%s" is not registered once.', $contentElementType));
        $this->assertSame($identifier, $items[0]['icon'] ?? null);
    }

    /**
     * The page module and the record list read the icon of a content element from here, not
     * from the select item.
     */
    #[Test]
    #[DataProvider('contentElementTypeIcons')]
    public function contentElementTypeIconIsTheTypeIcon(string $contentElementType, string $identifier): void
    {
        $this->assertSame(
            $identifier,
            $GLOBALS['TCA']['tt_content']['ctrl']['typeicon_classes'][$contentElementType] ?? null,
        );
    }

    #[Test]
    #[DataProvider('contentElementIcons')]
    public function wizardEntryNamesTheIconOfTheContentElementType(
        string $contentElementType,
        string $identifier,
        int $pageId,
    ): void {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ContentElementPages.csv');

        $elements = BackendUtility::getPagesTSconfig($pageId)['mod.']['wizards.']['newContentElement.']['wizardItems.']['academic.']['elements.'] ?? [];
        $wizardIdentifier = $elements[$contentElementType . '.']['iconIdentifier'] ?? null;

        $this->assertSame($identifier, $wizardIdentifier);
        $this->assertSame(
            $GLOBALS['TCA']['tt_content']['ctrl']['typeicon_classes'][$contentElementType] ?? null,
            $wizardIdentifier,
            sprintf('The wizard and the page module show different icons for "%s".', $contentElementType),
        );
    }

    #[Test]
    #[DataProvider('pluginIconIdentifiers')]
    public function pluginIconIsRegisteredWithTheColourSchemeAwareProvider(string $identifier): void
    {
        $this->assertIconIsRegisteredWithCurrentColorProvider($identifier);
    }

    #[Test]
    #[DataProvider('pluginIconIdentifiers')]
    public function pluginIconIsInlinedInBothMarkups(string $identifier): void
    {
        $this->assertIconIsInlinedInBothMarkups($identifier);
    }

    #[Test]
    #[DataProvider('pluginIconIdentifiers')]
    public function pluginIconMarkupFollowsTheTextColour(string $identifier): void
    {
        $this->assertIconMarkupFollowsTheTextColour($identifier);
    }

    #[Test]
    #[DataProvider('pluginIconIdentifiers')]
    public function renderedPluginIconCarriesItsIdentifier(string $identifier): void
    {
        $this->assertRenderedIconCarriesItsIdentifier($identifier);
    }
}
