# Popup Settings Loss — Root Cause, Fixes, and Support Replies

**Status:** Fixed on branch `develop` (working tree). Tests green. Not committed.
**Date:** 2026-07-04

## TL;DR

Four HelpScout tickets (#48738 Martin, #46298 Paola/WPBakery, #47633 Nikunj, #46613
Markus), all tagged `popup settings deleted` / `settings reset`, plus FluentBoards
dev ticket #1189. **This is a Popup Maker save/migration bug, not caching and not the
customer's host.** The Cloudflare / "disable asset caching" framing in the current
replies is wrong and should be corrected.

## The fingerprint

Nikunj inspected his DB (PM 1.21.5) and reported `popup_settings` contained **only
theme-related values** — triggers/conditions/display gone. That is exactly what our
save code writes when it runs against an empty settings base: the two theme keys get
re-stamped automatically, everything else is absent.

## Root cause — multiple write paths, one destructive outcome

`popup_settings` is a single serialized post-meta blob. Several code paths could
overwrite it with a near-empty array:

| Path | Location | Mechanism | Fix |
|------|----------|-----------|-----|
| **P2** REST/React editor save | `RestAPI.php:402` → `update_settings($value, merge=true)` | Merged a partial submission into a poisoned/empty in-memory base, dropping everything not resubmitted. | Fresh read of base from DB + central destructive-write guard; surfaces `WP_Error` to the editor. |
| **P3** Write-on-read `theme_slug` | `Popup.php:get_theme_slug()` | Persisted `theme_slug` **during front-end render**; on an empty base wrote `{theme_id, theme_slug}` as the whole record. | Only backfill when the popup already has real content; also guarded centrally. |
| **P4** Classic/builder save | `Admin/Popups.php:save()` | A `save_post` from a page builder (WPBakery/BeTheme) or block editor lacks our `popup_settings` field → code treated absence as "clear everything." | Skip the settings write entirely when the field was not submitted (mirrors the existing title-field guard). |
| **P1** Passive migration | `migrations.php:pum_popup_migration_2()` | Folds legacy meta into `popup_settings` then **deletes the source keys**. A fold on an empty base would delete sources and leave a theme-only record. | Abort the write + source-key deletion if the fold would be destructive; legacy data survives for retry. |

Not vulnerable: the CTA model (different persistence, no merge/write-on-read), and the
install seed (`pum-install-functions.php`, fresh-install only).

## The central guard

`PUM_Model_Popup`:
- `settings_have_content($settings)` — true if the array has keys beyond the incidental
  `theme_id` / `theme_slug`.
- `is_destructive_settings_write($stored, $proposed)` — true only when `$stored` had
  content and `$proposed` would strip it to nothing.
- `update_settings()` now reads its merge base fresh from stored meta, applies the guard,
  and returns a `WP_Error` (logged via `pum_log_message`) instead of silently wiping.

## Tests

`tests/php/tests/PUM_Popup_Settings_Integrity_Test.php` — 7 tests, all green. Each
P2/P3/P4 test reproduced the wipe **before** the fix and passes after. Full suite: 884
tests, only 2 pre-existing unrelated `REST_Connect_Test` errors (fail identically on
baseline).

To run:
```bash
export PATH="/Applications/Local.app/Contents/Resources/extraResources/lightning-services/mysql-8.4.0/bin/darwin-arm64/bin:$PATH"
export WP_TESTS_DIR="/var/folders/.../T/wordpress-tests-lib"  # from bin/install-wp-tests.sh
vendor/bin/phpunit -c tests/php/phpunit.xml --filter PUM_Popup_Settings_Integrity_Test
```

## Open questions / follow-ups

- **What triggers the empty base in the wild?** The guard stops the *damage* regardless,
  but we still don't have the exact upstream cause of the empty read (object-cache/Redis
  miss? premature `get_settings()` during REST bootstrap?). The new `pum_log_message`
  calls will give telemetry on how often the guard fires. Worth watching after release.
- **"Deactivated popups reactivated"** (Martin) is a separate `post_status` path via REST
  field `default => 'publish'` — NOT addressed here. Needs its own investigation.

---

## Corrected support replies (DRAFT — do not send without review)

### To all four customers (shared core)

> Thanks for your patience — and apologies for the earlier troubleshooting that pointed
> at caching/Cloudflare. After investigating we've confirmed this is a bug in Popup
> Maker itself, not your host or CDN.
>
> In certain situations a save (or a plugin update's data migration, or even another
> plugin/page builder saving the popup post) could overwrite a popup's stored settings
> with an almost-empty record — leaving only the theme and wiping triggers, conditions,
> and display settings. That matches exactly what you saw.
>
> We've fixed this: saves that would erase an existing popup's settings are now refused,
> and the plugin surfaces an error instead of silently losing your configuration. The fix
> will ship in an upcoming release. If you can share a database backup from *before* the
> loss we may be able to help recover the affected popups; otherwise they'll need to be
> reconfigured once, and they will stay put after that.

### Nikunj (#47633) — add:

> You were right that `popup_settings` only contained theme data — that's the signature of
> this bug, and your DB inspection was exactly what let us pin it down. Thank you.

### Paola (#46298) — add:

> The WPBakery/BeTheme combination is relevant: a page builder saving the popup post
> without our settings field was one of the paths that could reset triggers. That specific
> path is now guarded.
