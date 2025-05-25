..  include:: /Includes.rst.txt

..  _installation:

============
Installation
============

This extension can be installed using the TYPO3 `extension manager
<https://extensions.typo3.org/extension/academic_persons>`__ or by `composer
<https://packagist.org/packages/fgtclb/academic-persons>`__.

..  code-block:: shell

    composer install \
        'fgtclb/academic-persons':'^2'

Testing 2.x.x extension version in projects (composer mode)
-----------------------------------------------------------

It is already possible to use and test the `2.x` version in composer based instances,
which is encouraged and feedback of issues not detected by us (or pull-requests).

Your project should configure `minimum-stabilty: dev` and `prefer-stable` to allow
requiring each extension but still use stable versions over development versions:

..  code-block:: shell

    composer config minimum-stability "dev" \
    && composer config "prefer-stable" true

and installed with:

..  code-block:: shell

    composer require \
        'fgtclb/academic-persons':'2.*.*@dev'
