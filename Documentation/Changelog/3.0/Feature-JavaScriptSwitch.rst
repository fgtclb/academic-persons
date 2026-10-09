..  _feature-1791566503:

=============================================================
Feature: The script of the public profile can be switched off
=============================================================

Description
===========

The template :file:`Templates/Profile/Detail.html` of the
:guilabel:`Profile detail` and the :guilabel:`Profile list and detail` content
elements registers the module
:js:`@fgtclb/academic-persons/frontend/profile.js`. An installation that brings
its own script had to override the whole template to leave it out.

The module is switchable now, per site:

..  list-table::
    :header-rows: 1

    *   -   Site setting and constant
        -   Default
    *   -   :yaml:`plugin.tx_academicpersons.assets.js`
        -   :yaml:`true`

It is declared by the site set `fgtclb/academic-persons`, with every other
setting of this extension:

..  code-block:: yaml
    :caption: config/sites/my-site/settings.yaml

    plugin.tx_academicpersons.assets.js: false

An installation that configures its frontend through :sql:`sys_template`
records sets the TypoScript constant of the very same name, in
:guilabel:`Constants`:

..  code-block:: typoscript
    :caption: Constants of the root sys_template record

    plugin.tx_academicpersons.assets.js = 0

Every academic extension that loads a script has this switch, named
:typoscript:`assets.js` below its own plugin namespace and switched on by
default, after the switch of the study plan content element.

Impact
======

Nothing changes for an installation that configures nothing: the profile
loads its script exactly as before.

Switched off, the page does not load it, and the markup is unchanged. **The
entries of the profile stay folded** until a script of the site opens them:
the template renders each panel with the attribute :html:`hidden`. See
:ref:`configuration-javascript` for the markup a script of your own addresses.

A project that overrides :file:`Templates/Profile/Detail.html` keeps whatever
its copy loads. The switch reaches it once it wraps its own
:html:`<f:asset.module>` in :html:`<f:if condition="{settings.assets.js}">`.

.. index:: Frontend, Fluid, TypoScript, ext:academic_persons
