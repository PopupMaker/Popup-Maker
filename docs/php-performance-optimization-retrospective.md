# Popup Maker PHP Performance Optimization Retrospective

**Status:** Draft developer retrospective

**Date:** August 2026

**Scope:** PHP bootstrap work, database queries, cache behavior, and the performance changes currently merged into `develop`

## TL;DR

The investigation found that much of Popup Maker's avoidable PHP cost came from ordinary work happening too broadly: admin code loading during frontend requests, full models being created where an ID or title was enough, metadata being fetched one record at a time, and failed remote checks being repeated.

The merged work now defers unnecessary frontend bootstrap code, provides lightweight repository query and title-projection APIs, batches several admin lookups, improves subscriber queries and indexes, removes the proprietary remote community-notice feed, and briefly caches failed WordPress.org translation lookups. This document intentionally excludes unmerged performance candidates and no longer repeats complete-stack totals measured against a different candidate set.

## Why this work started

Popup Maker is a mature WordPress plugin with legacy APIs, namespaced services, React admin applications, third-party integrations, and a large compatibility surface. That makes broad rewrites risky. The investigation focused on four practical questions:

1. Which code runs on requests that never use its result?
2. Which consumers need full models, and which need only IDs, posts, or titles?
3. Can repeated work be deferred, narrowed, batched, or cached without changing public behavior?
4. Do focused changes preserve compatibility as well as improve their target workload?

## Measurement approach

### Use a matched WordPress control

Plugin-attributable memory was measured against an otherwise matched WordPress request:

```text
Popup Maker incremental memory =
    matched request with Popup Maker enabled
  - matched WordPress request with Popup Maker unavailable
```

Whole-process retained memory and peak memory were recorded separately. HTTP time to first byte was measured from the client and therefore included the full WordPress bootstrap rather than Popup Maker CPU time alone.

### Measure focused workloads

The broad request matrix covered frontend, wp-admin, Popup Maker screens, editor requests, REST and AJAX endpoints, cron, and WP-CLI. Focused fixtures then exercised subscriber pagination and privacy requests, popup title lists, bulk actions, form discovery, notification conditions, and subscriber sorting.

The original complete-stack results combined merged, pending, and later-held candidates. Those totals are not presented as current `develop` results. A new end-to-end benchmark should be run after the final candidate set is merged and tested together.

### Preserve behavior, not just query counts

The review treated fewer queries as a useful signal rather than the goal. Optimizations were kept only when their target workload improved without bypassing required filters, cache behavior, or third-party compatibility.

Examples from the merged implementations include:

- Popup title projections retain a dedicated filter and use WordPress's posts cache generation to invalidate cached maps.
- Subscriber title batching still resolves IDs through the public `pum_get_popup_id` filter.
- Bulk popup actions skip records already present in the post cache.
- Form integrations continue to query their providers' post types while disabling metadata and taxonomy cache priming they do not consume.
- Feature-announcement checks use bounded metadata batches when an early match can stop the scan.

## What is merged

### Request-aware frontend bootstrap

