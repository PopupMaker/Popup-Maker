# Integrating Page Builders with Popup Maker

This guide describes the minimum architecture used by Popup Maker's bundled
page-builder integrations. It is based on live testing with Elementor, Bricks,
Divi, Beaver Builder, SiteOrigin Page Builder, Brizy, and Visual Composer, with
Gutenberg and TinyMCE as controls.

The central lesson is simple: share the WordPress and Popup Maker lifecycle,
but leave every builder's native APIs in its own small adapter.

## Final architecture

```text
PopupMaker\Controllers\Builders
├── discovers active integrations at builder-safe lifecycle boundaries
├── authorizes native editor requests
├── restores private popup queries for authorized users
├── renders the normal site theme as the editor background
├── suppresses the theme loop's duplicate popup content
├── loads one editable popup through Popup Maker's footer renderer
└── delegates document ownership and rendering to one adapter

PopupMaker\Base\PageBuilder
├── is_available()
├── register_hooks()                 optional
├── get_requested_popup_id()         optional
├── is_canvas_request()              optional
├── owns_document()                  optional
└── render_document()                optional

PopupMaker\Builders\*
└── one adapter per supported builder, containing only its native APIs

@popup-maker/builder-preview
└── shared editor-canvas positioning and close prevention
```

There is intentionally no public registry, collector object, capability
interface tree, or trait hierarchy. Popup Maker ships a small, known set of
integrations and normally only one builder is active. `Builders` conditionally
constructs adapters only when the corresponding plugin or theme is detected,
and each adapter overrides only the methods it needs.

This keeps the runtime contract visible in one abstract class without forcing
unrelated builders into the same rendering or asset mechanism.

## Keep preview and builder responsibilities separate

`PopupMaker\Controllers\Previews` owns the core editor's signed, real-page
preview flow. It can also supply a useful preview URL to a builder such as
Elementor.

`PopupMaker\Controllers\Builders` owns native builder editor requests and
secondary builder documents rendered inside popups. Asset loading outside an
editor is therefore a builder concern, not a preview concern.

The deprecated `PUM_Previews` class remains only as a compatibility facade for
older callers.

## Editor request lifecycle

A popup post is public in WordPress terms but deliberately not publicly
queryable. Several builders assume every editable post has a normal public
single URL, which is the source of most direct-editor failures.

The shared controller resolves that mismatch as follows:

1. Each active adapter recognizes only its builder's native request arguments
   and returns the requested popup ID.
2. The controller verifies that the ID is a `popup`, the visitor is logged in,
   and `current_user_can( 'edit_post', $popup_id )` succeeds.
3. The `request` filter restores the private popup query for that authorized
   request, including draft status when necessary.
4. WordPress renders the active theme's normal single template. Popup Maker
   does not replace it with a hand-built HTML document.
5. The main loop's copy of `the_content` is suppressed on the editable canvas.
6. Popup Maker's normal footer renderer outputs exactly one popup with its real
   theme, overlay, container, size, position, title, and close button.
7. Live triggers are removed and the close button remains visible but inert.
8. The owning adapter supplies native builder markup or the mount node required
   by the builder.

Using the active theme is important. It makes the area under a transparent or
disabled popup overlay match the user's site, preserves the site's real head
and footer hooks, and avoids maintaining a second approximation of WordPress's
document structure.

Some builders have separate shell and canvas requests. Their shell must remain
fully builder-owned; only the editable iframe or front-end canvas enters the
Popup Maker rendering lifecycle. `is_canvas_request()` expresses that one
distinction without creating another contract type.

## Document ownership and rendering

`owns_document( $popup_id )` should use the builder's canonical saved state:
its document API, editor-mode helper, or stable post meta. Do not infer
ownership merely because a builder plugin is active.

There is one exception for a first edit: a builder may not save its ownership
meta until the document is first updated. In that case the adapter may claim an
already-authorized native request for that same popup. Visual Composer uses
this pattern.

