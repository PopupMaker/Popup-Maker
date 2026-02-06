# Popup Maker Testing Gap Analysis & Priority Plan

**Date**: 2026-02-06
**Branch**: testing
**Analyzed by**: 3-agent parallel analysis (PHP, JS/TS, E2E)

---

## Executive Summary

**Current state**: ~649 source files, 16 test files. Roughly **2.5% test file coverage**.

| Area | Source Files | Lines of Code | Test Files | Test Coverage |
|------|-------------|---------------|------------|---------------|
| PHP classes/ | 197 | ~15,000+ | 6 | ~3% |
| PHP includes/ | 85 | ~4,500+ | (above) | - |
| TS/TSX packages/ | 288 | ~12,000+ | 7 | ~2.4% |
| Legacy JS assets/ | 79 | ~5,000+ | 2 | ~2.5% |
| E2E specs | - | - | 3 | ~5% of workflows |

**Bottom line**: The codebase has massive testing gaps everywhere. This plan prioritizes by **risk x effort** — what catches the most bugs per test dollar spent.

---

## What IS Currently Tested

### PHP (6 test files)
| Test File | What It Covers | Quality |
|-----------|---------------|---------|
| `test-popup-maker.php` | Plugin instantiation, constants defined | Smoke test only |
| `test-pum-analytics.php` | Track open/conversion events, pum_vars, namespace/route | Decent for analytics |
| `test-pum_utils_array.php` | filter_null, remove_keys_starting_with, remove_keys | Good utility coverage |
| `test-pum_admin_onboarding.php` | Tour pointers, tips, show_tip logic | Basic assertions |
| `test-license-endpoints.php` | REST auth/permissions, activate/deactivate, sanitization, rate limiting, XSS, error conditions | **Thorough** |
| `test-webhook-endpoints.php` | Webhook verify security, install validation, endpoint registration | Good security coverage |

### JS/TS (7 test files)
| Test File | What It Covers | Quality |
|-----------|---------------|---------|
| cta-admin `status.test.tsx` | StatusFilter rendering, filtering, popover | Good component test |
| cta-admin `type.test.tsx` | TypeFilter rendering, filtering | Good component test |
| cta-admin `list-filters.test.ts` | ListFiltersRegistry registration, ordering | Good registry test |
| cta-editor `header-actions.test.ts` | EditorHeaderActionsRegistry | Good registry test |
| cta-editor `header-options.test.ts` | EditorHeaderOptionsRegistry | Good registry test |
| `ajax-handlers.test.js` | REST API, caching, retry, error handling, queuing (785 lines!) | **Comprehensive** |
| `pro-upgrade-flow.test.js` | License validation, popup mgmt, UI state (688 lines!) | **Comprehensive** |

### E2E (3 spec files)
| Spec | What It Covers |
|------|---------------|
| `call-to-actions.spec.ts` | CTA list page, add new CTA, permission checks |
| `pro-upgrade.spec.ts` | Full upgrade workflow, error handling, accessibility, mobile |
| `gutenberg-validator.spec.ts` | Block validation, pattern library, error recovery |

---

## Priority Tiers

### TIER 1: CRITICAL (Security, Data Integrity, Revenue)
*Write these first. Highest risk, many are easy to test.*

#### PHP — Security-Sensitive Code

| # | File | Lines | What Needs Testing | Risk | Testability |
|---|------|-------|-------------------|------|-------------|
| 1 | `Services/Connect.php` | 635 | Remote server connection, token validation, webhook auth | 🔴 Critical | Moderate |
| 2 | `RestAPI/Connect.php` | 584 | Plugin install/upgrade webhooks, auth verification | 🔴 Critical | Moderate |
| 3 | `DB/Subscribers.php` | 262 | SQL queries, subscriber data CRUD, table creation | 🔴 Critical | Easy |
| 4 | `Services/License.php` | 1,135 | License validation, activation/deactivation, status checks | 🔴 Critical | Moderate |
| 5 | `RestAPI/License.php` | 629 | License REST endpoints (partially tested, expand) | 🟡 High | Easy |
| 6 | `Analytics.php` | 354 | Event tracking, data recording (partially tested, expand) | 🟡 High | Easy |

