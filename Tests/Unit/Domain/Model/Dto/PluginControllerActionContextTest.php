<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Unit\Domain\Model\Dto;

use FGTCLB\AcademicPersons\Domain\Model\Dto\PluginControllerActionContext;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Site\Entity\NullSite;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * The persons context is handed to the persons events and read by listeners that cannot check
 * what it was built from, so a getter declared nullable must answer null rather than fail with a
 * `TypeError` when the request holds no usable value. That is what is covered here.
 *
 * The plain pass-through of request, site, language and settings is covered by the functional
 * test of the same name, {@see \FGTCLB\AcademicPersons\Tests\Functional\Domain\Model\Dto\PluginControllerActionContextTest}.
 */
final class PluginControllerActionContextTest extends UnitTestCase
{
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
