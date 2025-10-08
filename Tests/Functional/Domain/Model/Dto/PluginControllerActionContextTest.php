<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Domain\Model\Dto;

use FGTCLB\AcademicPersons\Domain\Model\Dto\PluginControllerActionContext;
use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;

final class PluginControllerActionContextTest extends AbstractAcademicPersonsTestCase
{
    public static function extbaseRequestParametersRelatedGettersReturnNullIfAttributeIsMissingDataSets(): \Generator
    {
        $getters = [
            'getExtbaseRequestParameters',
            'getPluginName',
            'getControllerName',
            'getControllerObjectName',
            'getActionName',
            'getControllerExtensionKey',
            'getControllerExtensionName',
        ];
        foreach ($getters as $index => $getter) {
            yield sprintf('#%s %s', $index, $getter) => [
                'getterName' => $getter,
            ];
        }
    }

    #[DataProvider('extbaseRequestParametersRelatedGettersReturnNullIfAttributeIsMissingDataSets')]
    #[Test]
    public function extbaseRequestParametersRelatedGettersReturnNullIfAttributeIsMissing(string $getterName): void
    {
        $request = new ServerRequest();
        $pluginControllerActionContext = new PluginControllerActionContext($request, []);
        $this->assertTrue(method_exists($pluginControllerActionContext, $getterName));
        $this->assertNull($pluginControllerActionContext->{$getterName}());
    }

    public function getRequestReturnsExpectedRequest(): void
    {
        $request = new ServerRequest();
        $pluginControllerActionContext = new PluginControllerActionContext($request, []);
        $this->assertSame($request, $pluginControllerActionContext->getRequest());
    }

    #[Test]
    public function getSiteReturnsExpectedSite(): void
    {
        $siteStub = $this->createStub(Site::class);
        $request = (new ServerRequest())->withAttribute('site', $siteStub);
        $pluginControllerActionContext = new PluginControllerActionContext($request, []);
        $this->assertSame($siteStub, $pluginControllerActionContext->getSite());
    }

    #[Test]
    public function getLanguageReturnsExpectedSite(): void
    {
        $siteLanguageStub = $this->createStub(SiteLanguage::class);
        $request = (new ServerRequest())->withAttribute('language', $siteLanguageStub);
        $pluginControllerActionContext = new PluginControllerActionContext($request, []);
        $this->assertSame($siteLanguageStub, $pluginControllerActionContext->getLanguage());
    }

    #[Test]
    public function getSettingsReturnsExpectedSettings(): void
    {
        $settings = [
            'key1' => 'value123',
            'array1' => [
                789,
                123,
                456,
            ],
        ];
        $request = new ServerRequest();
        $pluginControllerActionContext = new PluginControllerActionContext($request, $settings);
        $this->assertSame($settings, $pluginControllerActionContext->getSettings());
    }
}
