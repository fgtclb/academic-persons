<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Fgtclb\AcademicPersons\Types;

class PhoneNumberTypes extends AbstractTypes
{
    public function initialize(): void
    {
        $this->loadTypesByExtensionConfigurationProperty('types/phoneNumberTypes');
    }
}
