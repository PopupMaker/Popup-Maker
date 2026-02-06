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

**Confirmed by**: Batch 2 testing (`test_update_many_removes_empty_values`).

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

## 8. `PUM_Analytics::track()`: Missing Event Key Causes PHP 8.x Warning

**Severity**: Medium
**Location**: `classes/Analytics.php` - `track()` method
**Test**: `tests/php/tests/PUM_Analytics_Expanded_Test.php` - `test_track_with_missing_event_key` (skipped)

**Problem**: When `$event_data` is passed without an `'event'` key, the method accesses `$event_data['event']` without checking if it exists. On PHP 8.x this triggers an `Undefined array key` warning which PHPUnit converts to an error.

**Impact**: Any external code calling `track()` with incomplete data gets a PHP warning on 8.x. Silent on 7.x.

**Fix**: Add an early guard:
```php
if ( empty( $event_data['event'] ) ) {
    return false;
}
```

---

## 9. `PUM_Utils_Fields::parse_fields()`: Default Name Parameter Causes ValueError

**Severity**: Medium
**Location**: `includes/utils/fields.php` - `parse_fields()` method
**Test**: `tests/php/tests/PUM_Utils_Fields_Test.php` - multiple tests

**Problem**: Method signature is `parse_fields( $fields, $name = '%' )`. The default `$name = '%'` is passed to `sprintf()` internally, but `'%'` alone is not a valid format specifier, causing a `ValueError: Unknown format specifier` on PHP 8.x.

**Impact**: Any caller using `parse_fields()` without a second argument gets a fatal ValueError on PHP 8.x. Tests must explicitly pass `'%s'` to work around it.

**Fix**: Change the default parameter:
```php
public static function parse_fields( $fields, $name = '%s' )
```

---

## 10. `PopupMaker\Plugin\Core` is `final` — Cannot Be Mocked

**Severity**: Low (testability issue, not runtime bug)
**Location**: `classes/Plugin/Core.php`
**Test**: `tests/php/tests/RestAPI/Test_License_REST_Endpoints.php` (entire class skipped)

**Problem**: `Core` is declared `final`, preventing PHPUnit from creating partial mocks or test doubles. All 17 License REST endpoint tests are skipped because the controller depends on `Core` and it cannot be mocked.

**Impact**: License REST API is untestable without either removing `final` or introducing an interface/wrapper.

**Fix options**:
1. Remove `final` from Core class
2. Extract a `CoreInterface` and type-hint against that
3. Use a dependency injection pattern that allows test substitution

---

## 11. `ObjectSearch`: Missing `paged` Param Causes Negative SQL LIMIT

**Severity**: Medium
**Location**: `classes/RestAPI/ObjectSearch.php` - `search_objects()` method

**Problem**: The offset calculation is `(paged - 1) * per_page`. When `paged` is not provided (defaults to 0 or null), this produces a negative offset like `LIMIT -10, 10`, which is invalid SQL.

**Impact**: REST API calls to `/popup-maker/v2/object-search` without a `paged` parameter produce a database error.

**Fix**: Default `paged` to 1:
```php
$paged = max( 1, (int) $request->get_param( 'paged' ) );
```

---

## Pre-existing Test Infrastructure Issues (Not Bugs)

These are not source code bugs but test environment issues:

- `cta-admin` tests fail: `@popup-maker/i18n` module not found (needs Jest moduleNameMapper or virtual mock)
- `cta-editor` tests fail: `@popup-maker/registry` module not found (needs build or moduleNameMapper)
- Settings store tests need `popupMakerCoreData` global set inside `jest.mock()` factory to run before ES import hoisting
- `PUM_Admin_Settings` tests: All skip when `dist/assets/site.css` not built (48 skips in test environment)
- `Test_Webhook_REST_Endpoints`: All skip — webhooks are a pro-only feature
- `Test_License_REST_Endpoints`: All skip — `Core` is `final` and can't be mocked (see Bug #10)
- wp-env global PHPUnit is v10.5 but WP core test lib uses v9.6 — must use `vendor/bin/phpunit`
