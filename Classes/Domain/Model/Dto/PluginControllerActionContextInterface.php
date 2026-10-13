<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Domain\Model\Dto;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;

interface PluginControllerActionContextInterface
{
    public function getRequest(): ServerRequestInterface;
    /**
     * The getter that can raise. Every request the frontend or the backend handled carries a type
     * this resolves. For a request built by hand without one, in a test or a command for example,
     * core raises its own exception, which is not caught here.
     *
     * @throws \RuntimeException with code 1606222812 when the request carries no application type
     * @throws \LogicException with code 1678875015 when the type is neither frontend nor backend
     */
    public function getApplicationType(): ApplicationType;
    public function getSite(): ?Site;
    public function getLanguage(): ?SiteLanguage;
    public function getPluginName(): ?string;
    public function getControllerName(): ?string;
    public function getControllerObjectName(): ?string;
    public function getActionName(): ?string;
    public function getControllerExtensionKey(): ?string;
    public function getControllerExtensionName(): ?string;
    public function getExtbaseRequestParameters(): ?ExtbaseRequestParameters;
    /**
     * @return array<string, mixed>
     */
    public function getSettings(): array;
}
