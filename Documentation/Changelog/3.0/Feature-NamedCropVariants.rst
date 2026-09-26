..  _feature-1790440966:

==================================================
Feature: Named crop variants for the profile image
==================================================

Description
===========

The image cropper of a profile image offers three named crop variants, so a
template of a site package can request a square or a portrait crop by its name:

..  list-table::
    :header-rows: 1

    *   -   Name
        -   Aspect ratio
    *   -   `default`
        -   Free, 16:9, 3:2, 4:3 and 1:1
    *   -   `square`
        -   1:1
    *   -   `portrait`
        -   3:4

`default` is the variant TYPO3 offers when a file field configures none, with
the same ratios, so a crop an editor stored before the update keeps its meaning,
and the templates of the extension, which render `default`, show the same image
as before.

..  code-block:: html
    :caption: A template of a site package

    <f:image image="{profile.image}" cropVariant="portrait" maxWidth="400" />

See :ref:`configuration-crop-variants`.

Impact
======

The cropper shows the new variants next to `default`. Nothing renders
differently until a template requests one of them.

An image carries a crop for `square` and `portrait` only once an editor opens it
in the backend form and saves the record; until then a template that requests
one of them renders the image uncropped. That applies to every profile image
that exists before the update, and to one written without the backend form, by
an import for example.

A project that already adds crop variants of its own to these images in TCA, at
:php:`$GLOBALS['TCA']['tx_academicpersons_domain_model_profile']['columns']['image']['config']['overrideChildTca']['columns']['crop']['config']['cropVariants']`,
keeps a variant it sets by name, and its editors see the variants of the
extension it does not define next to its own. A project that assigns the whole
array there replaces the variants of the extension.

Crop variants a project configures on `sys_file_reference` for every image are
merged with those of the extension on the profile image: the values of the
extension win key by key, and a ratio the project adds to a variant of the same
name stays. A project that restricted `default` to a fixed ratio that way finds
the ratios of the TYPO3 default offered there again.

A variant a site does not want is disabled on the field:

..  code-block:: php
    :caption: Configuration/TCA/Overrides of the site package

    $GLOBALS['TCA']['tx_academicpersons_domain_model_profile']['columns']['image']['config']['overrideChildTca']['columns']['crop']['config']['cropVariants']['portrait']['disabled'] = true;

Not with page TSconfig: `TCEFORM.sys_file_reference.crop.config.cropVariants`
reaches every image below the page, and leaves an image field that configures no
variants without any, `default` included.

.. index:: Backend, TCA, ext:academic_persons
