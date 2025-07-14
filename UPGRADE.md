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
