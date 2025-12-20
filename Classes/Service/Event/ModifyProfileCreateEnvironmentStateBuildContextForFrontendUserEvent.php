<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Service\Event;

use FGTCLB\AcademicBase\Environment\StateBuildContext;
use FGTCLB\AcademicPersons\Service\ProfileCreateCommandService;

/**
 * This event is dispatched in {@see ProfileCreateCommandService::bootstrapSuitableEnvironmentForFrontendUser()}
 * and can be used to modify the {@see StateBuildContext} used to bootstrap a dedicated environment for the
 * `$frontendUserRecord`.
 *
 * Passing `NULL` to {@see ModifyProfileCreateEnvironmentStateBuildContextForFrontendUserEvent::setStateBuildContext()}
 * prevents bootstrapping a dedicated environment for the user record and effectively not changing the environment.
 *
 * @internal as considered experimental like the whole environment state manager and build implementation and not part of public API.
 */
final class ModifyProfileCreateEnvironmentStateBuildContextForFrontendUserEvent
{
    /**
     * @param array<string, mixed> $frontendUserRecord
     */
    public function __construct(
        private readonly array $frontendUserRecord,
        private readonly StateBuildContext $defaultStateBuildContext,
        private ?StateBuildContext $stateBuildContext = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function getFrontendUserRecord(): array
    {
        return $this->frontendUserRecord;
    }

    public function getDefaultStateBuildContext(): StateBuildContext
    {
        return $this->defaultStateBuildContext;
    }

    public function getStateBuildContext(): ?StateBuildContext
    {
        return $this->stateBuildContext;
    }

    public function setStateBuildContext(?StateBuildContext $stateBuildContext = null): self
    {
        $this->stateBuildContext = $stateBuildContext;
        return $this;
    }
}
