<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Domain\Model\Dto;

use FGTCLB\AcademicBase\Domain\Model\Dto\PluginControllerActionContextInterface as AcademicBasePluginControllerActionContextInterface;

/**
 * The context the persons plugin events carry. It is the `academic_base` interface under the
 * name the persons events have always declared, so a listener typed against either one works.
 *
 * @deprecated since 3.0, will be removed in 4.0. Type against
 *             {@see AcademicBasePluginControllerActionContextInterface} instead; the persons
 *             events declare it from 4.0 on.
 *
 * @api
 */
interface PluginControllerActionContextInterface extends AcademicBasePluginControllerActionContextInterface {}
