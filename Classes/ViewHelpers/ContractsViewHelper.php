<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\ViewHelpers;

use FGTCLB\AcademicPersons\Domain\Model\Contract;
use FGTCLB\AcademicPersons\Domain\Model\Profile;
use FGTCLB\AcademicPersons\Service\ContractSelection;
use FGTCLB\AcademicPersons\Service\ContractSelector;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Cache\CacheDataCollectorInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * The contracts of a profile a view shows, as configured by the plugin settings or by one
 * block of the detail view:
 *
 * ```html
 * <html xmlns:persons="http://typo3.org/ns/FGTCLB/AcademicPersons/ViewHelpers" data-namespace-typo3-fluid="true">
 *
 * <f:for each="{persons:contracts(profile: profile, settings: settings)}" as="contract">...</f:for>
 * <f:for each="{persons:contracts(profile: profile, detailBlock: publicProfile.details.contact)}" as="contract">...</f:for>
 * ```
 *
 * `settings` reads `contracts.display` (`all`|`first`), `contracts.matchFilter` together
 * with `organisationalUnits` and `functionTypes`, and `contracts.onlyValid`. A
 * `detailBlock` reads `contracts` and `onlyValid` and wins over `settings`. Without any of
 * them every contract is returned, in the editor's order.
 *
 * While "only valid" applies, the page cache lifetime of the frontend request is limited
 * to the next midnight on which the selection changes, so a contract disappears on the
 * day after it ends and appears on the day it starts. Rendered outside a frontend page,
 * nothing is limited.
 */
final class ContractsViewHelper extends AbstractViewHelper
{
    /**
     * @var bool
     */
    protected $escapeOutput = false;

    public function __construct(
        private readonly ContractSelector $contractSelector,
        private readonly Context $context,
    ) {}

    public function initializeArguments(): void
    {
        // Not required, so a template handing in no profile renders no contract - as
        // looping over `{profile.contracts}` of a missing profile did - on Fluid 4 and 5 alike.
        $this->registerArgument('profile', Profile::class, 'The profile whose contracts are selected.', false);
        $this->registerArgument('settings', 'array', 'The plugin settings.', false, []);
        $this->registerArgument('detailBlock', 'array', 'One block of the detail view, "publicProfile.details.<block>"; wins over "settings".', false);
    }

    /**
     * @return list<Contract>
     */
    public function render(): array
    {
        $profile = $this->arguments['profile'] ?? null;
        if (!$profile instanceof Profile) {
            return [];
        }
        $detailBlock = $this->arguments['detailBlock'] ?? null;
        $settings = $this->arguments['settings'] ?? [];
        $selection = is_array($detailBlock)
            ? ContractSelection::fromDetailBlock($detailBlock)
            : ContractSelection::fromPluginSettings(is_array($settings) ? $settings : []);

        $result = $this->contractSelector->select($profile, $selection);
        if ($result->changesAt !== null) {
            $this->restrictPageCacheLifetime($result->changesAt);
        }

        return $result->contracts;
    }

    /**
     * The collector is the request's own object, so the limit reaches the page cache entry
     * of this request only. The next change is never earlier than the next midnight of the
     * same `date` aspect, so the lifetime is at least a second; the `max()` only guards
     * against a lifetime of 0, which the cache backends read as "unlimited".
     */
    private function restrictPageCacheLifetime(\DateTimeImmutable $changesAt): void
    {
        if (!$this->renderingContext instanceof RenderingContextInterface
            || !$this->renderingContext->hasAttribute(ServerRequestInterface::class)
        ) {
            return;
        }
        $request = $this->renderingContext->getAttribute(ServerRequestInterface::class);
        if (!$request instanceof ServerRequestInterface) {
            return;
        }
        $cacheDataCollector = $request->getAttribute('frontend.cache.collector');
        if (!$cacheDataCollector instanceof CacheDataCollectorInterface) {
            return;
        }
        $now = (int)$this->context->getPropertyFromAspect('date', 'timestamp');
        $cacheDataCollector->restrictMaximumLifetime(max(1, $changesAt->getTimestamp() - $now));
    }
}
