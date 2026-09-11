# Query audit: write-on-read in popup/theme models

Follow-up from the `data_version` finding.

**Conclusion up front: this is not an ongoing performance problem.** Every
backfill is one-time and self-healing. An earlier revision of this document
presented the un-backfilled figures as if they were a standing cost; they are
not, and that framing was wrong. Corrected below.

## The pattern

Five getters write to postmeta during a read:

| # | Site | Meta written |
|---|------|--------------|
| 1 | `PUM_Model_Popup::setup()` L1442 | `data_version` |
| 2 | `PUM_Model_Popup::is_enabled()` L1125 | `enabled` |
| 3 | `PUM_Model_Popup::get_event_count()` L1256 | `popup_{event}_count` |
| 4 | `PUM_Model_Popup::get_event_count()` L1266 | `popup_{event}_count_total` |
| 5 | `PUM_Model_Theme::setup()` L559 | `popup_theme_data_version` |

Each writes once when the key is absent, then the key exists and the write never
repeats. The transient cost is that `update_post_meta()` invalidates that post's
`post_meta` cache entry, discarding the bulk prime `WP_Query` just performed —
so meta is re-fetched per popup for the remainder of *that one request*.

## Measured: it heals after exactly one page load

25 popups seeded with no backfill meta, consecutive simulated requests:

| request | frontend (`query()` + `pum_is_popup_loadable()` ×25) | admin list (5 getters ×25) |
|---------|------------------------------------------------------|-----------------------------|
| #1 | 154 | 429 |
| #2 | **4** | 50 |
| #3 | **4** | 50 |
| #4 | **4** | 50 |

Frontend drops to 4 queries and stays there. One page load, clean afterwards.

A write counter hooked to `update_post_metadata` / `add_post_metadata` confirms
**0 meta writes** across three post-heal requests. The backfills genuinely fire
once.

### The admin "50" was a probe artifact, not plugin behavior

The residual 50 above came from the probe calling `wp_cache_flush()` between
requests, which clears WordPress's *post* cache as well as postmeta. A real page
load does not do that. The SQL confirmed it: `SELECT * FROM posts WHERE ID = N`
plus a single-post meta prime, per popup — `get_post()` refilling a cache the
probe had just emptied.

Re-measured with plugin request-local caches reset but the WP object cache left
intact (i.e. a site with a persistent object cache, or simply the same request
lifecycle WordPress actually has):

| request | admin list (5 getters ×25) |
|---------|----------------------------|
| #1 | 25 |
| #2 | **0** |
| #3 | **0** |

Steady state is zero queries.

## Steady state, verified

Popups with backfill meta present — what a normal site looks like:

| pass | n | queries |
|------|---|---------|
| `pum_get_all_popups()` | 25 | 4 |
| `get_title()` + `is_enabled()` + `get_setting()` ×25 | 25 | 0 |
| `pum_get_all_popups()` repeat, same args | 10 | 0 |
| `pum_get_all_themes()` | 6 | 4 |
| per-theme `get_setting()` ×6 | 6 | 0 |
| frontend preload + loadable checks | 25 | 4 |

Healthy throughout. Bulk prime works; request-local caches work.

## Live site state

All 9 popups have `data_version`, `enabled`, and the open counts. Two lack
`popup_conversion_count_total`. All 6 themes have `popup_theme_data_version`. So
the backfill will fire at most twice more on this site, ever.

## Verdict

Not a performance issue. The behaviour is a deliberate one-time settle after
something new happens (fresh install, import, programmatic insert, new event
type), which is exactly what a backfill is for. The only scenario where it would
recur is one where the write cannot persist — a genuinely read-only database or
a failing object cache — and in that case the write-on-read is the least of the
problems.

No change recommended, and none made. Documented so the pattern is not
re-discovered and mistaken for a leak later.

## One genuine (small) observation retained

`get_event_count()` has no request-local memo, so repeated calls within a single
request re-read through `get_post_meta()`. With a warm object cache that is cache
hits rather than SQL, so the cost is negligible. `Admin/Popups.php` calls it up
to 6× per row; memoizing would save object-cache lookups, not queries. Cosmetic,
not worth a change on its own.
