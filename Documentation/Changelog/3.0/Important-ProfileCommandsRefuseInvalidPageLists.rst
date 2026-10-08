.. _important-ace-870-academic-persons:

=====================================================================
Important: Profile create and update commands refuse wrong page lists
=====================================================================

Description
===========

:bash:`academic:createprofiles` and :bash:`academic:updateprofiles` read
:bash:`--include-pids` and :bash:`--exclude-pids` with
:php:`GeneralUtility::intExplode()`, which converts every part to an integer,
whatever it holds. A list such as ``abc`` became page ``0``, and ``100;110``
became page ``100``. The run ended with exit code ``0`` and
printed nothing, so a typo widened or narrowed it without notice.

Both commands now read the two options the way
:bash:`academic:cleanupprofiles` does, with the same code: every
comma-separated part must be a page uid. A list with any other part is refused
with an error message and the exit code ``2`` (invalid input), and nothing is
created or updated.

Both commands also report their result:

*   :bash:`academic:createprofiles` prints the number of created profiles.
    When it created none and ``profile.autoCreateProfiles`` is disabled in the
    extension configuration of ``academic_persons``, it says so, because that
    option is what makes the default profile factory create profiles at all. A
    profile factory chosen through the :php:`ChooseProfileFactoryEvent` may
    ignore the option, the hint names the default factory for that reason.
*   :bash:`academic:updateprofiles` prints the number of frontend users whose
    profiles it updated.

Impact
======

A scheduler task or a script that passes a page list with a part that is no
page uid fails now instead of running with a different list. Correct the list.
Spaces around the commas and empty parts are still accepted.

Affected Installations
======================

Installations that run the profile create or update command with
:bash:`--include-pids` or :bash:`--exclude-pids`.

.. index:: CLI, ext:academic_persons
