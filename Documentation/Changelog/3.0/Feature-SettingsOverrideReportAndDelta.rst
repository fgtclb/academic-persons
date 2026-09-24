..  _feature-settings-override-report-and-delta:

===================================================
Feature: Settings overrides are reported and shrunk
===================================================

Description
===========

Since the settings files are merged per entry, an override needs to name only
what it changes - see :ref:`breaking-settings-files-merge-recursively`. Two
tools help turn an existing copy into that shape, and show what a copy
inherits in the meantime.

**The status report** of EXT:reports lists, under *Academic Persons*, every
package whose :file:`Configuration/AcademicPersons/Settings.yaml` removes
entries with :yaml:`~` or leaves entries out of a map it copied, each as a
dotted path such as ``contracts.fields.room``. An entry a copy leaves out is
inherited from the packages loaded before it, so it is back after the update;
only the integrator knows whether the copy meant to remove it, and the entry is
a notice until that is decided; a package that only removes is an info. A map
counts as copied when the package restates at least two of its entries
unchanged - in any key order, and also where upstream has added keys to them
since - and so does every map inside a copy, because a copied map used to
replace everything below it. A file that neither removes nor leaves anything
out of a copy is not listed. The rule is a heuristic and errs both ways: a
copy that restates at most one entry of a map unchanged, and sits in no other
copy, is taken for a delta, and a delta that restates two unchanged keys next
to its change is taken for a copy - the report then names more, never less.

**The console command** ``academic:persons:settings:migrate`` has a
``--delta`` option. It prints, for every package after the first one, the
smallest file that has the same effect as the package's file, and the entries
the package's copied maps leave out as comment lines:

..  code-block:: bash

    vendor/bin/typo3 academic:persons:settings:migrate --delta

..  code-block:: yaml

    # my_site: Configuration/AcademicPersons/Settings.yaml
    # Left out of a copied map, and inherited because the files are merged per entry.
    # Add an entry with "~" where leaving it out was meant to remove it:
    #   contracts.fields.room: ~
    contracts:
      fields:
        validTo:
          validators:
            - required
            - date
        officeHours: ~

The printed file is exact: replacing the package's file with it changes
nothing, the order of the entries included. A copy that names every entry of
an upstream map decides the order of that map, so where leaving out its
unchanged entries would change the order - the copy sorts them differently, or
places a new entry among them - the map is printed with every entry it names.
A field that gained a :yaml:`validators` list between two of its shipped keys
is therefore printed whole. A package whose file repeats upstream values only
is named with a line saying the file can be dropped.

The option never writes a file and always exits with ``0``. Without it the
command migrates the pre-3.0 keys and exits as before.

Impact
======

Nothing changes for an installation that uses neither. To shrink an override:

#.  Run the command with ``--delta``.
#.  For every commented entry, decide whether leaving it out was meant to
    remove it, and add it with :yaml:`~` if so.
#.  Replace the package's file with the result, and flush the TYPO3 caches.

The first package that ships the file is the base of the comparison and is
never listed; on an installation that is :guilabel:`academic_persons` itself.

..  index:: Configuration, CLI, Backend, ext:academic_persons
