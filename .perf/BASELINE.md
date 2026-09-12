# Baseline metrics @ develop 21e59a1d

Environment: WP PHPUnit integration suite, single site, MySQL 8.0.35 (Local),
PHP CLI. Hydration counts and distinct-object counts are **deterministic**.
Elapsed time and memory are single samples and are **noisy**.

Reproduce:

```bash
export WP_TESTS_DIR="$TMPDIR/wordpress-tests-lib"
PUM_BENCH_POPUPS=<n> vendor/bin/phpunit -c tests/php/phpunit.xml \
  --testsuite bench
```

> **Superseded query counts.** The first run of this benchmark seeded popups
> without `data_version` meta and recorded 30 queries at n=10 and 150 at n=50.
> Those numbers measured the fixture, not the plugin: a popup missing
> `data_version` makes `PUM_Model_Popup::setup()` perform a SELECT + UPDATE
> during a read, and those writes invalidate the bulk postmeta cache that
> `WP_Query` had just primed, forcing per-popup meta refetches.
>
> Real popups always carry `data_version`. With a corrected fixture the popup
> pass is **3 queries flat at any popup count** (one posts query, one term
> query, one bulk postmeta prime) with **zero queries on readback**, both before
> and after this change. The table below reports the corrected values. See
> `.perf/RESULTS.md` for the full corrected comparison and `.perf/QUERY-AUDIT.md`
> for the `data_version` write-on-read analysis.

## Results (corrected fixture)

| n | scenario | queries | hydrations | distinct objects | notes |
|---|----------|---------|------------|------------------|-------|
| 0 | D frontend query | 2 | 0 | — | no popups, no hydration |
| 1 | A cold single | 1 | 1 | — | |
| 1 | B warm ×10 | 0 | 0 | 1 | warm path already good |
| 1 | C cross-API | 0 | 2 | **2** | identity split |
| 1 | D query+readback | 2 | **2** | 1 | 2 hydrations for 1 popup |
| 10 | A cold single | 1 | 1 | — | |
| 10 | B warm ×10 | 0 | 0 | 1 | |
| 10 | C cross-API | 0 | 2 | **2** | identity split |
| 10 | D query+readback | 3 | **20** | 10 | 2 hydrations per popup |
| 50 | D query+readback | 3 | **100** | 50 | hydrations scale linearly |

Scenario E (`stale_after_meta_write`) reads fresh at every count — the model's
settings provenance guard was already working. Any refactor must keep it so.

## The two defects the numbers prove

1. **Split identity (C).** Requesting one popup through `pum_get_popup()`,
   `plugin()->get('popups')->get_by_id()`, and `pum()->popups->get_item()`
   returns **2 distinct objects**. The modern and legacy repositories each
   hydrate their own instance, so a mutation applied to one is invisible to the
   other, and a reference held across a settings write reports the pre-write
   value.

2. **Double hydration (D).** Every popup is constructed **twice** per frontend
   pass: once by the repository `query()` and once again on readback through
   `pum_get_popup()`, which consulted a cache that never saw the query results.
   At 50 popups that is 100 model constructions where 50 would do.

**Query count is not one of the defects.** It was already 3 flat and is
unchanged by this work.
