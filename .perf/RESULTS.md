# Canonical popup model cache — before/after

Baseline: develop `21e59a1d`. Both columns measured with the same harness on the
same machine and the same database.

Reproduce:

```bash
export WP_TESTS_DIR="$TMPDIR/wordpress-tests-lib"
PUM_BENCH_POPUPS=<n> vendor/bin/phpunit -c tests/php/phpunit.xml \
  --filter Popup_Model_Cache_Bench
```

Query counts, hydration counts and distinct-object counts are **deterministic**
— repeated runs give identical values. `elapsed_ms` and `mem_delta_kb` are
single samples and are **noisy**; they are directional support, not a claim.

## Model hydrations (object constructions)

| scenario | n | before | after | change |
|----------|---|--------|-------|--------|
| C cross-API, one popup | any | 2 | **1** | −50% |
| D query + readback | 1 | 2 | **1** | −50% |
| D query + readback | 10 | 20 | **10** | −50% |
| D query + readback | 50 | 100 | **50** | −50% |
| A cold single | any | 1 | 1 | — |
| B warm ×10 | any | 0 | 0 | — |

Exactly one model per popup per request, at every scale.

## Object identity

| scenario | before | after |
|----------|--------|-------|
| C: `pum_get_popup()` vs modern repo vs `pum()->popups` | **2 distinct** | **1 shared** |
| D: distinct objects on readback | 10 of 10 | 10 of 10 (all canonical) |

Before, a mutation applied through one API was invisible through another, and a
reference held across a settings write kept reporting the pre-write value. Both
are fixed.

## SQL queries

| scenario | n | before | after |
|----------|---|--------|-------|
| D query + readback | 0 | 2 | 2 |
| D query + readback | 1 | 2 | 2 |
| D query + readback | 10 | 30 | 30 |
| D query + readback | 50 | 150 | 150 |

**Unchanged, deliberately.** The remaining per-popup queries come from
`WP_Query` and WordPress metadata priming, not from model hydration. This change
removes duplicate *object construction*, not duplicate SQL. Reducing the query
count is a separate piece of work and is not claimed here.

## Memory and time (noisy, single sample)

| n | metric | before | after |
|---|--------|--------|-------|
| 10 | mem_delta_kb | 83.0 | 51.0 |
| 50 | mem_delta_kb | 409.8 | 246.0 |
| 10 | elapsed_ms | 1.94 | 2.42 |
| 50 | elapsed_ms | 7.65 | 7.74 |

Memory drops roughly 40% at 50 popups, consistent with halving live model count.
Elapsed time is flat to marginally worse within sample noise — the freshness
check on the canonical read costs a `get_post()` lookup (object-cache backed, not
SQL). At these magnitudes the timing signal is not meaningful; the durable wins
are hydration count, memory, and identity.

## Staleness

Scenario E (`stale_after_meta_write`) reports `is_fresh: yes` before and after at
every popup count. Additionally, with shared identity the *previously held*
reference now also reports the fresh value, where before it kept the stale one.

## Correctness coverage

`Canonical_Popup_Cache_Test` (11 tests) covers:

- shared identity across all four public fetch APIs
- mutation propagation between APIs
- no stale read after a direct meta write, including via a held reference
- post update and hard delete clear the cached model
- multisite partitioning of canonical models
- legacy getter: object returned for unknown IDs, current-popup fallback
- `query()` reuses models callers already hold
- frontend preload leaves one shared model per popup
- extension repository subclasses keep their own model class
