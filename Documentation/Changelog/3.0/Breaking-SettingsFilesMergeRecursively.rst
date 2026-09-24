..  _breaking-settings-files-merge-recursively:

==========================================
Breaking: Settings files merge recursively
==========================================

..  seealso::
    :ref:`upgrade` is the order in which the 3.0 changes have to be applied.

Description
===========

:file:`Configuration/AcademicPersons/Settings.yaml` is collected from every
active package, and the files are now folded onto each other **recursively**
instead of top-level key by top-level key. A site package states the keys it
changes, at any depth, and keeps everything it does not name - including the
entries a later :guilabel:`academic_persons` release adds.

Four rules decide what happens to a value, and they hold at every depth:

..  list-table::
    :header-rows: 1

    *   -   The later file has
        -   Result
    *   -   a map
        -   merged key by key with the earlier map
    *   -   a list
        -   replaces the earlier list as a whole, an empty list included
    *   -   a value of another type
        -   replaces the earlier value
    *   -   :yaml:`null` (:yaml:`~`)
        -   removes the key, as if no package had configured it

A list is replaced rather than combined because the entries of a flag list
have no identity to merge by: an override that could only add entries could
never drop :yaml:`required` from :yaml:`[required]`. A YAML map whose keys
happen to be ``0`` to ``n-1`` is a list as well - nothing in the shipped file
has that shape - and an empty map, :yaml:`{}`, is the same empty array as an
empty sequence, so it clears a map the way :yaml:`[]` clears a list.

The key order of a merged map is the order of the later file when that file
names **every** key of the earlier map; otherwise the earlier order stays and
the keys only the later file names are appended. Order is display order for
the :yaml:`profile` entries, :yaml:`special.<component>.fields`,
:yaml:`contracts.fields`, :yaml:`contracts.contactSections` and
:yaml:`documentSections`.

..  important::
    A file decides the order only while it names every key of the map. A copy
    that reordered the entries keeps its order today, and **loses it to the
    upstream order as soon as a later** :guilabel:`academic_persons` **release
    adds an entry it does not name** - the copy stops being complete, so the
    upstream order applies, the added entry included, wherever upstream put
    it. A project that depends on its own order adds the new key to its file
    when it updates; there is no way to state an order for a map that is only
    partly named.

The same loader serves every settings file an academic extension reads through
:guilabel:`academic_base`; :guilabel:`academic_jobs` has an implementation of
its own and is unaffected.

Impact
======

**An entry is no longer removed by leaving it out, at any depth.** Leaving a
key out used to be the only way to drop something, and it now means "do not
change it". Wherever an override restated a map without one of its entries,
that entry is back and configured as :guilabel:`academic_persons` ships it -
and the level it sits on makes no difference:

*   a **field, section or document section** the override does not list is
    offered again;
*   a **key inside a restated entry** is inherited again. The recipe this
    manual gave for "making the profile names editable again" is exactly that
    shape: it restates every field of :yaml:`profile` and leaves the
    :yaml:`validators` key off :yaml:`firstName`, :yaml:`middleName` and
    :yaml:`lastName`. Those three now inherit the shipped
    :yaml:`[readonly, disabled]` again and are locked, in the backend and in
    the editing frontend.

Nothing fails in either case: the settings are valid, they just say what
upstream says. **An override therefore has to be compared with the shipped
file key by key, not map by map.** The status report of EXT:reports and
``academic:persons:settings:migrate --delta`` do that comparison and name the
entries a map that looks copied leaves out - see
:ref:`feature-settings-override-report-and-delta` for when a map looks copied.

An override behaves as it did only where it is complete at every level. A
file that named one entry of a map used to be the whole map; it is now that
one change on top of everything :guilabel:`academic_persons` ships.

Two packages that both still ship a pre-3.0 :yaml:`validations` or
:yaml:`profileInformationsTypes` key now contribute a combined map, where the
last one alone used to win. The migration command reports both packages as it
did before, and folds their files the way the runtime does.

Affected Installations
======================

Every installation whose site package ships
:file:`Configuration/AcademicPersons/Settings.yaml` and leaves anything out of
a map it restates - a field, a section, or a key inside a field it declares.
That includes the full copies the 2.x and 3.0 manuals asked for: being
complete on the top level says nothing about the levels below it.

Migration
=========

#.  Compare the override with
    :file:`EXT:academic_persons/Configuration/AcademicPersons/Settings.yaml`
    **at every level**, and collect every key the shipped file has and the
    override does not. A missing :yaml:`validators` list, a missing field and
    a missing section are all inherited from now on.
    :bash:`vendor/bin/typo3 academic:persons:settings:migrate --delta` prints
    those keys as comments, for every package after
    :guilabel:`academic_persons`; it takes a map for a copy when the package
    restates at least two of its entries unchanged, or when the map is part of
    a copy.
#.  Decide per key: keep the inheritance, or state it. An entry that is to be
    gone is set to :yaml:`~`, a flag list that is to be empty is set to
    :yaml:`[]`:

    ..  code-block:: yaml

        profile:
          # before: the field was left out of the copied map to remove it
          # now:
          middleName: ~
          # before: the field was restated without its `validators` key to
          # unlock it
          # now:
          firstName:
            validators: []

#.  Reduce the override to the keys that differ from the shipped file, once
    the two above are settled. It is not required, but it is the point of the
    recursive merge: what is not named follows upstream. The file ``--delta``
    prints is that reduced file, the :yaml:`~` lines of the previous step
    added.
#.  Flush the TYPO3 caches. The normalised graph is cached in the core cache.

..  index:: Configuration, Frontend, Backend, ext:academic_persons
