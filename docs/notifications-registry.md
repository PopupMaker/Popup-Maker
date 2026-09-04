# Notifications Registry

Central inventory of every notice registered through the `pum_alert_list` filter across the Popup Maker ecosystem. Keep this updated when adding or removing notices.

## Delivery channels

Popup Maker has two surfaces that read from the same `pum_alert_list` registry:

- **Legacy alerts widget** — the yellow/blue banner strip that appears at the top of Popup Maker admin screens. Renders *all* registered alerts (unless dismissed), regardless of category.
- **Notifications panel** — the slide-in panel triggered from the admin toolbar marker (this plugin). Shows only notices suitable for the panel: `feature`, `recommendation`, `announcement`, `offer`. Notices with `type: error | warning` or `global: true` stay on the legacy widget.

The legacy widget pre-dates categories. Notices without a `category` key default to `announcement` on the panel but still render on the legacy widget.

## Dismissal model

Dismissals are stored in the `_pum_dismissed_alerts` user-meta key per user. Values:

- `true` — permanent dismissal.
- Unix timestamp — snooze; alert reappears after the timestamp passes.

Two dismissal paths from the panel:

1. **Corner X** (`action: ''`) — permanent. Requires `dismissible: true` on the alert.
2. **Declared "Not now" button** (`action: 'dismiss'` + `expires: '30 days'`) — snooze per the action's `expires` field.

