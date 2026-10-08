<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Tca;

use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Every content element of the extension carries the extension icon in its CType
 * item and as its type icon.
 *
 * The CType item feeds the type select of the content element form and, on TYPO3
 * v13, the new content element wizard. The type icon is what the page module and
 * the list module show. The profile card, selected profiles and selected contracts
 * elements were registered with an empty icon, so they showed none in any of
 * those places (ACE-831).
 */
final class ContentElementIconTest extends AbstractAcademicPersonsTestCase
{
    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function contentElementTypeDataProvider(): \Generator
    {
        yield 'profile list' => ['academicpersons_list'];
        yield 'profile list and detail' => ['academicpersons_listanddetail'];
        yield 'profile detail' => ['academicpersons_detail'];
        yield 'profile card' => ['academicpersons_card'];
        yield 'selected profiles' => ['academicpersons_selectedprofiles'];
        yield 'selected contracts' => ['academicpersons_selectedcontracts'];
    }

    #[Test]
    #[DataProvider('contentElementTypeDataProvider')]
    public function contentElementCarriesTheRegisteredExtensionIcon(string $contentElementType): void
    {
        $item = null;
        foreach ($GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items'] ?? [] as $candidate) {
            if (($candidate['value'] ?? null) === $contentElementType) {
                $item = $candidate;
                break;
            }
        }

        $this->assertIsArray($item, sprintf('The CType item "%s" is not registered.', $contentElementType));
        $this->assertSame('persons_icon', $item['icon'] ?? null, 'The CType item carries another icon.');
        $this->assertSame(
            'persons_icon',
            $GLOBALS['TCA']['tt_content']['ctrl']['typeicon_classes'][$contentElementType] ?? null,
            'The content element type carries another type icon.',
        );
        $this->assertTrue(GeneralUtility::makeInstance(IconRegistry::class)->isRegistered('persons_icon'));
    }
}
