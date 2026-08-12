# Popup Maker Frontend Rendering Analysis

> This is a historical analysis of the v1.21.0 regression. The authoritative
> integration contract is now documented in
> [`page-builder-integration.md`](page-builder-integration.md). Current code uses
> `wp_enqueue_scripts:11`, after Beaver Builder's initialization callback, as
> the proven preload boundary for that regression.

## Executive Summary

Critical timing change identified: Popup preloading moved from `wp_enqueue_scripts:11` to `wp_head:0`, causing Beaver Builder CSS conflicts when popups contain BB templates.

## Critical Finding: Beaver Builder CSS Conflicts

### The Problem

When a popup contains a Beaver Builder template, CSS on the main page gets corrupted.

### Root Cause

```text
OLD: wp_enqueue_scripts:11 → BB is initialized → Popup shortcodes process → BB CSS properly scoped
NEW: wp_head:0 → Popup shortcodes process → BB NOT initialized → CSS leaks everywhere
```

### Why This Happens

1. Beaver Builder initializes its CSS isolation framework around `wp_enqueue_scripts:10`
2. OLD version processes popups at priority 11 - AFTER BB is ready
3. NEW version processes popups at `wp_head:0` - BEFORE BB exists
4. BB shortcodes in popups execute without proper CSS context
5. Result: BB styles leak into main page instead of being isolated

### Historical Working Threshold

Historical testing found both of these avoided the original breakage:

-   `add_action( 'wp_head', [ $this, 'preload_popups' ], 1 );`
-   `add_action( 'wp_enqueue_scripts', [ $this, 'preload_popups' ], 10 );`

The production integration uses `wp_enqueue_scripts:11` so Beaver Builder's
callback at priority 10 always finishes first, regardless of plugin registration
order.

## Complete Frontend Process Comparison

### OLD Version (v1.20.6) - Chronological Hook Execution

1. **`init` hook**

    - `PUM_Site::init()` → Registers site classes
    - `PUM_Site_Assets::init()` → Hooks asset management
    - `PUM_Site_Popups::init()` → Hooks popup loading

2. **`wp_enqueue_scripts:9`**

    - `register_styles()` → Registers popup CSS from `assets/css/`
    - `register_scripts()` → Registers popup JS from `assets/js/`

3. **`wp_enqueue_scripts:11`** ⭐

    - `PUM_Site_Popups::load_popups()` → Queries published popups
    - Processes popup content with `get_content()` → Executes shortcodes
    - Fires `pum_preload_popup` for each popup

4. **`the_content` filter**

    - `check_content_for_popups()` → Scans for `popmake-###` classes
    - Auto-loads additional popups found

5. **`wp_enqueue_scripts:100`**

    - `fix_broken_extension_scripts()` → ACTIVE (fixes AWeber/MailChimp)

6. **`wp_footer:19`**

    - `late_localize_scripts()` → Popup settings to JavaScript

7. **`wp_footer`**
    - `render_popups()` → Outputs popup HTML via WP_Query loop

### Regression Version (v1.21.0) - Chronological Hook Execution

1. **`init` hook**

    - `PopupMaker\Controllers\Frontend::init()` → Controller pattern
    - Dependency injection container setup

2. **`wp_head:0`** 🚨 **CRITICAL CHANGE**

    - `Frontend\Popups::preload_popups()` → MOVED HERE FROM wp_enqueue_scripts:11
    - Processes popup content → Executes shortcodes VERY EARLY

3. **`wp_enqueue_scripts:9`**

    - `register_styles()` → From `dist/assets/` (path changed)
    - `register_scripts()` → Enhanced dependency tracking

4. **`the_content` filter**

    - `check_content_for_popups()` → Improved regex pattern
    - `/class=[\'"][^"\']*?popmake-(\d+)[^"\']*?[\'"]/'`

5. **`wp_enqueue_scripts:100`**

    - `fix_broken_extension_scripts()` → COMMENTED OUT ❌

6. **`wp_footer:19`**

    - `late_localize_scripts()` → Uses new controller for data

7. **`wp_footer`**
    - `render_popups()` → Simple array iteration (no WP_Query)

## Process Mapping (OLD → NEW)

| OLD Process                    | Timing                  | NEW Process                    | Timing      | Change Impact                 |
| ------------------------------ | ----------------------- | ------------------------------ | ----------- | ----------------------------- |
| `load_popups()`                | `wp_enqueue_scripts:11` | `preload_popups()`             | `wp_head:0` | **-11 priorities earlier** 🚨 |
| WP_Query popup loop            | Runtime                 | Array-based storage            | Runtime     | Architecture change           |
| `assets/js/` paths             | -                       | `dist/assets/` paths           | -           | Build system change           |
| Basic popup regex              | -                       | Enhanced class regex           | -           | Better accuracy               |
| `fix_broken_extension_scripts` | Active                  | `fix_broken_extension_scripts` | **Removed** | Extension compatibility lost  |
| Direct WP_Query render         | `wp_footer`             | Array iteration                | `wp_footer` | Simplified                    |

## Unmigrated/Removed Processes

1. **`fix_broken_extension_scripts()`** - Completely commented out (fixes AWeber/MailChimp extensions)
2. **WP_Query loop context** - Replaced with arrays
3. **Late popup loading timing** - Everything loads earlier now

## Key Architecture Changes

### Asset Loading

-   **OLD**: `assets/js/` and `assets/css/`
-   **NEW**: `dist/assets/` unified directory

### Popup Management

-   **OLD**: WP_Query-based with `have_posts()`, `next_post()`
-   **NEW**: Array-based with repository pattern

### Dependency Management

-   **OLD**: Static dependency list
-   **NEW**: Dynamic bundled dependency tracking

## Recommended Solutions

### Immediate Fix

```php
// Move popup preloading back to safe timing.
// Replace the wp_head priority 0 callback with this registration.
add_action( 'wp_enqueue_scripts', [ $this, 'preload_popups' ], 11 );
```

Do not conditionally fall back to `wp_head:0` based on a list of detected page
builders. Builder detection is necessarily incomplete, and the early path can
break an integration that is active but not listed. Popup preloading should use
`wp_enqueue_scripts:11` consistently.

## Testing Verification

1. Create popup with Beaver Builder template
2. Check if main page CSS remains intact
3. Verify popup still displays correctly
4. Test with other page builders

## Notes

-   `fix_broken_extension_scripts()` is NOT related to page builders - it fixes old AWeber/MailChimp extension compatibility
-   The wp_head:0 timing is the definitive cause of the Beaver Builder CSS conflicts
-   Any priority lower than 1 for wp_head or 10 for wp_enqueue_scripts breaks Beaver Builder
