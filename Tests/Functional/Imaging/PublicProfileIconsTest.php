<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Imaging;

use FGTCLB\AcademicBase\Imaging\FrontendIconRegistry;
use FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider;
use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendIconsAssertionTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconProvider\AbstractSvgIconProvider;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Imaging\IconSize;

/**
 * The icons of the public profile detail view are frontend icons: registered in
 * `Configuration/FrontendIcons.php` and rendered by `ab:icon` of academic_base. The
 * backend never shows them, so the icon registry of the backend must not know them,
 * or a site that replaces one in `Configuration/Icons.php` sees no effect and no error.
 * The record icons and the plugin icon are the opposite case, backend icons only.
 *
 * The identifiers are spelled out here rather than read back out of the registration, so a
 * rename has to be made twice instead of silently agreeing with itself.
 */
final class PublicProfileIconsTest extends AbstractAcademicPersonsTestCase
{
    use FrontendIconsAssertionTrait;

    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function publicProfileIconIdentifiers(): \Generator
    {
        $identifiers = [
            'academic-persons-envelope',
            'academic-persons-phone',
            'academic-persons-address',
            'academic-persons-room',
            'academic-persons-clock',
            'academic-persons-detail-plus',
            'academic-persons-detail-minus',
        ];
        foreach ($identifiers as $identifier) {
            yield $identifier => [$identifier];
        }
    }

    #[Test]
    #[DataProvider('publicProfileIconIdentifiers')]
    public function publicProfileIconIsAFrontendIconWithTheColourSchemeAwareProvider(string $identifier): void
    {
        $this->assertFrontendIconIsRegisteredWithProvider($identifier, CurrentColorSvgIconProvider::class);
    }

    /**
     * The default markup is the inlined file, not an `<img>`, so the icon takes the text
     * colour of the page.
     */
    #[Test]
    #[DataProvider('publicProfileIconIdentifiers')]
    public function publicProfileIconIsInlinedInBothMarkups(string $identifier): void
    {
        $icon = $this->getFrontendIcon($identifier);
        $markup = $icon->getMarkup();

        $this->assertStringStartsWith('<svg', $markup);
        $this->assertStringNotContainsString('<img', $markup);
        $this->assertSame($markup, $icon->getAlternativeMarkup(AbstractSvgIconProvider::MARKUP_IDENTIFIER_INLINE));
        $this->assertFrontendIconMarkupFollowsTheTextColour($identifier);
    }

    #[Test]
    #[DataProvider('publicProfileIconIdentifiers')]
    public function renderedPublicProfileIconCarriesItsIdentifier(string $identifier): void
    {
        $this->assertRenderedFrontendIconCarriesItsIdentifier($identifier);
    }

    /**
     * Asked through the icon API of the backend, the way `core:icon` asks, the identifier
     * is unknown and the answer is TYPO3's placeholder.
     */
    #[Test]
    #[DataProvider('publicProfileIconIdentifiers')]
    public function publicProfileIconIsNoBackendIcon(string $identifier): void
    {
        $this->assertFrontendIconIsNotABackendIcon($identifier);
        $this->assertSame(
            'default-not-found',
            $this->get(IconFactory::class)->getIcon($identifier, IconSize::SMALL)->getIdentifier(),
        );
    }

    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function backendIconIdentifiers(): \Generator
    {
        yield from RecordIconsTest::recordIconIdentifiers();
        yield 'persons_icon' => ['persons_icon'];
    }

    /**
     * The record icons and the plugin icon are shown by the backend only, and stay out of
     * the frontend registry.
     */
    #[Test]
    #[DataProvider('backendIconIdentifiers')]
    public function backendIconIsNoFrontendIcon(string $identifier): void
    {
        $this->assertTrue($this->get(IconRegistry::class)->isRegistered($identifier));
        $this->assertFalse($this->get(FrontendIconRegistry::class)->isRegistered($identifier));
    }
}
