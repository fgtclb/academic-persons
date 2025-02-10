<?php
/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Boilerplate for a functional test phpunit boostrap file.
 *
 * This file is loosely maintained within TYPO3 testing-framework, extensions
 * are encouraged to not use it directly, but to copy it to an own place,
 * usually in parallel to a FunctionalTests.xml file.
 *
 * This file is defined in FunctionalTests.xml and called by phpunit
 * before instantiating the test suites.
 */
(static function () {
    $testbase = new \TYPO3\TestingFramework\Core\Testbase();
    $testbase->defineOriginalRootPath();
    $testbase->createDirectory(ORIGINAL_ROOT . 'typo3temp/var/tests');
    $testbase->createDirectory(ORIGINAL_ROOT . 'typo3temp/var/transient');

    // Iterate over all fixture extensions and register them to allow the composer
    // package name to be used in functional tests as `$testExtensionToLoad`.
    $composerPackageManager = (new \TYPO3\TestingFramework\Composer\ComposerPackageManager());
    $iterator = new \DirectoryIterator($composerPackageManager->getRootPath() . '/Tests/Functional/Fixtures/Extensions');
    /** @var \SplFileInfo $info */
    foreach ($iterator as $info) {
        if ($info->isDot() || !$info->isDir()) {
            continue;
        }
        $composerPackageManager->getPackageInfoWithFallback($info->getPathname());
    }
})();
