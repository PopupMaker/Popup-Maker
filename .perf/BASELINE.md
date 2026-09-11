# Baseline metrics @ develop 21e59a1d

Environment: WP PHPUnit integration suite, single site, MySQL 8.0.35 (Local),
PHP CLI. Query counts and hydration counts are **deterministic** — repeated runs
produce identical values. Elapsed time and memory are single samples and are
**noisy**; treat them as supporting data only.

Reproduce:

```bash
export WP_TESTS_DIR="$TMPDIR/wordpress-tests-lib"
PUM_BENCH_POPUPS=<n> vendor/bin/phpunit -c tests/php/phpunit.xml \
  --filter Popup_Model_Cache_Bench
```

## Results

| n | scenario | queries | hydrations | distinct objects | notes |
|---|----------|---------|------------|------------------|-------|
| 0 | D frontend query | 2 | 0 | — | no popups, no hydration |
| 1 | A cold single | 3 | 1 | — | |
| 1 | B warm ×10 | 1 | 0 | 1 | warm path already good |
| 1 | C cross-API | 0 | 2 | **2** | identity split |
| 1 | D query+readback | 2 | **2** | 1 | 2 hydrations for 1 popup |
| 10 | A cold single | 3 | 1 | — | |
| 10 | B warm ×10 | 1 | 0 | 1 | |
| 10 | C cross-API | 0 | 2 | **2** | identity split |
| 10 | D query+readback | **30** | **20** | 10 | 3 queries + 2 hydrations per popup |
| 50 | D query+readback | **150** | **100** | 50 | scales linearly |

Scenario E (`stale_after_meta_write`) reports `is_fresh: yes` at every count —
the current provenance guard **is** working. Any refactor must keep it that way.

## The two defects the numbers prove

1. **Split identity (C).** Requesting one popup through `pum_get_popup()`,
   `plugin()->get('popups')->get_by_id()`, and `pum()->popups->get_item()`
   returns **2 distinct objects**. Caches #1 and #2 each hydrate their own
   instance. A mutation applied to one is invisible to the other.

2. **Double hydration (D).** Every popup is constructed **twice** per frontend
   pass: once by the repository `query()` (cache #1) and once again on readback
   through `pum_get_popup()`, which consults cache #3 then falls through to
   cache #2 — a cache that never saw the query results. At 50 popups that is
   100 model constructions and 150 queries where 50 and ~51 would do.

Warm repeat reads (B) are already efficient, so the win is concentrated in the
query→readback path and in collapsing identity — which is also the correctness
story, not just a speed story.
