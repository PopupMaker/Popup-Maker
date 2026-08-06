# Page Builder Integration Guide

## Purpose

This guide defines how Popup Maker should integrate page builders with popup
editing, previewing, frontend rendering, asset loading, and widget
initialization. It uses the Elementor integration as the reference
implementation, but separates reusable Popup Maker lifecycle concerns from
builder-specific APIs.

The goal is to make the next builder integration small and predictable:

1. Identify the popup represented by the builder request.
2. Let Popup Maker authorize and restore that otherwise non-public query.
3. Render the popup in Popup Maker's isolated builder canvas.
4. Ask the builder to render its document during Popup Maker's normal preload.
5. Batch the builder's asset finalization after all popup documents are known.
6. Refresh interactive widgets when a hidden popup becomes visible.

## Reference implementation

The current implementation is split across these files:

| Responsibility | Location | Ownership |
| --- | --- | --- |
| Preview authorization, isolated query, popup preload, and template selection | `classes/Controllers/Previews.php` | Shared |
| Builder request registration and preview URL helpers | `classes/Controllers/Compatibility/Builder/Concerns/BuilderPreview.php` | Shared concern |
| Builder asset batch boundaries | `classes/Controllers/Compatibility/Builder/Concerns/AssetBatching.php` | Shared concern |
| Deprecated static preview API | `classes/Previews.php` | Backward compatibility |
| Bare popup preview document and Popup Maker chrome | `templates/single-popup.php` | Shared |
| Builder detection, document rendering, standalone preview URL, style finalization | `classes/Controllers/Compatibility/Builder/Elementor.php` | Elementor |
| Widget refresh after popup visibility changes | `assets/js/src/integration/elementor.js` | Elementor |
| Controller registration | `classes/Controllers/Compatibility.php` | Shared registry |
| Asset batch coordinator coverage | `tests/php/tests/Page_Builder_Preview_Test.php` | Shared |
| Request and authorization coverage | `tests/php/tests/Elementor_Compatibility_Test.php` | Elementor reference tests |

## Architecture rule: abstract the lifecycle, not third-party internals

Popup Maker should own behavior that is stable across builders. An adapter
should own every call into a builder's API.

### Shared Popup Maker responsibilities

- Authenticate preview routes.
- Require `edit_post` capability for the exact popup.
- Restore the non-public `popup` post type only for the authorized request.
- Preload draft or published popup content at the builder-safe time.
- Select the isolated popup template.
- Render only the target popup in a preview request.
- Preserve Popup Maker theme, container, title, close button, and data
  attributes.
- Keep the preview close button visible but inert.
- Reposition the preview after size changes.
- Suppress the ordinary footer popup loop in an isolated preview.
- Authorize the popup ID supplied by a builder through
  `popup_maker/builder_preview_id`.
- Provide reusable nonce-backed standalone preview URL helpers.
- Track pending builder assets and flush one batch after head-phase preload or
  one late batch before footer rendering.

### Builder adapter responsibilities

- Recognize the builder's native iframe or preview request.
- Determine whether a popup was built with that builder.
- Render the builder document instead of raw `post_content`.
- Register each rendered document with the builder's asset system.
- Finalize builder styles and other assets through guarded builder APIs.
- Supply a standalone preview URL when the builder's editor-only iframe cannot
  be opened on its own.
- Refresh legacy and modern widget runtimes after a popup becomes visible.
- Add narrowly scoped editor-canvas CSS fixes when the builder assumes a page
  layout rather than a popup container.

### What must remain out of shared code

- Builder globals and singleton access.
- Builder hook names.
- Builder document models.
- Builder-generated CSS file names.
- Builder widget initialization APIs.
- Version-specific workarounds.

Do not create a shared abstraction merely because two builders both have a
method named `render()`. Extract a shared lifecycle only when the ordering,
security, and state transition are the same.

## Rendering phases

Page-builder popup support spans several WordPress phases. Treat them as one
pipeline rather than independent compatibility fixes.

### Phase 1: builder editor iframe

Some builders load the edited document through a frontend iframe. Popup posts
are intentionally not publicly queryable, so WordPress normally strips the
popup post type and produces a 404.

The builder adapter supplies its request through the shared builder adapter
base. The `Previews` controller then:

1. Validates the target popup.
2. Requires an authenticated user with `edit_post` capability.
3. Restores `p` and `post_type=popup` through the `request` filter.
4. Limits Popup Maker loading to that popup.
5. Selects `templates/single-popup.php`.

Builder-native iframe requests may not contain a WordPress nonce. They are only
acceptable when the builder already requires authentication and the preview
controller still checks the exact popup capability. A Popup Maker-owned route
must always use a nonce-backed URL.

### Phase 2: standalone editor preview