#### PHP — Data Layer

| # | File | Lines | What Needs Testing | Risk | Testability |
|---|------|-------|-------------------|------|-------------|
| 7 | `Models/CallToAction.php` | 335 | CTA model getters/setters, settings, conversions | 🔴 Critical | Easy |
| 8 | `Services/Repository/CallToActions.php` | 130 | CTA CRUD operations | 🔴 Critical | Moderate |
| 9 | `Services/Repository/Popups.php` | 88 | Popup CRUD operations | 🔴 Critical | Moderate |
| 10 | `Repository/Popups.php` | - | Popup queries and retrieval | 🟡 High | Moderate |
| 11 | `Repository/Themes.php` | - | Theme queries and retrieval | 🟡 High | Moderate |

#### JS/TS — Data Stores (0 tests, ~69 source files)

| # | Package/Module | Key Files | What Needs Testing | Risk | Testability |
|---|----------------|-----------|-------------------|------|-------------|
| 12 | core-data/call-to-actions | reducer.ts (612 lines) | State mutations, undo/redo, JSON patch application | 🔴 Critical | **Easy** (pure function) |
| 13 | core-data/call-to-actions | selectors.ts (466 lines) | Entity selectors, editor selectors, resolution status | 🔴 Critical | **Easy** (pure function) |
| 14 | core-data/call-to-actions | validation.ts (42 lines) | CTA validation logic | 🔴 Critical | **Easy** (pure function) |
| 15 | core-data/popups | reducer.ts, selectors.ts | Popup state management (similar to CTAs) | 🔴 Critical | **Easy** (pure function) |
| 16 | core-data/settings | reducer.ts, selectors.ts | Settings state management | 🟡 High | **Easy** |
| 17 | core-data/license | reducer.ts, selectors.ts | License state management | 🟡 High | **Easy** |

**Estimated Tier 1 effort**: ~200-250 tests
**Estimated time**: 2-3 weeks focused work
**Value**: Covers security, data integrity, and core state management

---

### TIER 2: HIGH PRIORITY (Core Business Logic)
*These protect the features users pay for.*

#### PHP — Business Logic

| # | File | Lines | What Needs Testing | Risk | Testability |
|---|------|-------|-------------------|------|-------------|
| 18 | `Conditions.php` | 555 | Condition registration, evaluation, group logic | 🟡 High | Moderate |
| 19 | `ConditionCallbacks.php` | 284 | All condition evaluation callbacks | 🟡 High | Easy |
| 20 | `AssetCache.php` | 1,364 | File generation, cache invalidation, priority loading | 🟡 High | Hard |
| 21 | `Services/FormConversionTracking.php` | 308 | Form submission detection, conversion recording | 🟡 High | Moderate |
| 22 | `Services/LinkClickTracking.php` | 232 | Link click tracking, external link detection | 🟡 High | Easy |
| 23 | `Services/Options.php` | 252 | Option get/set/delete, defaults | 🟢 Medium | **Easy** |
| 24 | `Services/Logging.php` | 503 | Log recording, retrieval, cleanup | 🟢 Medium | Easy |

#### PHP — Utility Functions (Easy Wins)

| # | File | Lines | What Needs Testing | Risk | Testability |
|---|------|-------|-------------------|------|-------------|
| 25 | `includes/namespaced/utils.php` | 189 | Pure utility functions | 🟢 Medium | **Easy** |
| 26 | `includes/namespaced/cacheit.php` | 190 | Caching utility functions | 🟢 Medium | **Easy** |
| 27 | `includes/namespaced/condition-helpers.php` | 305 | Condition helper functions | 🟡 High | **Easy** |
| 28 | `includes/namespaced/core.php` | 155 | Core utility functions | 🟢 Medium | **Easy** |

#### JS/TS — Utilities & Components

