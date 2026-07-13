.. _important-ace-254-academic-persons:

==============================================================================
Important: New required dependency `fgtclb/environment-state-manager`
==============================================================================

Description
===========

:php:`EXT:academic_persons` now depends on the standalone extension
`fgtclb/environment-state-manager` and declares it consistently in both
:file:`composer.json` (:php:`"fgtclb/environment-state-manager": "^1.0"`) and
:file:`ext_emconf.php` (:php:`'environment_state_manager' => '1.0.0-1.99.99'`).

The extension was switched from the internal, now deprecated
:php:`\FGTCLB\AcademicBase\Environment` subsystem to the extracted
`fgtclb/environment-state-manager` extension (namespace
:php:`\FGTCLB\EnvironmentStateManager`), which it uses in its profile command
services. The dependency is therefore required at runtime.

Impact
======

Composer-managed installations pull `fgtclb/environment-state-manager` in
automatically when :php:`EXT:academic_persons` is updated to 2.4; no action is
required.

Classic, non-composer installations (TER / extension manager) must install the
`environment_state_manager` extension in addition to :php:`EXT:academic_persons`,
otherwise the extension cannot be activated.

Affected Installations
======================

Only non-composer installations updating to :php:`EXT:academic_persons` 2.4
need to install the additional extension manually. Composer-managed
installations are unaffected.

.. index:: PHP, ext:academic_persons
