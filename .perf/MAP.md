# Popup model fetch/cache path map (baseline @ develop 21e59a1d)

## Caches that hold hydrated `PUM_Model_Popup` instances

| # | Owner | Storage | Key | Staleness strategy | Eviction API |
|---|-------|---------|-----|--------------------|--------------|
| 1 | `PopupMaker\Services\Repository\Popups` (via `Base\Service\Repository`) | `$items_by_id` | `get_current_blog_id() . ':' . (int) $id` | none — trusted until forgotten | `forget_item()`, `replace_cached_item()` |
| 2 | `PUM_Repository_Popups` (via `PUM_Abstract_Repository_Posts`) | `$cache['objects'][ $post->ID ]` | raw `$post->ID` (NOT site-partitioned) | `hash` field = `md5( blog_id . ':' . md5( json_encode( $post ) ) )`, compared on every read | `forget_item()` |
| 3 | `Controllers\Frontend\Popups` | `$queried_popups` | `popup_cache_key()` (site-partitioned) | re-reads `get_post()` and diffs `get_object_vars()` on every read | `invalidate_queried_popup()` |

Three independent caches store the same logical object. #2 side-steps key
collisions across sites by folding the blog ID into the *hash* rather than the
key, so a cross-site read is treated as stale and re-hydrated instead of
returning the wrong site's model. Correct, but it means the model is
re-constructed on every site switch, and the ID-keyed `forget_item()` is doing
double duty.

## Fetch entry points

- `pum_get_popup( $id )` — legacy public helper. Hand-rolls a three-step
  cascade: `pum()->current_popup` → frontend controller's `$queried_popups` →
  `pum()->popups->get_item()` (cache #2), then *writes back* into cache #3.
  This is the duplicate ownership called out in r3983541633.
- `PopupMaker\plugin()->get( 'popups' )->get_by_id()` — modern path, cache #1.
- `pum()->popups->get_item()` — legacy path, cache #2. Throws
  `InvalidArgumentException` when `has_item()` fails.
- `Controllers\Frontend\Popups::preload_popups()` — queries via cache #1's
  `query()`, then reconciles each model into caches #1 and #3 by hand
  (`get_queried_popup` / `cache_queried_popup` / `replace_cached_item`).

## Cross-cache invalidation wiring (the "convoluted mess")

`Controllers\Frontend\Popups::invalidate_queried_popup()` must poke all three:

```php
unset( $this->queried_popups[ $key ] );           // #3
$this->container->get( 'popups' )->forget_item(); // #1
pum()->popups->forget_item( $post_id );           // #2
```

`invalidate_queried_popup_settings()` repeats the pattern and additionally
reaches *into* the model to null `$popup->settings`.

## Settings provenance (r3983427868)

`PUM_Model_Popup::get_settings()` guards with four conditions:

```php
isset( $this->settings )
&& $this->settings_loaded_from_meta
&& $this->settings === $this->settings_loaded_value
&& $this->settings_meta_cache_hash !== $this->get_settings_meta_cache_hash()
```

backed by three private properties (`$settings_loaded_from_meta`,
`$settings_loaded_value`, `$settings_meta_cache_hash`).
`get_settings_meta_cache_hash()` does a `wp_cache_get( 'post_meta' )` and a
**sha256 over the serialized meta entry on every single call**. `Model\Theme`
carries a parallel-but-different variant of the same idea. This is the DRY
target.

Semantics worth preserving verbatim:

- Only self-loaded-from-meta settings are re-validated; values injected by a
  subclass or by a `get_post_metadata` short-circuit are left alone
  (`from_metadata => false`).
- If a third party mutated `$this->settings` directly, the
  `=== $settings_loaded_value` check declines to clobber it.

## Design target

One canonical request-local fetch/cache boundary below frontend/admin/AJAX, with
caches #2 and #3 delegating to #1 rather than owning storage. Invalidation
collapses to a single call. Identity becomes shared by construction rather than
by hand-reconciliation.
