<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersons\Routing\Aspect;

use Symfony\Component\DependencyInjection\Attribute\Exclude;
use TYPO3\CMS\Core\Routing\Aspect\PersistedAliasMapper;

/**
 * The core `PersistedAliasMapper`, except that a slug that cannot be one segment maps to
 * no segment at all, so the route is skipped and the value stays a query argument.
 *
 * The core mapper returns the slug as it is stored. The URL generator of Symfony checks it
 * against the requirement `[^/]+` of the route and throws for an empty slug and for one
 * with a slash, which takes the whole page down. A function type or an organisational
 * unit has an empty slug until the upgrade wizard `academicPersons_fillFilterSlugs` ran or
 * when an import wrote it without one, and an editor or an import can store a slash,
 * which the core keeps in a slug.
 *
 * Aspects are created by core's `AspectFactory` with `GeneralUtility::makeInstance()`
 * and never by the container, so the class is kept out of the container.
 *
 * @internal Used by the route enhancers this extension ships, not part of the public API.
 */
#[Exclude]
final class PersonsFilterSlugMapper extends PersistedAliasMapper
{
    public function generate(string $value): ?string
    {
        $slug = parent::generate($value);
        return $slug === null || $slug === '' || str_contains($slug, '/') ? null : $slug;
    }
}
