# Query audit: write-on-read in popup/theme models

Follow-up from the `data_version` finding. Measured on this branch with the WP
PHPUnit suite; every figure below was re-run **in isolation** because running
probes together pollutes counts via plugin bootstrap and `wp_cache_flush()`.

## The pattern

Five getters write to postmeta during a **read**:

| # | Site | Meta written | Called from |
|---|------|--------------|-------------|
| 1 | `PUM_Model_Popup::setup()` L1442 | `data_version` | every model construction |
| 2 | `PUM_Model_Popup::is_enabled()` L1125 | `enabled` | **frontend every page load**, admin list |
| 3 | `PUM_Model_Popup::get_event_count()` L1256 | `popup_{event}_count` | admin list table |
| 4 | `PUM_Model_Popup::get_event_count()` L1266 | `popup_{event}_count_total` | admin list table |
| 5 | `PUM_Model_Theme::setup()` L559 | `popup_theme_data_version` | every theme construction |

Each is a self-healing backfill and is *correct* — it writes once, then the meta
exists. The cost is not the write itself.

**The real cost is cache poisoning.** `update_post_meta()` invalidates that
post's entry in the `post_meta` object cache. `WP_Query` had just primed meta for
*all* popups in one bulk query. One backfill write per popup destroys that prime
row by row, so the next read re-fetches meta **one popup at a time**.

That is why a 3-query pass became 30 (n=10) or 150 (n=50) in the original
benchmark: 3 legitimate queries, then SELECT+UPDATE per popup, then a per-popup
meta re-prime.

## Measured, isolated

Popups **with** backfill meta (steady state — what a normal site looks like):

| pass | n | queries |
|------|---|---------|
| `pum_get_all_popups()` | 25 | **4** |
| `get_title()` + `is_enabled()` + `get_setting()` ×25 | 25 | **0** |
| `pum_get_all_popups()` repeat, same args | 10 | **0** |
| `pum_get_all_themes()` | 6 | **4** |
| per-theme `get_setting()` ×6 | 6 | **0** |

Healthy. Bulk prime works, request-local caches work.

Popups **without** backfill meta (fresh import, programmatic insert, migration):

| pass | n | queries |
|------|---|---------|
| hydrate via `pum_get_all_popups()` | 25 | **54** |
| admin list-table row render ×25 (what `Admin/Popups.php` actually calls) | 25 | **375** |
| second pass over the same rows | 25 | **25** |
| **frontend** `is_enabled()` ×25 | 25 | **75** |

375 queries to paint one admin list page. 75 queries on a **public page load**.

## Severity

On the live dev site this is close to dormant: all 9 popups have `data_version`,
`enabled`, and the open counts; 2 lack `popup_conversion_count_total`. All 6
themes have `popup_theme_data_version`. So the backfills fire at most twice ever
here and then stop.

It bites when popups arrive **without** going through the normal save path:

- imports / migrations / staging syncs
- programmatic `wp_insert_post()` (our own tests hit exactly this)
- `wp post create` via WP-CLI
- duplication plugins that copy a subset of meta

It is also permanently live for #2 in one specific way: `is_enabled()` is reached
from `is_loadable()`, which `preload_popups()` calls for **every popup on every
frontend request**. A site that never heals (e.g. an object-cache-only host where
writes fail, or a read-replica setup) pays this on every page view.

## Second-order issue: repeat cost after healing

Even once meta exists, `get_event_count()` ×25 on a second pass in the same
request costs **25 queries**, not 0 — because the first pass's writes evicted
each popup's meta cache. The model has no request-local memo for count values,
so it re-reads through `get_post_meta()` each time.

## Suggested fixes (not implemented — out of scope for PR #1407)

1. **Don't write during a read.** Return the default in-memory and let the value
   be persisted by a save path, or defer backfills to a single batched
   `shutdown` write. This removes the cache poisoning entirely.
2. If the write must stay, **batch it**: collect the popups needing backfill
   during the pass and issue one `update_meta_cache()` re-prime afterwards
   instead of letting each write invalidate individually.
3. **Memoize count reads** on the model so repeat `get_event_count()` calls in
   one request cost nothing.
4. Consider a one-time upgrade routine that backfills `data_version`, `enabled`,
   and the four count keys for all popups, so the read paths never need to.

Worth its own issue. None of this is touched by the canonical-cache PR, which
only changed *where models are cached*, not *what getters write*.
