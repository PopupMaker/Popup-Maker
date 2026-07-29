# Pro-Owned Licensing Compatibility Migration

## Purpose

This transition separates WordPress.org Core from remote Pro installation while
preserving compatibility with released Pro and addon versions. It deliberately
keeps a small legacy licensing facade in Core because released Pro 1.0.0 through
1.1.0 resolve Core's `license` service unconditionally during `init`.

The transition is capability-driven. Code must not infer behavior from a
distribution name, edition constant, or repository.

## Capability contract

Core exposes `PopupMaker\licensing_capabilities()` and the
`popup_maker/licensing_capabilities` filter. Stable plugin slugs identify the
current owner.

| Capability | Transition Core default | New Pro active |
| --- | --- | --- |
| `contract_version` | `1` | `1` |
| `license_provider` | `popup-maker` | `popup-maker-pro` |
| `license_ui_owner` | `popup-maker` | `popup-maker-pro` |
| `pro_updates_owner` | `popup-maker-pro` | `popup-maker-pro` |
| `addon_updates_owner` | `addon` | `addon` |
| `remote_installation` | `false` | `false` |
| `legacy_core_license_service` | `true` | Reports whether it existed before any facade |
| `legacy_extension_updater` | Feature-detected | Preserved only for historical compatibility |

Providers preserve unknown keys when filtering the contract. Consumers obtain
services defensively and test the required method before calling it.

Core alone owns no remote installation capability. Without Pro, the settings
page provides only an external purchase/download link. With released Pro, Core
shows and operates its compatibility license field. With new Pro, Pro claims
the provider and UI capabilities, and Core's license lifecycle becomes inert.

## Ownership

### Core

Core retains the historical `license` service, license REST facade,
`PUM_Extension_License`, and `PUM_Extension_Updater` during this release. These
exist solely to prevent released Pro and addons from failing. Core gates its
field, template, form handler, cron work, refresh requests, and updater
registration on current ownership so the facade does not duplicate Pro work.

Core no longer contains the Connect service, remote install or activation REST
routes, webhook handling, silent upgrader, installer skin, or their JavaScript.
Production `.org` archives are checked after creation for forbidden paths and
patterns.

### Pro

Pro owns the new license service, field, template, activation/deactivation
handlers, REST endpoints, and namespaced updater. Its resolver:

1. Delegates to legacy Core when Core has no capability contract and already
   provides licensing.
2. Claims ownership when Core advertises the contract.
3. Claims ownership and supplies a temporary Core `license` facade when a
   future Core omits that service.
4. Deduplicates updater registration by plugin basename.
5. Leaves Pro features enabled if licensing is unavailable and shows an
   administrator notice.

### Ecommerce Popups and LMS Popups

Both current addons require Pro for their product features. They obtain the Pro
compatibility resolver defensively, ask it to register one namespaced updater,
and otherwise continue running with an actionable update notice. They no longer
read Core's `license` service or instantiate `PUM_Extension_Updater`.

The packaged Ecommerce 1.0.2 and LMS 1.0.1 archives were inspected. Both
historical packages directly read Core's `license` service and instantiate
`PUM_Extension_Updater`; neither contains an independent license provider.
Those packages therefore require the Core compatibility facade until their
minimum supported versions are raised.

## Preserved storage

No destructive option migration runs. Pro reads and continues to use:

- `popup_maker_license` for structured key, status, and auto-activation data.
- `popup_maker_pro_license_key` inside `popup_maker_settings` for the historical
  settings value.
- `POPUP_MAKER_PRO_LICENSE` for constant-based activation without persisting
  the raw constant value.
- `popup_maker_license_status_check` as the existing cron hook name.

Customers do not need to re-enter or reactivate a license. When structured
storage has no key, Pro lazily recognizes the historical settings value and
does not write merely to migrate it.

## Release order

Transition Core and new Pro may ship independently, in either order:

1. Release transition Core to WordPress.org and new Popup Maker Pro as
   independently scheduled releases.
2. Release new Ecommerce Popups and LMS Popups only after the new Pro package
   is available.

New Pro deliberately delegates to older/private Core, so Pro does not depend on
transition Core shipping first. Transition Core keeps released Pro 1.1.0 and
historical addons operational, so Core does not depend on new Pro shipping
first. Only the new addon releases depend on the resolver shipped by new Pro.

## Compatibility matrix

| Combination | Expected result |
| --- | --- |
| Transition `.org` Core + released Pro 1.1.0 | Core facade owns license UI/lifecycle; no fatal; no remote installer |
| Transition `.org` Core + new Pro | Pro owns UI, license requests, REST, and updates; Core facade is inert |
| Older/full/private Core + new Pro | Core retains license lifecycle/UI; Pro uses Core key with its namespaced updater |
| Future Core without license/updater services + new Pro | Pro owns licensing and exposes a Core `license` facade for compatible consumers |
| Transition Core alone | No license field; external Pro link only; no remote installation |
| New Pro with missing/incompatible Core | prerequisite notice; Pro container is not booted; no fatal |
| New Ecommerce/LMS + new Pro + transition Core | Pro resolver supplies license key; exactly one updater per addon |
| New Ecommerce/LMS without resolver | addon features continue; private updates pause; actionable admin notice |
| Older/private Core + all new private plugins | Core owns one field/lifecycle; Pro owns updater instances; no duplicate basename |
| Settings page and `init` | ownership is resolved at hook execution; exactly one lifecycle owner |

Released Ecommerce 1.0.2 and LMS 1.0.1 cannot support a Core that entirely
removes the legacy license and updater surfaces. That combination remains
intentionally unsupported until minimum addon versions are established.

## Phase two

Phase two does not begin when transition Core or new Pro ships. It waits until
the new Pro, Ecommerce, and LMS releases are available and telemetry plus
support policy establish and enforce their minimum supported versions. The
smallest follow-up Core change is then:

1. Remove the `license` container service and `classes/Services/License.php`.
2. Remove Core's license field/template/form handling and license REST facade.
3. Remove `classes/Extension/License.php` and
   `classes/Extension/Updater.php`, including `PUM_Extension_License` and
   `PUM_Extension_Updater`.
4. Change Core defaults to a null provider/UI owner and set both legacy
   capability flags to `false`.
5. Remove compatibility-only tests and required-path allowances from the
   artifact verifier.

Already safe to remove in this transition were Connect, webhooks, remote
download/install/activation routes, silent upgrading, installer UI, and their
assets. Pro now owns the replacement license lifecycle and private updater.
Ecommerce and LMS require only Pro's resolver in their new versions. The code
remaining in `.org` Core is needed only for released Pro and historical addons,
and becomes removable once those minimum private-plugin versions are enforced.
