.. _feature-profile-items-without-detail-link:

==================================================
Feature: Profile names can be shown without a link
==================================================

Description
===========

The name of every profile in the list, card, selected profiles and selected
contracts elements links to the profile's detail view. A site without profile
detail pages had no way to turn that off, and without a detail page the link
led to the page of the element, which showed the same list again.

The new setting :typoscript:`plugin.tx_academicpersons.detailLink` takes the
values `link`, the default, and `none`. With `none`, the names are shown as
text, in the table view mode as well. It is a site setting of the aggregate
set `fgtclb/academic-persons` and a constant of the shared static template,
with the same default in both.

The four elements offer the same choice in their plugin options,
:guilabel:`Link the names to the detail view`, with :guilabel:`Use the site
setting` as the default. A chosen value wins over the site setting in both
directions.

The list-and-detail element always links its names, because it shows the
detail view itself, and does not offer the choice. A detail page that a
template passes to the item partial links as well, and so do the contacts of
a page of :guilabel:`EXT:academic_contacts4pages`.

See :ref:`configuration-detail-link`.

Impact
======

Nothing changes until the setting or the choice of an element is set to
:guilabel:`No link`.

A project that overrides :file:`Profile/Item/DetailLink.html`, or copies
:file:`Profile/Item.html` together with the header partials to drop the link,
ignores the setting. A copy made only to drop the link can be removed, and the
site sets the setting to `none` instead. An override of the detail link
partial that is kept follows the setting when the last case, the one that
builds the address from :typoscript:`settings.detailPid`, checks it. In the
shipped partial that case reads:

..  code-block:: html

    <f:else if="{settings.detailLink} != 'none'">
        <f:variable
            name="profileDetailUri"
            value="{f:uri.action(
                pageUid: settings.detailPid,
                action: 'detail',
                arguments: {
                    profile: profile
                },
                pluginName: 'Detail',
                controller: 'Profile',
                extensionName: 'academicpersons'
            )}"
        />
    </f:else>

The cases for a passed :html:`detailPid` and for the list-and-detail element
stay as they are.

.. index:: Backend, Frontend, FlexForm, TypoScript, ext:academic_persons
