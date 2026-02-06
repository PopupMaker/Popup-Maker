# Bugs Found by Test Army (2026-02-06)

Bugs discovered during automated test writing. Each is documented with a test that validates the actual (buggy) behavior.

---

## 1. PURGE_RECORD/PURGE_RECORDS: String vs Number Key Comparison

**Severity**: Medium
**Location**: `packages/core-data/src/popups/reducer.ts` (lines 342-365)
**Also affects**: `packages/core-data/src/call-to-actions/reducer.ts` (same pattern)
**Test**: `packages/core-data/src/popups/__tests__/reducer.test.ts` - "BUG: not from byId"

**Problem**: `Object.entries()` returns string keys, but the `ids` array contains numbers. `ids.includes("1")` does NOT match `ids.includes(1)`.

**Impact**: `allIds` is correctly purged (number-to-number comparison), but `byId`, `editedEntities`, `editHistory`, and `editHistoryIndex` entries are NEVER actually removed. This is a silent memory leak - purged entities remain in state indefinitely.

**Fix**: Cast the key to number before comparison:
```ts
const byId = Object.fromEntries(
    Object.entries(state.byId).filter(
        ([_id]) => !ids.includes(Number(_id))
    )
);
```

---

## 2. RECEIVE_ERROR: State Mutation

**Severity**: Medium
**Location**: `packages/core-data/src/popups/reducer.ts` (line 313)
**Also affects**: `packages/core-data/src/call-to-actions/reducer.ts` (same pattern)
**Test**: `packages/core-data/src/popups/__tests__/reducer.test.ts` - "BUG: mutates previous errors state directly"

**Problem**: Line 307 gets a reference to `state.errors`. Line 313 does `prevErrors.global = error` which directly mutates the previous state. In Redux/reducer patterns, state should NEVER be mutated - only new state objects should be returned.

**Impact**: Cross-test pollution, potential UI inconsistencies in React components relying on reference equality for re-renders. Time travel debugging (undo/redo) may show corrupted history.

**Fix**: Don't mutate `prevErrors` directly:
```ts
const prevErrors = state.errors || { global: null, byId: {} };
const newById = { ...prevErrors.byId };
if (id) {
    newById[id] = error;
}
return {
    ...state,
    errors: {
        global: id ? prevErrors.global : error,
        byId: newById,
    },
};
// Remove line 313: prevErrors.global = error;
```

---

## 3. `omit.ts`: Implementation Picks Instead of Omitting

**Severity**: High
**Location**: `packages/utils/src/lib/omit.ts`
**Test**: `packages/utils/src/lib/__tests__/omit.test.ts`

**Problem**: The function is typed as `Omit<T, K>` but the implementation copies the specified keys TO the result instead of excluding them FROM the result. It behaves like `pick()`, not `omit()`.

**Impact**: Any code calling `omit(obj, ['a', 'b'])` expecting `a` and `b` to be removed will instead get ONLY `a` and `b`. If callers adapted to the buggy behavior, fixing this will break them.

**Fix**: Either:
1. Fix the implementation to actually omit keys, OR
2. Rename it to `pick` if that's the intended behavior and update the type signature

---

## 4. `validatePopup`: Empty Title Not Caught

**Severity**: Low
**Location**: `packages/core-data/src/popups/validation.ts` (line 29)
**Test**: `packages/core-data/src/popups/__tests__/validation.test.ts` - "does NOT catch empty string title"

**Problem**: Line 29 checks `popup.title && !popup.title?.length`. Empty string `''` is falsy, so `popup.title` short-circuits to `false` and the validation passes. Empty titles are never caught.

**Fix**: Check for title being explicitly set with length 0:
```ts
if (popup.title !== undefined && !popup.title?.length) {
```

---

## 5. Existing Test Bug: Analytics API Version

**Severity**: Low
**Location**: `tests/php/tests/test-pum-analytics.php` (line 65)
**Not a source bug - test bug**

**Problem**: Existing test asserts REST route is `pum/v2` but the source code builds `pum/v1`.

**Fix**: Update test assertion to match actual API version.

---

## 6. `Options::update_many()`: Unset Then Re-Set Bug

**Severity**: Low
**Location**: `classes/Services/Options.php` (lines 172 + 187)
**Test**: `tests/php/tests/PUM_Services_Options_Test.php` - `test_update_many_removes_empty_values`

**Problem**: `update_many()` at line 172 does `unset($options[$key])` when value is empty. But then line 187 unconditionally does `$options[$key] = $value` — which re-adds the key with the empty value. The unset is dead code.

**Impact**: Passing an empty value in `update_many()` does NOT delete the key as presumably intended. The key persists with an empty string value. This differs from `update()` which correctly delegates to `delete()` for empty values.

**Fix**: Skip the assignment on line 187 when the value was empty:
```php
// Option A: skip empty values after unset
if ( empty( $value ) ) {
    unset( $options[ $key ] );
    continue; // Don't fall through to re-assignment
}
$options[ $key ] = $value;

// Option B: just remove the dead unset and accept the behavior
```

---

## 7. `PUM_Analytics::track()`: Inconsistent Event Key Usage

**Severity**: Low
**Location**: `classes/Analytics.php` (line ~85)
**Test**: `tests/php/tests/PUM_AnalyticsTEST.php` - `test_track`

**Problem**: `track()` expects `$event_data['event']` values of `'open'` or `'conversion'`, but internally `event_keys('open')` returns `['open', 'opened']`. The meta key stored is `popup_open_count` (singular), but model methods like `get_event_count()` may use different key lookups depending on context. This inconsistency made the original test fragile — had to read `get_post_meta()` directly to verify counts reliably.

**Impact**: Not a functional bug per se, but the multiple synonyms for events (`open`/`opened`, `conversion`/`converted`) create confusion and fragile test/integration code.

---

## Pre-existing Test Infrastructure Issues (Not Bugs)

These are not source code bugs but test environment issues:

- `cta-admin` tests fail: `@popup-maker/i18n` module not found (needs Jest moduleNameMapper or virtual mock)
- `cta-editor` tests fail: `@popup-maker/registry` module not found (needs build or moduleNameMapper)
- Settings store tests need `popupMakerCoreData` global set inside `jest.mock()` factory to run before ES import hoisting
