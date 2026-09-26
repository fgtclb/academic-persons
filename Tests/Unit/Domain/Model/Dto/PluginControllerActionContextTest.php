<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Unit\Domain\Model\Dto;

use FGTCLB\AcademicBase\Domain\Model\Dto\PluginControllerActionContextInterface;
use FGTCLB\AcademicPersons\Domain\Model\Dto\PluginControllerActionContext;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * The persons context is deprecated for 4.0 and handed to the persons events until then, so it
 * has to be what the `academic_base` context is: a listener written for the events of the other
 * academic plugins must accept it, and must find the content element on it.
 *
 * The plain pass-through of request, site, language and settings is covered by the functional
 * test of the same name, {@see \FGTCLB\AcademicPersons\Tests\Functional\Domain\Model\Dto\PluginControllerActionContextTest}.
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

    private function subject(ServerRequestInterface $request): PluginControllerActionContext
    {
        return new PluginControllerActionContext($request, []);
    }
}