[#1302](https://github.com/PopupMaker/Popup-Maker/pull/1302) and [#1313](https://github.com/PopupMaker/Popup-Maker/pull/1313) reduced frontend PHP work by deferring admin-only systems and unavailable integrations until a matching request needs them. The change keeps normal WordPress hooks and the Composer autoloader while avoiding unnecessary class loading and construction.

### Shared lightweight popup queries

[#1340](https://github.com/PopupMaker/Popup-Maker/pull/1340) added a cached popup ID-to-title projection for consumers that already have popup IDs. It preserves caller order, duplicate handling, raw title values, cache invalidation, and extension filtering without hydrating complete popup models.

[#1329](https://github.com/PopupMaker/Popup-Maker/pull/1329) added repository-level `query_ids()` and `query_posts()` methods. These give internal consumers a common way to request IDs or WordPress post objects without forcing model hydration. Feature-announcement conditions now use those APIs, including bounded metadata loading for stored legacy Exit Intent triggers.

The following merged consumers use the narrowest practical query shape:

- [#1324](https://github.com/PopupMaker/Popup-Maker/pull/1324) batch-loads popup titles for the Subscribers table through the shared title projection.
- [#1325](https://github.com/PopupMaker/Popup-Maker/pull/1325) primes missing popup posts for bulk enable and disable actions through the repository.
- [#1326](https://github.com/PopupMaker/Popup-Maker/pull/1326) keeps provider-owned form queries local to each integration while disabling unused cache priming.
- [#1327](https://github.com/PopupMaker/Popup-Maker/pull/1327) consolidates Elementor form discovery and deduplicates submission references before loading form titles.

### Subscriber and CTA database paths

[#1318](https://github.com/PopupMaker/Popup-Maker/pull/1318) replaced full-row subscriber pagination counts with native count queries and changed privacy export and erase requests to filter by the indexed email column.

[#1330](https://github.com/PopupMaker/Popup-Maker/pull/1330) added an index for subscriber creation timestamps so recent and creation-date-sorted pages do not require a full filesort on large tables.

[#1323](https://github.com/PopupMaker/Popup-Maker/pull/1323) narrowed Call to Action UUID lookups while retaining the repository and WordPress cache path.

### Remote requests and settings previews

[#1328](https://github.com/PopupMaker/Popup-Maker/pull/1328) removed the proprietary community-notice request from `PUM_Admin_Notices`. That class now registers only local compatibility notices and makes no remote calls to Popup Maker-owned services. The remaining translation-status request goes to the WordPress.org translations API; failed or empty responses are cached for one hour so wp-admin does not retry them repeatedly.

The lazy settings CSS preview introduced before this series and hardened by [#1352](https://github.com/PopupMaker/Popup-Maker/pull/1352) keeps preview generation out of the initial Settings request and handles incomplete or failed endpoint responses more reliably.

## Pending final live-stack validation

The following PRs are ready candidates but remain outside `develop` until the combined Core, Pro, and Pro+ stack is tested live:

- [#1316](https://github.com/PopupMaker/Popup-Maker/pull/1316) — Popup Maker admin-page loading and localization.
- [#1317](https://github.com/PopupMaker/Popup-Maker/pull/1317) — Popup Editor loading and payload work.

[#1316](https://github.com/PopupMaker/Popup-Maker/pull/1316) adapts the shared popup-title helper to the shape used by admin selector controls and prevents the same package variables from being localized twice in one request. [#1317](https://github.com/PopupMaker/Popup-Maker/pull/1317) applies the lightweight popup choices to the block editor and reads plugin-owned editor styles from local files instead of asking WordPress to fetch those files over HTTP during editor rendering.

They are intentionally excluded from the landed-performance summary above. If they pass live-stack validation and merge, their user-facing admin and editor improvements can remain in the release changelog; otherwise those claims must be removed before release.

## Held and excluded

[#1314](https://github.com/PopupMaker/Popup-Maker/pull/1314), [#1315](https://github.com/PopupMaker/Popup-Maker/pull/1315), and [#1319](https://github.com/PopupMaker/Popup-Maker/pull/1319) are held and excluded. Frontend model-reuse, general-admin, and atomic analytics-counter claims from those branches are therefore excluded. [#1355](https://github.com/PopupMaker/Popup-Maker/pull/1355) corrected a test fixture and is not counted as a user-facing performance change.

## Validation status

The merged PRs contain focused regression coverage for their affected query, cache, integration, and endpoint behavior. This retrospective branch changes documentation only. It does not claim that every remaining PR check or review thread is clean; exact-head CI and review status must be rechecked for each pending candidate before merge.

After the final candidate set is chosen, the combined branch should be tested in Popup Maker Core, Pro, and Pro+ and remeasured before publishing aggregate memory, TTFB, SQL-query, or HTTP-request totals.

## Lessons to carry forward

1. Measure plugin-attributable memory against a matched WordPress control.
2. Treat retained and peak memory as separate outcomes.
3. Profile by request context before replacing architecture.
4. Keep Composer and namespaces; prevent eager code from invoking the autoloader unnecessarily.
5. Prefer repository IDs, posts, or cached title projections when a consumer does not need a model.
6. Keep provider-owned queries inside their integrations and request only the caches they consume.
7. Batch work, but bound batches so early exits remain cheap.
8. Optimize elapsed time and semantics, not query count alone.
9. Benchmark the exact merged candidate set before publishing aggregate results.
