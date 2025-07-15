# Upgrade 2.0

## X.Y.Z

### BREAKING CHANGES

#### BREAKING: `ProfilesController::selectedProfilesAction()` no longer dispatches `ModifyListProfilesEvent`

`ProfilesController::selectedProfilesAction()` dispatched the `ModifyListProfilesEvent`
PSR14 event accidentally due to copy&paste when introducing the new plugin and action
for `2.0.x`. This event is no longer dispatched for this action, instead the new and
correct event [ModifySelectedProfilesEvent](#feature-modifyselectedprofilesevent-profilescontrollerselectedprofilesaction)
is now dispatched.

#### BREAKING: Removed partials

Some partials got removed as the templating structure has changed. Those partials include:

`Resources/Private/Partials/List/AlphabetPagination.html`
`Resources/Private/Partials/List/ListItem.html`
`Resources/Private/Partials/List/Pagination.html`
`Resources/Private/Partials/SelectedContracts/ListItem.html`

> [!NOTE]
> The default templating now supports basic bootstrap styling and is semantically optimized
> to also not lack any major accessibility.

#### BREAKING: Replace constructor DI with inject-methods in `AbstractProfileFactory`

Using constructor dependency injection in abstract classes defines the constructor
as API, which should be avoided by using the inject-method approach and allows to
implement classes using constructor DI without the requirement to deal and align
with parent (abstract) class constructor and passing it down.

`AbstractProfileFactory` used constructor DI and therefore violated the above
described design pattern.

Constructor DI arguments are now replaced with inject-methods in the abstract
`\FGTCLB\AcademicPersons\Profile\AbstractProfileFactory`.

Implementation using the abstract and defining own constructor DI arguments
needs to remove the removed parent arguments and avoid calling the parent
constructor.

Additionally, the `\Symfony\Contracts\Service\Attribute\Required` attribute is
used for the inject methods to tell symfony DI that these inject methods needs
to be called and are mandatory - beside having a visually glue for developers.

See [Autowiring other Methods (e.g. Setters and Public Typed Properties)](https://symfony.com/doc/current/service_container/autowiring.html#autowiring-other-methods-e-g-setters-and-public-typed-properties)

### FEATURES

#### FEATURE: Introduce localized pageTitleFormat placeholder (`LLL:EXT:`)

It's a valid use-case to use a pageTitleFormat for the person
profile detail page view as HTML page title including localized
text as placeholders and could be implemented using the PSR-14
event `ModifyProfileTitlePlaceholderReplacementEvent` dispatched
in the `ProfileTitleProvider`.

Localization is a generic feature and it's most likely that it's
use-full for a broader audience this change adds now support for
localization placeholder in the format:

```
%%LLL:EXT:<extension-key>/Resources/.../locallang.xlf:identifier%%
```

Note that no context fallback detection is made like within fluid
templates or extbase context areas and a valid relative path for
the default language file within a extension needs to be provided.

Functional tests are added to cover the new feature basically and
provide some examples, using a dedicated test fixture extension.

#### FEATURE: Introduce `ModifyProfileTitlePlaceholderReplacementEvent` in `ProfileTitleProvider`

With recent changes a series of features has been implemented to
make the HTML title tag for person profile pages more flexible,
with a placeholder based FlexForm options and also allowing to
influence the default and the setting pageTitleFormat.

The used `ProfileTitleProvider` already looks into the format
string and provides the ability to replace placeholders, which
matches getters in the profile.

To provide even more flexibility, this change introduces a new
PSR-14 Event `ModifyProfileTitlePlaceholderReplacementEvent`,
which is dispatched for each placeholder enriched with quite a
handfull of use-full context information.

Following methods are available on the event:

* `getPluginControllerActionContext(): PluginControllerActionContextInterface`
  containing the request along with easy access methods to site,
  siteLanguage and extbase plugin information and the plugin
  settings.
* `getProfile(): Profile` the current person profile to display.
* `getPlaceholder()` the original/raw placeholder identifier.
* `getReplacement()` the value to replace the placeholder with,
  which may differ already if a earlier event listener changed
  the value using `setReplacement()`.
* `setReplacement(string $replacement): void` to set the value
  used to replace the placeholder.

This event allows project to implement custom placeholders and
the replacement without using old-school xclassing technique.


#### FEATURE: Allow modifying default and settings pageTitleFormat for detail view

It's possible to set a pageTitleFormat in the plugin settings for
plugins using the `ProfileController::detailAction()`, which is
used in `ProfileTitleProvider` to set the HTML page title for the
person profile pages.

This change extends the existing `ModifyDetailProfileEvent`, which
is dispatched in the `ProfileController::detailAction()`, to make
the default and the setting pageTitleFormat changeable using an
PSR-14 event listener.

This gives developers the ability to implement a wide range of
use-cases in projects, for example adding a prefix to the format
based on the site configuration or similar.

New `ModifyDetailProfileEvent` methods:

* `getDefaultPageTitleFormat(): string`
* `setDefaultPageTitleFormat(string $defaultPageTitleFormat): void`
* `getSettingsPageTitleFormat(): string`
* `setSettingsPageTitleFormat(string $settingsPageTitleFormat): void`
* `getPageTitleFormatToUse(): string`

The `getPageTitleFormatToUse(): string` is a calculated function to
get the aggregated format to use, which allows checking the result
in event listeners and determine the format finally used as detail
view page title.

The original default pageTitleFormat is `ProfileTitleProvider::DETAIL_PAGE_TITLE_FORMAT`.

#### FEATURE: Introduce new PSR-14 events `ModifySelectedProfilesEvent` and `ModifySelectedContractsEvent`

New PSR-14 events are introduced which are dispatched in `ProfileController`
actions.

##### FEATURE: `ModifySelectedProfilesEvent` (ProfilesController::selectedProfilesAction())

`ProfileController::selectedProfilesAction()` dispatches now the new PSR-14
`ModifySelectedProfilesEvent` instead of erroneous copied listAction event
`ModifyListProfilesEvent`, which is no longer dispatched. That should not
be that of an issue for most implementations.

The event provides following methods:

* `getProfiles(): QueryResultInterface` return current result set.
* `setProfiles(QueryResultInterface $profiles): void` to allow setting a custom
  resultset.
* `getView(): FluidViewInterface|CoreViewInterface` return the current view to
  allow assigning custom values to the view.
* `getPluginControllerActionContext(): PluginControllerActionContextInterface`
  to provide more context information, see [FEATURE `PluginControllerActionContext`](#feature-introduce-plugincontrolleractioncontext-suitable).

##### FEATURE: `ModifySelectedContractsEvent` (ProfilesController::selectedContractsAction())

`ProfileController::selectedContractsAction()` dispatches now the new PSR-14
`ModifySelectedContractsEvent`.

The event provides following methods:

* `getContracts(): QueryResultInterface` return current result set.
* `setContracts(QueryResultInterface $contracts): void` to allow setting a custom
  resultset.
* `getView(): FluidViewInterface|CoreViewInterface` return the current view to
  allow assigning custom values to the view.
* `getPluginControllerActionContext(): PluginControllerActionContextInterface`
  to provide more context information, see [FEATURE `PluginControllerActionContext`](#feature-introduce-plugincontrolleractioncontext-suitable).

#### FEATURE: Introduce `PluginControllerActionContext` suitable

A new readonly DTO object `PluginControllerActionContext` is introduced and is
attached to dispatched PSR-14 events in `ProfileController` actions.

Following main getters are provided:

*   `getApplicationType(): ApplicationType` to return the TYPO3 application type
    for the current request.
*   `getExtbaseRequestParameters(): ?ExtbaseRequestParameters` to retrieve extbase
    attribute from request as a simple accessor.
*   `getRequest(): ServerRequestInterface` to return the current request.
*   `getSettings(): array` to retrieve raw plugin settings (TypoScript, FlexForm).
*   `getSite(): ?Site` to retrieve resolved site configuration.
*   `getLanguage(): ?SiteLanguage` to retrieve resolves site language.

Following getters dispatches to `ExtbaseRequestParameters` methods and returning
null in case the request attribute is not set in the request:

* `getActionName(): ?string`
* `getControllerName(): ?string`
* `getControllerObjectName(): ?string`
* `getControllerExtensionKey(): ?string`
* `getControllerExtensionName(): ?string`
* `getPluginName(): ?string`

#### `pageTitleFormat` FlexForm option for person detail view

It's now possible to define the format used to generate the HTML PageTitle for
the detail view of persons in the frontend, using the TYPO3 PageTitle API.

The default format used based on `Profile` extbase model data is:

```
%%TITLE%% %%FIRST_NAME%% %%MIDDLE_NAME%% %%LAST_NAME%%
```

To allow easier customization in project, a new FlexForm option `pageTitleFormat`
has been added to `listanddetail` plugin and as single new option for the `detail`
plugin, which uses `TCA type=input` combined with a ValuePicker to allow picking
from a list of pre-defined formats while still making it possible to define own
custom format directly on plugin usage.

The mapping from placeholder to extbase model is based on transforming the
placeholder to camelcase using first character after separators and prefix
it with `get`, and if the getter exists it is called to retrieve the value.

For example:

```
PLACEHOLDER...: %%FIRST_NAME%%
CAMEL_CASE....: FirstName
PREFIXED......: getFirstName

which calls `Profile->getFirstName()` to retrieve the replacement value from
the detail view profile.
```

The whole process contains some behaviour, which needs to be kept in mind:

* Leading and trailing spaces are trimmed from each value(placeholder).
* Multiple spaces are removed from the whole format string.
* Leading and trailing spaces are trimmed from the whole format pattern, after
  placeholder resolving has been processed.

Example for allowed characters as placeholder identifier:

```
%%SOME.IDENTIFIER%%
%%SOME:IDENTIFIER%%
%%SOME;IDENTIFIER%%
%%SOME-IDENTIFIER%%
%%SOME_IDENTIFIER%%
%%SOME/IDENTIFIER%%
%%SOME\IDENTIFIER%%
%%SOME IDENTIFIER%%
```
Note that most of them has no handling for matching person profile getters, but
are use-full for advanced replacement using the experimental PSR-14 event.

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