A builder's editor iframe URL is not necessarily a usable standalone preview.
For example, Elementor's iframe intentionally returns an empty document wrapper
and expects the parent editor to inject live data.

When the editor's Preview button needs a normal browser page:

1. Filter the builder's WordPress preview URL.
2. Return `get_standalone_preview_url( $post_id, $builder_key )`.
3. Recognize it with
   `get_standalone_popup_id_from_request( $builder_key )`.
4. Render the saved builder document through the isolated popup canvas.

Do not point a standalone Preview button at an editor-only iframe URL unless it
has been tested outside the parent editor.

### Phase 3: normal frontend preload

Popup Maker intentionally renders popup content before the main loop and caches
the result:

```text
wp_enqueue_scripts:10   Page builders initialize CSS isolation and registries.
wp_enqueue_scripts:11   Popup Maker queries and renders configured popups.
wp_enqueue_scripts:12   Builder adapters finalize the collected popup styles.
wp_enqueue_scripts:20   Builder/default style callbacks may run; one-shot calls no-op.
main loop               The page's builder document renders normally.
wp_footer:0             Finalize one batch of popups discovered in main-loop content.
wp_footer:10            Popup Maker prints cached popup markup.
wp_footer               Builder runtime enqueues/prints its normal scripts.
```

Priority 11 is a compatibility boundary, not an arbitrary number. Processing
builder content before priority 11 can run before builder CSS isolation exists
and cause popup styles to leak into the page.

The footer does not normally execute the builder document again. It prints the
content cached by `Frontend\Popups::preload_popup()`.

### Phase 4: popup visibility

Many builder widgets initialize while Popup Maker markup is hidden. Layout- or
visibility-sensitive widgets may need a refresh after `pumAfterOpen`.

The adapter should support both generations of a builder runtime when they
coexist. The Elementor adapter, for example, refreshes its atomic Alpine tree
and invokes the legacy element-ready trigger when those APIs exist.

Always:

- Scope refreshes to the opened popup's builder root.
- Gate every third-party global and method.
- Use the builder's lifecycle API instead of widget-specific click handlers.
- Avoid reinitializing the whole document.
- Also initialize the isolated builder canvas, which is already visible and
  does not open through Popup Maker's normal lifecycle.

## Asset finalization and batching

Rendering each builder document is unavoidable. Re-running a builder's global
asset handler for every popup is avoidable.

### Required batching behavior

Each builder adapter should:

1. Render every configured builder popup during Popup Maker's priority-11
   preload.
2. Register each document with the builder's asset collector.
3. Call `mark_builder_assets_pending()` after the builder registers a document.
4. Run the builder's guarded style finalizer once at priority 12.
5. Clear the pending marker.
6. Use one `wp_footer:0` pass for popups discovered while rendering main-loop
   content.

This changes cumulative work from repeated passes over an expanding document
set toward one pass over the complete set.

For the Elementor reference case, an instrumented request containing one
Elementor main page and six Elementor popups produced:

- Seven builder documents in the DOM.
- Six popup-specific generated CSS files.
- One Elementor style-finalizer call.
- One Elementor script lifecycle call.

The remaining per-popup cost is the builder document render and its own
generated CSS. Popup Maker should not try to combine or rewrite builder-owned
CSS.

### One-shot handlers

Calling a builder's asset handler manually is appropriate when that is the
builder's supported lifecycle boundary. The call must be:

- Guarded by class, object, method, and hook existence as applicable.
- Batched once per rendering phase.
- Idempotent or protected by the builder's own one-shot behavior.
- Repeated only when new documents were registered after the original
  finalization pass.

Elementor's `enqueue_styles()` is one-shot. The adapter records whether
`elementor/frontend/after_enqueue_post_styles` advanced during the call. If it
did not, and a finalizer is registered, it fires that finalizer hook once for
the newly collected batch.

Do not manually invoke Elementor's script enqueue method. Rendering builder
content marks Elementor as present, and Elementor's normal footer lifecycle
enqueues its scripts once.

## Shared preview template contract

`templates/single-popup.php` is a minimal WordPress document. It intentionally
contains only:

- A valid HTML document.
- `wp_head()`, `wp_body_open()`, and `wp_footer()`.
- The selected Popup Maker popup theme and container.
- Optional popup title and an accessible dialog name.
- The builder-rendered popup content.
- The configured close button, visually present but disabled.
- Preview-only positioning and resize behavior.

It intentionally omits the active theme header, footer, navigation, page
content, and unrelated popups. This prevents theme layout rules from becoming
part of the editor canvas while retaining the same Popup Maker chrome used on
the frontend.

The template currently preserves the legacy Bricks fallback until Bricks is
migrated to the shared `Previews` controller lifecycle.

## Builder adapter template