`render_document( $popup_id, $is_editor_canvas )` follows three rules:

- Return `null` when WordPress's existing content pipeline is already correct.
- Return a string only when the builder needs native rendering or an editor
  mount hierarchy.
- Use the builder's public frontend API whenever one exists.

For example, Elementor renders through its frontend API. Divi visitors stay in
the normal content pipeline, while its front-end editor receives the minimum
`#et-boc > .et-l > #et-fb-app` mount. Visual Composer visitors also stay in the
normal pipeline, while its canvas receives `#vcv-editor`.

When a builder renderer mutates global or static document state, snapshot and
restore it in `finally`. Bricks needs this because a popup can be discovered
while the host page's Bricks element tree is still rendering.

## Secondary-document assets

A page-builder page in the main loop is the primary document. A builder popup
rendered from the header, footer, a shortcode, or a late trigger is a secondary
document. Many builders load CSS and JavaScript only for the primary queried
post, so correct HTML alone is not sufficient.

Do not build a shared asset collector unless multiple builders genuinely expose
the same mechanism. They do not:

| Builder | Secondary popup strategy |
| --- | --- |
| Elementor | Rendering through Elementor's frontend API registers document state; Elementor's normal footer pass emits the accumulated styles. |
| Bricks | Render the popup element data without replacing the host page's active-template state; generate only the popup CSS delta and restore Bricks' shared statics. |
| Divi | Use Divi's normal content and asset pipeline. |
| Beaver Builder | Let Beaver's bundled Popup Maker integration render and enqueue the layout. |
| SiteOrigin | Let SiteOrigin's bundled Popup Maker content filter render the layout and its secondary CSS. |
| Brizy | Add the popup through Brizy's asset manager, deduplicate document IDs, and emit only late styles/head-code deltas when the head has passed. |
| Visual Composer | Add the popup ID to Visual Composer's `AssetsEnqueue` list and flush its CSS-list event once per collected batch. |

The repeatable policy is smaller than the implementations:

1. Prefer the builder's native document or asset API.
2. Collect each popup ID at most once per request.
3. Let WordPress print scripts in the footer whenever possible.
4. If `wp_head` has passed, print only newly registered styles or the exact
   builder-generated delta.
5. Never reset or emit a builder's global asset bucket wholesale.
6. Never re-run one-time builder bootstrap methods to process another popup.

This scales to many popups without multiplying the builder's complete page
bootstrap. The adapter performs one small native operation per owned document,
deduplicates repeated renders, and finalizes a batch only when its builder
requires it.

## Canvas behavior

The shared `@popup-maker/builder-preview` package runs only on an authorized
builder canvas. It asks Popup Maker's existing JavaScript API to reposition the
popup after opening, on window resize, and when a `ResizeObserver` detects a
container-size change. It also prevents accidental closing while leaving the
themed close control visible for design work.

Do not add builder-specific JavaScript until a real editor demonstrates a gap
that the shared popup events cannot solve. Widget reinitialization in
particular is easy to duplicate: many builders already initialize visible
editor content themselves.

Bricks is the deliberate exception to the standard popup DOM. Its Vue editor
replaces descendants of its own content root, so its adapter adopts Bricks'
surviving canvas as the popup container and mirrors only the Popup Maker theme
and geometry it needs. That behavior stays in `Builders\Bricks`; it is not a
shared canvas capability.

Brizy has one similarly narrow CSS correction for its first empty editor block.
All other integrations use the shared canvas behavior unchanged.

## Builder-specific findings

