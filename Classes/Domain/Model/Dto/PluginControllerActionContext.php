<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Domain\Model\Dto;

use FGTCLB\AcademicPersons\Controller\ProfileController;
use FGTCLB\AcademicPersons\Event\ModifyDetailProfileEvent;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * Generic context object used to provide plugin controller action related context information, either in views
 * or dispatched events, for example {@see ModifyDetailProfileEvent} in {@see ProfileController::detailAction()}.
 *
 * @deprecated since 3.0, will be removed in 4.0 together with {@see PluginControllerActionContextInterface}.
 *             The persons plugins then hand the context of `academic_base` to their events.
 */
final class PluginControllerActionContext implements PluginControllerActionContextInterface
{
    /**
     * @param array<string, mixed> $settings
     */
    public function __construct(
        private readonly ServerRequestInterface $request,
        private readonly array $settings,
    ) {}

    public function getContentObjectRenderer(): ?ContentObjectRenderer
    {
        $attribute = $this->request->getAttribute('currentContentObject');
        return $attribute instanceof ContentObjectRenderer ? $attribute : null;
    }

    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }

    public function getApplicationType(): ApplicationType
    {
        return ApplicationType::fromRequest($this->request);
    }

    public function getSite(): ?Site
    {
        return $this->request->getAttribute('site');
    }

    public function getLanguage(): ?SiteLanguage
    {
        return $this->request->getAttribute('language');
    }

    public function getPluginName(): ?string
    {
        return $this->getExtbaseRequestParameters()?->getPluginName();
    }

    public function getControllerName(): ?string
    {
        return $this->getExtbaseRequestParameters()?->getControllerName();
    }

    public function getControllerObjectName(): ?string
    {
        return $this->getExtbaseRequestParameters()?->getControllerObjectName();
    }

    public function getActionName(): ?string
    {
        return $this->getExtbaseRequestParameters()?->getControllerActionName();
    }

    public function getControllerExtensionKey(): ?string
    {
        return $this->getExtbaseRequestParameters()?->getControllerExtensionKey();
    }

    public function getControllerExtensionName(): ?string
    {
        return $this->getExtbaseRequestParameters()?->getControllerExtensionName();
    }

    public function getExtbaseRequestParameters(): ?ExtbaseRequestParameters
    {
        $attribute = $this->request->getAttribute('extbase');
        return $attribute instanceof ExtbaseRequestParameters ? $attribute : null;
    }

    /**
     * @return array<string, mixed>
     */
    public function getSettings(): array
    {
        return $this->settings;
    }
}