The smallest PHP adapter extends Popup Maker's normal `Controller` and composes
only the concerns it needs. Add `BuilderPreview` for editor request handling,
and add `AssetBatching` only when the builder needs explicit asset finalization.

```php
namespace PopupMaker\Controllers\Compatibility\Builder;

use PopupMaker\Controllers\Compatibility\Builder\Concerns\AssetBatching;
use PopupMaker\Controllers\Compatibility\Builder\Concerns\BuilderPreview;
use PopupMaker\Plugin\Controller;

class ExampleBuilder extends Controller {

	use AssetBatching;
	use BuilderPreview;

	/**
	 * Initialize builder-specific hooks.
	 *
	 * @return void
	 */
	public function init() {
		$this->register_builder_preview();
		$this->register_builder_asset_batching();

		add_filter( 'pum_popup_content', [ $this, 'render_popup_content' ], 1000, 2 );
	}

	/**
	 * Identify a native builder preview request.
	 *
	 * @return int Popup ID, or 0 when the request does not match.
	 */
	protected function get_popup_id_from_request() {
		// Validate scalar request values and return only the matching popup ID.
		return 0;
	}

	/**
	 * Render a popup document through the builder.
	 *
	 * Third-party hook callbacks must remain defensively untyped.
	 *
	 * @param mixed $content  Original popup content.
	 * @param mixed $popup_id Popup ID.
	 *
	 * @return mixed Builder markup or original content.
	 */
	public function render_popup_content( $content, $popup_id = 0 ) {
		// Validate the builder, popup ID, document, and supported render method.
		// Register the document, then call mark_builder_assets_pending().
		return $content;
	}

	/**
	 * Finalize one collected builder-asset batch.
	 *
	 * @return bool Whether the batch was finalized.
	 */
	protected function finalize_builder_assets() {
		// Gate the builder's asset API and invoke it once for the batch.
		return true;
	}
}
```

Register the adapter in `classes/Controllers/Compatibility.php`. Keep the
adapter enabled and cheap when the builder is absent, or implement a reliable
`controller_enabled()` check if loading it has measurable cost.

## JavaScript adapter template

Place builder runtime integration with the other frontend integrations. Do not
add a new standalone bundle unless the dependency or payload justifies one.

```javascript
{
	const $ = window.jQuery;

	const refreshWidgets = ( popup ) => {
		const builderRoot = popup.querySelector( '.example-builder' );

		if ( ! builderRoot || ! window.exampleBuilder ) {
			return;
		}

		if ( typeof window.exampleBuilder.refresh === 'function' ) {
			window.exampleBuilder.refresh( builderRoot );
		}
	};

	$( document ).on( 'pumAfterOpen', '.pum', function () {
		refreshWidgets( this );
	} );

	$( function () {
		$( '.pum-builder-preview-popup' ).each( function () {
			refreshWidgets( this );
		} );
	} );
}
```

## Security requirements

Every preview integration must preserve the non-public popup boundary.

- Never make `popup` publicly queryable to satisfy a builder.
- Never restore `post_type=popup` for a generic `p` request.
- Match all builder-specific IDs included in the request.
- Require an authenticated user and `edit_post` for the exact popup.
- Use a builder-specific nonce for Popup Maker-owned standalone routes.
- Treat native builder iframe routes without nonces as exceptions and retain
  the exact capability check.
- Reject arrays and objects in query parameters before sanitizing them.
- Do not load other popups in an isolated preview.
- Preserve draft support only inside the authorized preview path.

Required negative tests include mismatched IDs, logged-out requests, users
without popup capability, invalid standalone nonces, and unrelated popup IDs.

## Main-page builder coexistence

Always test a builder popup on a page built with the same builder. This catches
document-manager corruption, duplicate asset finalization, and CSS leakage that
a classic WordPress page cannot expose.

The Elementor reference test used two distinct documents:

- Main-loop Elementor page with its own generated `post-{id}.css`.
- Elementor popup with its own generated popup CSS.

Both builder roots and CSS files must coexist. Rendering the popup must not
replace the builder's current main document, and opening it must not remove or
restyle the main page.

Also test a classic page with a builder popup. A builder may only schedule its
base assets when the main queried object uses that builder, so the popup adapter
must cover this case without requiring a builder page.

## Test matrix

### Request and authorization

- Native builder iframe with an authorized administrator.
- Draft popup in the builder iframe.
- Mismatched builder and WordPress post IDs.
- Logged-out iframe request.
- Logged-in user without `edit_post` capability.
- Valid standalone preview nonce.
- Invalid or different-popup standalone nonce.

### Editor canvas

- Popup theme, size, position, title, and close button are visible.
- Theme header, footer, and navigation are absent.
- Close attempts do not remove the editing canvas.
- Container repositions after content or setting changes.
- Empty builder sections do not inherit page-canvas spacing.
- The editor's Preview button opens a usable standalone page.