| Builder | Minimum Popup Maker responsibility |
| --- | --- |
| Elementor | Add post-type support, authorize its iframe request, render through the frontend API, and point Preview at the real-page preview controller. |
| Bricks | Inject runtime post-type support without persisting it, distinguish shell/canvas/preview, safely render secondary element data and CSS, and adapt its DOM-owning canvas. |
| Divi | Register the post type, authorize the front-end builder, preserve the back-end builder, and use the minimum editor mount. |
| Beaver Builder | Recognize its native request and stop Beaver's broad popup redirect from intercepting another authorized builder. Everything else remains native. |
| SiteOrigin | Inject runtime post-type support without persisting it, retain its classic editor only for SiteOrigin documents, and repair the Live Editor preview URL. |
| Brizy | Register the post type, distinguish shell/iframe requests, provide two native mount nodes, render compiled visitor content, and use Brizy's asset manager. |
| Visual Composer | Honor its Role Manager, distinguish shell/iframe requests, provide its native mount, and use its secondary-source asset queue. |

Gutenberg and TinyMCE require no adapter. They prove that an active builder
must not take over an unrelated popup document.

## Adding another builder

### 1. Discover before abstracting

Use a dedicated `wp-env` instance with the current builder release. Record:

- how the builder enables a custom post type;
- whether that setting is global, per role, or per document;
- editor shell, canvas, and standalone preview request shapes;
- canonical document-ownership state;
- public frontend renderer;
- per-document asset API and output timing;
- whether the editor preserves or replaces server-rendered DOM; and
- any native Popup Maker or popup-feature integration that must not be
  duplicated.

Test with the builder on both the host page and the popup. A popup-only test
misses global-state corruption, duplicate IDs, and primary/secondary asset
conflicts.

### 2. Add the smallest adapter

Create `classes/Builders/<Builder>.php` extending `PageBuilder`. Implement only
the methods proven necessary. Builder hooks belong in `register_hooks()`.

Add one conditional detection block to `Builders::default_builders()`. Detection
must be cheap and safe before the builder has completed initialization;
`is_available()` performs the stronger API check when the controller retries at
`plugins_loaded`, `after_setup_theme`, and `init`.

Third-party hook callbacks must validate arguments defensively and remain PHP
7.4 compatible.

### 3. Prove the integration by subtraction

For every custom hook, mount node, style, asset finalizer, or script:

1. remove it;
2. reproduce the exact editor or visitor failure;
3. restore it; and
4. verify the failure disappears without changing an unrelated builder or
   native editor.

If removal changes nothing in the supported matrix, delete the code.

### 4. Add focused tests

At minimum cover:

- false-positive request rejection;
- shell versus canvas recognition;
- controller authorization and private-query restoration;
- saved document ownership and first-edit ownership when needed;
- visitor render fallback (`null`) versus custom output;
- asset deduplication and late-output boundaries;
- host-page state restoration after nested rendering; and
- inactive integration overhead.

Keep builder API doubles in isolated fixtures. The full Popup Maker test suite
validates shared regressions; builder-specific tests should remain focused.

### 5. Run the live matrix

Verify all of the following in a real browser:

1. direct editor opens without a 404 or redirect;
2. builder controls edit actual popup content;
3. popup theme, overlay, title, close button, size, and position match settings;
4. close is visibly present but inert in the editor;
5. Preview opens a real visitor page when the builder exposes that control;
6. a builder-built main page and builder-built popup render together;
7. multiple builder popups do not duplicate document assets;
8. late-discovered popups retain CSS and interactivity;
9. Gutenberg and TinyMCE popups still use their own editors; and
10. another installed builder cannot intercept the request.

## Anti-patterns

- Replacing the active theme with a full custom HTML template.
- Making the `popup` post type publicly queryable to satisfy a builder.
- Persisting a builder's global post-type setting when runtime injection works.
- Treating every active-builder popup as owned by that builder.
- Calling internal one-time bootstrap methods once per popup.
- Copying an entire builder asset bucket into Popup Maker output.
- Reinitializing every frontend widget after each popup open without evidence.
- Adding an interface or trait for a behavior currently used by one adapter.
- Moving builder asset compatibility into the preview controller.

The target is not identical code for every builder. It is one understandable
orchestration path, small adapters around native APIs, and no custom mechanism
where the builder already provides the correct one.
