<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Unit\Domain\Model\Dto;

use FGTCLB\AcademicBase\Domain\Model\Dto\PluginControllerActionContextInterface;
use FGTCLB\AcademicPersons\Domain\Model\Dto\PluginControllerActionContext;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Site\Entity\NullSite;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * The persons context is deprecated for 4.0 and handed to the persons events until then, so it
 * has to be what the `academic_base` context is: a listener written for the events of the other
 * academic plugins must accept it, and must find the content element on it.
 *
 * The plain pass-through of request, site, language and settings is covered by the functional
 * test of the same name, {@see \FGTCLB\AcademicPersons\Tests\Functional\Domain\Model\Dto\PluginControllerActionContextTest}.
 * What is covered here is what the getters answer when the request holds no usable value.
 */
final class PluginControllerActionContextTest extends UnitTestCase
{
    #[Test]
    public function isAnAcademicBasePluginControllerActionContext(): void
    {
        $this->assertInstanceOf(PluginControllerActionContextInterface::class, $this->subject(new ServerRequest()));
    }

    #[Test]
    public function getContentObjectRendererReturnsTheContentObjectOfTheRequest(): void
    {
        $contentObjectRenderer = $this->createStub(ContentObjectRenderer::class);
        $subject = $this->subject((new ServerRequest())->withAttribute('currentContentObject', $contentObjectRenderer));

        $this->assertSame($contentObjectRenderer, $subject->getContentObjectRenderer());
    }

    #[Test]
    public function getContentObjectRendererReturnsNullWithoutAContentObject(): void
    {
        $this->assertNull($this->subject(new ServerRequest())->getContentObjectRenderer());
    }

    /**
     * The attribute name is not reserved, and a listener reading the context is not in a
     * position to handle a `TypeError`.
     */
    #[Test]
    public function getContentObjectRendererReturnsNullForAForeignAttributeValue(): void
    {
        $subject = $this->subject((new ServerRequest())->withAttribute('currentContentObject', new \stdClass()));

        $this->assertNull($subject->getContentObjectRenderer());
    }

    #[Test]
    public function getSiteReturnsNullWithoutASite(): void
    {
        $this->assertNull($this->subject(new ServerRequest())->getSite());
    }

    /**
     * The backend stores a `NullSite` for page 0 and for a page outside any site. It implements
     * `SiteInterface` but is no `Site`, and it means "no site", which is what the getter answers.
     */
    #[Test]
    public function getSiteReturnsNullForTheNullSiteOfARequestOutsideAnySite(): void
    {
        $subject = $this->subject((new ServerRequest())->withAttribute('site', new NullSite()));

        $this->assertNull($subject->getSite());
    }

    #[Test]
    public function getSiteReturnsNullForAForeignAttributeValue(): void
    {
        $subject = $this->subject((new ServerRequest())->withAttribute('site', new \stdClass()));

        $this->assertNull($subject->getSite());
    }

    #[Test]
    public function getLanguageReturnsNullWithoutALanguage(): void
    {
        $this->assertNull($this->subject(new ServerRequest())->getLanguage());
    }

    #[Test]
    public function getLanguageReturnsNullForAForeignAttributeValue(): void
    {
        $subject = $this->subject((new ServerRequest())->withAttribute('language', new \stdClass()));

        $this->assertNull($subject->getLanguage());
    }

    private function subject(ServerRequestInterface $request): PluginControllerActionContext
    {
        return new PluginControllerActionContext($request, []);
    }
}