| # | Package | What Needs Testing | Risk | Testability |
|---|---------|-------------------|------|-------------|
| 29 | utils (clamp, debug, omit, pick) | Pure utility functions | 🟢 Medium | **Easy** |
| 30 | components/is-url-like.tsx | URL validation logic | 🟡 High | **Easy** |
| 31 | components/use-controlled-state.tsx | Controlled/uncontrolled state hook | 🟡 High | Moderate |
| 32 | registry (createRegistry) | Registration, priority sorting, subscriptions | 🟡 High | **Easy** |
| 33 | core-data/actions (CTA + Popup) | CRUD actions with API mocking | 🟡 High | Moderate |

**Estimated Tier 2 effort**: ~150-180 tests
**Estimated time**: 2-3 weeks
**Value**: Covers condition logic, tracking, utilities, core business functions

---

### TIER 3: IMPORTANT (E2E Coverage for User Workflows)
*These catch integration bugs that unit tests miss.*

#### Critical E2E Scenarios (currently 0 coverage)

| # | Workflow | User Impact | Complexity |
|---|---------|-------------|------------|
| 34 | **Popup creation + publish + frontend display** | Plugin unusable without this | Moderate |
| 35 | **Click trigger → popup opens** | Core feature broken | Easy |
| 36 | **Time delay trigger** | Most common trigger type | Easy |
| 37 | **Cookie management** (show once, session) | Popups show too often/never | Easy |
| 38 | **Condition targeting** (page-based) | Wrong audience targeting | Moderate |
| 39 | **Analytics tracking** (impressions + conversions) | Can't optimize campaigns | Moderate |
| 40 | **Block editor popup trigger** | Modern WordPress integration | Moderate |
| 41 | **Form integration** (at least CF7) | Lost leads | Hard |
| 42 | **Multi-popup on same page** | Popup conflicts | Moderate |

#### Frontend JS Integration Tests

| # | System | What Needs Testing | Complexity |
|---|--------|-------------------|------------|
| 43 | PUM.open() / PUM.close() API | Popup instance management | Moderate |
| 44 | Cookie creation/expiration | Show frequency logic | Easy |
| 45 | PUM.hooks system | Extension compatibility | Moderate |
| 46 | Client-side condition evaluation | Advanced targeting | Hard |

**Estimated Tier 3 effort**: ~40-60 E2E tests, ~20-30 frontend integration tests
**Estimated time**: 2-3 weeks
**Value**: Catches real user-facing regressions

---

### TIER 4: NICE TO HAVE (Lower Risk, Lower Urgency)

| Area | What | Why Lower Priority |
|------|------|--------------------|
| Admin Controllers | Page rendering, menu registration | Mostly WordPress boilerplate |
| Compatibility controllers | Divi, Yoast, backcompat filters | Edge cases, not core |
| Form integrations (all 15+) | Individual form plugin compat | Each is low-frequency |
| Presentational components | React UI components | E2E covers interactions |
| Build tooling | Webpack plugins | Build-time only |
| Legacy functions | `includes/legacy/` | Deprecated, don't invest |
| Extension system | License/updater framework | Shared across plugins |
| Upgrade routines | v1.7, v1.8 migrations | One-time code |
| Admin themes | Theme editor CRUD | Low user impact |
| Mobile-specific triggers | Touch interactions | Niche feature |
| Accessibility | Focus traps, ARIA | Important but separate initiative |

---

## Recommended Implementation Order

### Phase 1: "Foundation" (Week 1-2) — Pure Functions First
**Strategy**: Maximum test coverage with minimum effort. All pure functions.

```
1. core-data/call-to-actions/validation.ts     (~5 tests)   — 30 min
2. core-data/call-to-actions/selectors.ts      (~25 tests)  — 2 hours
3. core-data/call-to-actions/reducer.ts        (~30 tests)  — 3 hours
4. core-data/popups/selectors.ts               (~20 tests)  — 2 hours
5. core-data/popups/reducer.ts                 (~25 tests)  — 3 hours
6. utils package (clamp, omit, pick)           (~15 tests)  — 1 hour
7. components/is-url-like.tsx                  (~10 tests)  — 30 min
8. includes/namespaced/utils.php               (~15 tests)  — 1 hour
9. includes/namespaced/condition-helpers.php    (~20 tests)  — 2 hours
10. PUM_Utils_Array (expand existing)           (~10 tests)  — 30 min
```
**Total**: ~175 tests, ~15 hours
**Result**: Data store logic and pure utilities fully tested