### Frontend rendering

- Classic main page plus one builder popup.
- Same-builder main page plus one builder popup.
- Same-builder main page plus at least six builder popups.
- Multiple popup documents receive their individual generated CSS.
- Global style and script finalizers run once per batch.
- A popup discovered in main-loop content receives the footer asset pass.
- Non-builder popups retain their original content path.
- Tabs, accordions, forms, sliders, and other interactive widgets initialize
  after opening.
- Reopening a popup does not duplicate handlers.

### Quality and compatibility

- PHP 7.4 syntax.
- Defensive, untyped third-party hook callbacks.
- PHPCS and PHPStan.
- Focused PHPUnit request tests.
- Frontend asset build and ESLint when JavaScript changes.
- Browser verification with generated CSS and script URLs recorded.

## Adding another builder

1. Capture the builder's editor iframe request and standalone Preview behavior.
2. Confirm whether the builder supports non-public post types directly.
3. Add a small adapter extending Popup Maker's normal `Controller`.
4. Compose `BuilderPreview` when the builder needs custom editor routing, then
   implement strict request matching in `get_popup_id_from_request()`.
5. Reuse the shared standalone URL helpers when needed.
6. Determine whether the builder already filters popup content.
7. If not, render only documents positively identified as builder documents.
8. Identify how document rendering registers generated CSS and widget assets.
9. Compose `AssetBatching` when needed and finalize after priority-11 popup
   preload.
10. Add a footer batch only for content discovered after the head phase.
11. Use the builder's normal script lifecycle whenever possible.
12. Add a scoped widget refresh on `pumAfterOpen` when required.
13. Add only builder-specific canvas CSS to the adapter.
14. Register the controller and add the full authorization test set.
15. Test classic-page, same-builder-page, and six-popup scenarios.

## Shared utilities and future extraction

The `Previews` controller and shared template cover routing, security, query
restoration, isolated rendering, and popup chrome. Optional builder concerns
then add only the primitives an adapter needs. The `AssetBatching` contract is:

```text
mark_pending()       Called whenever a builder document registers assets.
flush_head_batch()   Runs after priority-11 popup preload.
flush_footer_batch() Runs before normal footer rendering for late discoveries.
finalize()           Supplied by the builder adapter and invoked once per batch.
```

Builder asset APIs remain in their adapter because their hooks and one-shot
behavior are builder-specific. Do not move a builder hook name or singleton
into the shared coordinator. It knows only whether work is pending and when a
batch boundary has been reached.

Likewise, extract a shared JavaScript visibility coordinator only after another
builder needs the same open/canvas callbacks. The builder adapter should still
supply the scoped refresh function.

## Common failure modes

| Symptom | Likely cause |
| --- | --- |
| Builder editor returns 404 | Popup query was not restored for the exact authorized request. |
| Editor shows the WordPress popup settings UI | The request opened the normal post editor rather than the builder iframe. |
| Preview button opens an empty document | An editor-only iframe URL was reused as a standalone preview. |
| Popup contains raw headings or shortcodes | Popup content bypassed the builder document renderer. |
| Popup is unstyled | Builder document CSS was not registered or finalized before output. |
| Main page styles change after enabling a popup | Popup content rendered before builder CSS isolation initialized. |
| Six popups cause repeated asset work | The builder finalizer is called inside the per-popup render method instead of once per batch. |
| Widgets look correct but do not respond | Builder scripts or visibility-time widget initialization are missing. |
| Preview closes and disappears | Builder canvas close behavior was not made inert. |
| Popup position ignores editor changes | Popup Maker repositioning was not triggered after resize or content mutation. |

## Elementor-specific notes

- `elementor-preview` is the native editor iframe route.
- The native iframe is an editor shell; use the shared standalone route for the
  editor's Preview button.
- `get_builder_content_for_display()` is preferred outside the current preview
  document.
- Elementor rejects rendering the current document through that display helper,
  so the isolated preview uses its internal current-document render method.
- `elementor/post/render` registers the popup document with Elementor's asset
  collectors.
- Elementor atomic styles finalize through
  `elementor/frontend/after_enqueue_post_styles`.
- Elementor enqueues scripts through its own footer lifecycle after rendered
  content marks Elementor as present.
- Atomic widgets use `elementorV2.alpinejs.refreshTree()`.
- Legacy widgets use `elementorFrontend.elementsHandler.runReadyTrigger()`.
- A widget defect that also reproduces on a normal Elementor page is upstream;
  do not hide it behind a Popup Maker-wide widget workaround.

## Related documentation

- `docs/frontend-rendering-analysis.md` explains why page-builder popup content
  must not be processed before the builder-safe preload window.
- `classes/Controllers/Frontend/Popups.php` contains the authoritative timing
  history and priority-11 compatibility notes.
