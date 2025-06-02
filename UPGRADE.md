# Upgrade 2.0

## X.Y.Z

### BREAKING: Removed partials

Some partials got removed as the templating structure has changed. Those partials include:

`Resources/Private/Partials/List/AlphabetPagination.html`
`Resources/Private/Partials/List/ListItem.html`
`Resources/Private/Partials/List/Pagination.html`
`Resources/Private/Partials/SelectedContracts/ListItem.html`

> [!NOTE]
> The default templating now supports basic bootstrap styling and is semantically optimized
> to also not lack any major accessibility.

## 2.0.1

## 2.0.0

### BREAKING: Migrated extbase plugins from `list_type` to `CType`

TYPO3 v13 deprecated the `tt_content` sub-type feature, only used for `CType=list` sub-typing also known
as `list_type` and mostly used based on old times for extbase based plugins. It has been possible since
the very beginning to register Extbase Plugins directly as `CType` instead of `CType=list` sub-type, which
has now done.

Technically this is a breaking change, and instances upgrading from `1.x` version of the plugin needs to
update corresponding `tt_content` records in the database and eventually adopt addition, adjustments or
overrides requiring to use the correct CType.

Relates to following plugins:

* academicpersons_detail
* academicpersons_list'
* academicpersons_listanddetail
* academicpersons_selectedcontracts
* academicpersons_selectedprofiles

> [!NOTE]
> An TYPO3 UpgradeWizard `academicPersons_pluginUpgradeWizard` is provided to migrate
> plugins from `CType=list` to dedicated `CTypes` matching the new registration.
