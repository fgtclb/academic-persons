.. _important-selected-profiles-sheet-title:

======================================================
Important: The selected profiles sheet is titled again
======================================================

Description
===========

The :guilabel:`Selected profiles` plugin declared the title of its settings
sheet inside a :xml:`<TCEforms>` element:

..  code-block:: xml
    :caption: EXT:academic_persons/Configuration/FlexForms/SelectedProfiles.xml

    <ROOT>
        <TCEforms>
            <sheetTitle>LLL:EXT:academic_persons/…:flexform.tab.settings</sheetTitle>
        </TCEforms>

TYPO3 removed that key from the parsed FlexForm array in v12 (breaking
#97126), so on every version this extension supports the title was not read at
all - it
stayed under a key nothing looks at, and :php:`ROOT.sheetTitle` was absent.

The wrapper is removed and the title moved one level up, which is how the
:guilabel:`Selected contracts` plugin next to it was already written.

Impact
======

The settings sheet of the :guilabel:`Selected profiles` content element shows
its title again instead of an untitled tab. Nothing else changes: the fields,
their order and the stored values are untouched.

Affected Installations
======================

All installations using the :guilabel:`Selected profiles` plugin.

Solution
========

None required. Extensions that ship their own FlexForms should check them for
the same wrapper - it is silently dropped rather than deprecated, so nothing
reports it.

.. index:: Backend, TCA, ext:academic_persons