After successful dismissal, the REST endpoint fires `pum_alert_dismissed` so providers can run post-dismissal logic (e.g. `WhatsNew::on_dismiss` records that user's `last_seen` release).

Dismissal scope is deliberately not configurable per message: standard notification dismissals are always per user. A custom action may change site state when that is the action's actual purpose (for example, enabling telemetry), but clicking Dismiss or the corner X must not suppress a message for other users.

## Registry

### Core plugin (`popup-maker`)

| Code | Source | Category | Type | Destination | Notes |
|---|---|---|---|---|---|
| `translation_request_<version>` | `classes/Utils/Alerts.php:244` | — | `info` | Legacy widget | Locale-specific translation nag. Version-suffixed so a new release re-prompts. |
| `php_<future_version>_<plugin_version>` | `classes/Admin/Notices.php:325` | `warning` | `error` (global) | Legacy widget | Upcoming PHP min-req nag. Version-suffixed so it re-fires on each plugin release while the server hasn't been upgraded. Non-dismissible for `manage_options` users. |
| `wp_<future_version>_<plugin_version>` | `classes/Admin/Notices.php:340` | `warning` | `error` (global) | Legacy widget | Upcoming WordPress min-req nag. Same re-fire pattern as the PHP nag. |
| `pum_telemetry_notice` | `classes/Telemetry.php:260` | — | `info` | Legacy widget | Opt-in prompt for anonymous usage telemetry. Suppressed in Pro via `Pro\Controllers\Admin\Telemetry::remove_telemetry_alert`. |
| `review_request` | `includes/modules/reviews.php:353` | — | default | Legacy widget | Review nag, driven by usage triggers (form conversions, popup counts). Snooze actions: `maybe_later`, `already_did`, `never`. |
| `license_not_valid` | `classes/Extension/License.php:549` / `:562` | — | default | Legacy widget | Fires per extension when its license is invalid/expired. Extension-scoped. |
| `upgrades_required` | `classes/Utils/Upgrades.php:277` | — | `warning` | Legacy widget | Blocks until the user runs pending DB upgrades. Non-dismissible by design. |
| `pum_tip_alert` | `classes/Admin/Onboarding.php:56` | — | `info` | Legacy widget | Rotating onboarding tips for new users (first N admin sessions). |
| `pum_writeable_notice` | `classes/AssetCache.php:705` | — | `warning` | Legacy widget | Filesystem can't write asset cache. Stays on legacy widget (warning → not panel-eligible). |

**Removed on this branch (2026):** `pum_notice_<id>` (remote community-notice feed), `whats_new_1_8_0` (2018-era, inert), `integration_alerts` + `<integration>_integration_available` (BuddyPress addon unmaintained), `pum_bfcm_2024` (expired), `pum_block_editor_migration` (shipped).

### Admin Notifications Panel plugin (this plugin)

#### WhatsNew provider (`classes/Services/Notifications/WhatsNew.php`)

| Code | Category | Dismissible | Notes |
|---|---|---|---|
| `pm_whats_new_release_<major>_<minor>` | `feature` | Yes (permanent, per user) | Auto-generated release announcement. One shared release slot remains available until the next release so every eligible user can see it. The version-suffixed code and `pum_whats_new_last_seen` user meta keep dismissal and catch-up copy user-specific. Parses highlights from readme.txt between each user's `last_seen` and `latest`. |

Actions:
- **View changelog** — iframe to WP plugin-information screen (install_plugins cap) or public `/changelog/` link (fallback).
- **Dismiss** — permanent.

#### FeatureAnnouncements provider (`classes/Services/Notifications/FeatureAnnouncements.php`)

All four are panel-only (`feature` or `recommendation`), dismissible, and behaviorally gated — they only surface when the user has demonstrated usage matching the target scenario.

| Code | Category | Condition | Destination | Notes |
|---|---|---|---|---|
| `pm_feat_ctas_2026` | `feature` | No CTAs exist yet (`has_no_ctas`) | `admin.php` → CTAs screen + `/docs/apply-popup-maker/create-call-to-action-cta-popup/` | Announces the CTA system to users who haven't tried it. |
| `pm_upsell_exit_intent` | `recommendation` | 10+ form conversions AND exit-intent not enabled (`converts_without_exit_intent`) | `/features/popup-triggers/exit-intent-triggers/` | Pro upsell with conversion-lift math in the message body. "Not now" = 30-day snooze. |
| `pm_tip_adblock_bypass` | `recommendation` | Bypass setting off AND 10+ form conversions (`needs_adblock_bypass`) | PM Settings → Misc tab | Points at a free core setting. "Not now" = 30-day snooze. |
| `pm_upsell_scheduling` | `recommendation` | 3+ popups AND (≥1 disabled OR stale >90 days) (`needs_popup_scheduling`) | `/features/popup-targeting/popup-scheduling/` | Pro upsell. Message adapts based on whether signals are disabled popups vs. stale ones. "Not now" = 30-day snooze. |

Shared helpers (in `FeatureAnnouncements`):
- `cta_admin_url()` — WP admin CTA list.
- `settings_url( $tab )` — PM settings, given tab slug.
- `doc_url( $path, $campaign )` — `wppopupmaker.com/docs/<path>/` with UTM.
- `feature_url( $slug, $campaign )` — `wppopupmaker.com/features/<slug>/` with UTM. Supports nested slugs (`popup-triggers/exit-intent-triggers`).
- `upgrade_url( $campaign )` — wraps `\PopupMaker\get_upgrade_link()` → `wppopupmaker.com/pricing/` with UTM. Kept for future pricing-direct CTAs; current upsells route to feature pages instead.

#### PageBuilderAnnouncements provider (`classes/Services/Notifications/PageBuilderAnnouncements.php`)

| Code | Category | Condition | Destination | Notes |
|---|---|---|---|---|
| `pm_feat_page_builder_support_2026_<builder-slugs>` | `feature` | At least one bundled page builder adapter passed runtime detection and its availability check | `/integrations/page-builder-integrations/` | Consolidates every active supported builder into one announcement with permanent per-user dismissal. The stable builder slug suffix scopes dismissal to the detected set, so installing another supported builder can surface the new capability without loading inactive adapters. |

### Pro / Pro+ / extensions

| Plugin | Registered alerts | Notes |
|---|---|---|
| `popup-maker-pro` | — | Only *removes* the telemetry notice via `Pro\Controllers\Admin\Telemetry`. Doesn't register its own. |
| `popup-maker-lms-popups` | — | None. |
| `popup-maker-ecommerce-popups` | — | None. |

`license_not_valid` is registered once per extension instance via `License.php`, so the same code can appear multiple times in the alert list with different extension metadata.

## Adding a new notification

1. **Decide the surface.** If it's behavior-driven and educational/promotional, write a new **Provider** under `classes/Services/Notifications/` implementing `\PopupMaker\Services\Notifications\Provider`. If it's a system alert (error, filesystem, license, upgrade needed), add it to the core plugin's existing alert registration path.
2. **Give it a stable code.** Prefix with `pm_` for panel-surfaced notices. If content changes over time (e.g. version-tied), include the version in the code so dismissals are scoped (see `pm_whats_new_release_*`).
3. **Set `category`** for panel delivery (`feature`, `recommendation`, `announcement`, `offer`). Omit or use `warning`/`error` to stay on the legacy widget.
4. **Declare actions** with explicit `action` keys and `expires` if applicable. Remember:
   - `action: ''` + `dismissible: true` → corner X, permanent.
   - `action: 'dismiss'` + `expires: '30 days'` → snooze.
   - Custom actions must appear in the alert's `actions[]` or the REST endpoint rejects them.
5. **Register provider** via `popup_maker/notification_providers` filter (see `Manager.php`).
6. **Update this file.**

## Testing notices locally

```bash
# List every registered alert + dismissal state:
wp eval '
wp_set_current_user( 1 );
$alerts = apply_filters( "pum_alert_list", [] );
foreach ( $alerts as $a ) {
    $code = $a["code"] ?? "?";
    $dismissed = PUM_Utils_Alerts::has_dismissed_alert( $code ) ? "DISMISSED" : "active";
    printf( "[%s] %s  category=%s  type=%s\n",
        $dismissed, $code,
        $a["category"] ?? "-",
        $a["type"] ?? "-" );
}'

# Reset all dismissals for current user (testing):
wp eval 'update_user_meta( get_current_user_id(), "_pum_dismissed_alerts", [] );'

# Reset WhatsNew state:
wp option delete pum_whats_new_slot
wp user meta delete <user-id> pum_whats_new_last_seen
wp option delete pum_whats_new_last_seen # Legacy pre-per-user fallback.

# Flush FeatureAnnouncements transient cache (locale-scoped, 12h TTL).
# `wp cache flush` won't touch these — they live in the options table.
wp eval '
$index = (array) get_option( "pum_feature_announcements_cache_index", [] );
foreach ( $index as $key ) { delete_transient( $key ); }
delete_option( "pum_feature_announcements_cache_index" );
'
```
