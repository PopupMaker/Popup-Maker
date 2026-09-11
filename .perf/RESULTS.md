# Canonical popup model cache — before/after

Baseline: develop `21e59a1d`, measured in a **separate clean worktree** with its
own `composer install` (symlinking `vendor/` between worktrees makes PHPUnit
silently load the other checkout's code). "After" measured on this branch.

Reproduce:

```bash
export WP_TESTS_DIR="$TMPDIR/wordpress-tests-lib"
PUM_BENCH_POPUPS=<n> vendor/bin/phpunit -c tests/php/phpunit.xml \
  --filter Popup_Model_Cache_Bench
```

Query counts, hydration counts and distinct-object counts are **deterministic**.
`elapsed_ms` and `mem_delta_kb` are single samples and are **noisy**.

## Fixture correctness (this invalidated an earlier draft of this file)

Popups must be seeded with `data_version` meta. Real popups always carry it. A
popup without it makes `PUM_Model_Popup::setup()` run
`update_meta( 'data_version', … )` during a *read* request — a SELECT + UPDATE
per popup — and those writes invalidate the per-post meta cache that `WP_Query`
had just primed in bulk, forcing a second, per-popup meta fetch on readback.

With an unseeded fixture the same pass measured 30 queries at n=10 and 150 at
n=50. That was an artifact of the fixture, not plugin behavior. Seeded, the
numbers below are flat. The `data_version` write path is worth a look as its own
issue, but it is **not** what this PR is about and is not counted as a win here.

## SQL queries — unchanged, and correctly so

| scenario | n | before | after |
|----------|---|--------|-------|
| D query + readback | 1 | 2 | 2 |
| D query + readback | 10 | 3 | 3 |
| D query + readback | 50 | 3 | 3 |

Three queries regardless of popup count: one posts query, one term query, one
bulk postmeta prime for all IDs. **Readback issues zero queries** at every size.
The plugin already hydrates by querying all popups once and priming meta in
bulk; this PR does not change that and does not need to.

## Model hydrations (object constructions) — the actual win

| scenario | n | before | after | change |
|----------|---|--------|-------|--------|
| C cross-API, one popup | any | 2 | **1** | −50% |
| D query + readback | 10 | 20 | **10** | −50% |
| D query + readback | 50 | 100 | **50** | −50% |

Before, the repository `query()` hydrated a model for every popup, and the
readback through `pum_get_popup()` hydrated a *second* one because it consulted
a different cache. Now there is exactly one model per popup per request.

## Object identity

| scenario | before | after |
|----------|--------|-------|
| C: `pum_get_popup()` vs modern repo vs `pum()->popups` | **2 distinct** | **1 shared** |

Before, a mutation applied through one API was invisible through another, and a
reference held across a settings write kept reporting the pre-write value.

## Memory (noisy, single sample)

| n | before | after |
|---|--------|-------|
| 10 | 90.8 KB | 58.8 KB |
| 50 | 456.1 KB | 292.4 KB |

Roughly −36%, consistent with halving live model count.

## Staleness

Scenario E reports `is_fresh: yes` before and after at every count. With shared
identity the previously held reference now also reports the fresh value.

## Honest summary

This change buys **object-graph efficiency and correctness**, not fewer queries:

- half the model constructions per request
- one shared model per popup instead of two divergent ones
- ~36% less incremental memory on the popup pass
- a fixed stale-read through a held reference

Query count was already optimal and is untouched.