### Phase 2: "Services & Security" (Week 3-4)
**Strategy**: Test security-critical PHP services with mocking.

```
1. DB/Subscribers.php                          (~15 tests)  — 2 hours
2. Models/CallToAction.php                     (~20 tests)  — 2 hours
3. Services/Options.php                        (~10 tests)  — 1 hour
4. Services/License.php                        (~25 tests)  — 4 hours
5. Services/Connect.php                        (~20 tests)  — 4 hours
6. ConditionCallbacks.php                      (~20 tests)  — 2 hours
7. Services/FormConversionTracking.php         (~15 tests)  — 2 hours
8. Services/LinkClickTracking.php              (~10 tests)  — 1 hour
9. core-data/settings (reducer + selectors)    (~15 tests)  — 2 hours
10. core-data/license (reducer + selectors)    (~10 tests)  — 1 hour
```
**Total**: ~160 tests, ~21 hours
**Result**: Security layer and business services covered

### Phase 3: "User Workflows" (Week 5-6)
**Strategy**: E2E tests for the critical happy paths.

```
1. Popup create → publish → verify frontend    (~5 tests)   — 4 hours
2. Click trigger opens popup                   (~3 tests)   — 1 hour
3. Time delay trigger                          (~3 tests)   — 1 hour
4. Cookie-based show frequency                 (~5 tests)   — 2 hours
5. Page condition targeting                    (~5 tests)   — 3 hours
6. Analytics tracking (open + conversion)      (~4 tests)   — 2 hours
7. Block editor trigger insertion              (~3 tests)   — 2 hours
8. Multi-popup same page                       (~3 tests)   — 2 hours
9. registry package (createRegistry)           (~15 tests)  — 2 hours
10. components/use-controlled-state.tsx        (~12 tests)  — 2 hours
```
**Total**: ~58 tests, ~21 hours
**Result**: Critical user workflows have regression protection

---

## Quick Wins (Do These Anytime)

These are easy tests you can add opportunistically:

| Target | Tests | Time | Why |
|--------|-------|------|-----|
| `validation.ts` (CTA) | 5-8 | 30 min | 42 lines, pure function, prevents bad data |
| `utils` package | 10-15 | 1 hour | Pure functions, widely used |
| `is-url-like.tsx` | 8-10 | 30 min | 18 lines, URL validation edge cases |
| `Services/Options.php` | 10 | 1 hour | Option wrapper, used everywhere |
| Expand `test-pum_utils_array.php` | 10 | 30 min | Add edge cases to existing test |

---

## Anti-Priorities (Don't Test These Yet)

| Area | Why Not |
|------|---------|
| `includes/legacy/` | Deprecated code, will be removed |
| Individual form integrations (15+) | Too many, low individual frequency |
| Admin page rendering | WordPress handles most of this |
| Webpack plugins | Build tooling, not runtime |
| `example-package` | Template package, not production |
| Upgrade routines | One-time migrations, already ran |
| CSS/styling | Visual regression testing is a separate concern |

---

## Infrastructure Needs

Before scaling test writing, verify:

1. **PHPUnit setup** works (`composer run tests`) — bootstrap exists ✅
2. **Jest setup** works (`npm run test:unit`) — config exists ✅
3. **Playwright setup** works (`npm run test:e2e`) — config exists ✅
4. **CI integration** — ensure tests run on PR (check GitHub Actions)
5. **Mock infrastructure** — WP_UnitTestCase for PHP, jest mocks for JS
6. **Code coverage reporting** — add `--coverage` to CI for tracking progress

---

## Success Metrics

| Milestone | Tests | Coverage Target | Timeline |
|-----------|-------|----------------|----------|
| Phase 1 complete | ~175 | Data stores: 80%+ | Week 2 |
| Phase 2 complete | ~335 | Services: 60%+ | Week 4 |
| Phase 3 complete | ~393 | Critical paths: E2E | Week 6 |
| Ongoing | ~500+ | Overall: 40%+ | Quarter |
