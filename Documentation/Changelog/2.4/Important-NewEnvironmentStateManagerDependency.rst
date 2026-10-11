.. _important-ace-254-academic-persons:

=====================================================================
Important: New required dependency `fgtclb/environment-state-manager`
=====================================================================

Description
===========

EXT:academic_persons now depends on the standalone extension
:composer:`fgtclb/environment-state-manager` and declares it in both
:file:`composer.json` (`"fgtclb/environment-state-manager": "^1.0"`) and
:file:`ext_emconf.php` (`'environment_state_manager' => '1.0.0-1.99.99'`).

The extension was switched from the internal, now deprecated
:php:`\FGTCLB\AcademicBase\Environment` subsystem to the extracted
:composer:`fgtclb/environment-state-manager` extension (namespace
:php:`\FGTCLB\EnvironmentStateManager`), which it uses in its profile command
services. The dependency is therefore required at runtime.

Impact
======

Composer-managed installations pull :composer:`fgtclb/environment-state-manager`
in automatically when EXT:academic_persons is updated to 2.4. No action is
required.

Classic, non-Composer installations (TER, extension manager) must install the
extension `environment_state_manager` in addition to EXT:academic_persons.
Otherwise the extension cannot be activated.

Affected Installations
======================

Only non-Composer installations updating EXT:academic_persons to 2.4 need to
install the additional extension manually. Composer-managed installations are
unaffected.

.. index:: PHP, ext:academic_persons
