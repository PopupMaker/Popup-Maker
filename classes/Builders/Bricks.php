<?php
/**
 * Bricks builder provider.
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2026, Code Atlantic LLC
 */

namespace PopupMaker\Builders;

use PopupMaker\Base\PageBuilder;

defined( 'ABSPATH' ) || exit;

/**
 * Bricks support for popup documents.
 *
 * Bricks ships as a theme, so every integration point is verified at call time
 * and the provider degrades to a no-op if Bricks changes its API.
 *
 * Two Bricks behaviors shape this class:
 *
 * 1. `Database::set_active_templates()` self-gates after its first run
 *    (`database.php:373-377`), and `$active_templates` / `$page_data` are
 *    request-scoped statics driving the main page's own template resolution.
 *    Re-running that bootstrap is impossible and re-initializing it by hand
 *    would corrupt the host page, so documents are rendered from
 *    `Database::get_data()` without touching it.
 * 2. Bricks' footer CSS pass emits only global classes and AJAX dynamic data
 *    (`frontend.php:483-514`). Per-element document CSS is generated during the
 *    `wp_enqueue_scripts:11` pass and never again, so popups discovered after
 *    head output must have their CSS printed inline.
 *
 * @since 1.25.0
 */
class Bricks extends PageBuilder {

	/** @var string */
	public $key = 'bricks';

	/** @var string */
	protected $label = 'Bricks';

	/**
	 * CSS generated for popup documents and awaiting emission.
	 *
	 * Held here rather than left in Bricks' shared popup bucket, which Bricks
	 * also writes to and reads back.
	 *
	 * @var string
	 */
	private $pending_css = '';

	/**
	 * Page-setting scripts discovered after their normal Bricks output hooks.
	 *
	 * @var string
	 */
	private $pending_scripts = '';

	/**
	 * Popup IDs already collected, so CSS is generated once per popup.
	 *
	 * @var array<int,bool>
	 */
	private $emitted = [];

	/**
	 * Whether popup support existed before this provider injected it.
	 *
	 * @var bool|null
	 */
	private $stored_popup_support;

	/**
	 * Whether Bricks is active and exposes every API used here.
	 *
	 * @return bool
	 */
	public function is_available() {
		return defined( 'BRICKS_VERSION' ) &&
			class_exists( '\Bricks\Database' ) &&
			class_exists( '\Bricks\Frontend' ) &&
			class_exists( '\Bricks\Helpers' ) &&
			method_exists( '\Bricks\Database', 'get_data' ) &&
			method_exists( '\Bricks\Frontend', 'render_data' ) &&
			method_exists( '\Bricks\Helpers', 'get_editor_mode' );
	}

	/**
	 * Register Bricks-specific hooks.
	 *
	 * @return void
	 */
	public function register_hooks() {
		$this->register_post_type_support();

		add_action( 'save_post_popup', [ $this, 'remember_saved_document' ], PHP_INT_MAX, 3 );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_canvas_assets' ], 20 );
		add_filter( 'body_class', [ $this, 'filter_canvas_body_classes' ] );

		/**
		 * Bricks' editor owns the canvas DOM: on mount it replaces the wrapper's
		 * contents with its own draggable content area, discarding any markup
		 * Popup Maker rendered inside it. Rather than fight that, the popup
		 * container class is added to Bricks' own content element so the popup's
		 * theme styles apply to the element the editor already manages.
		 */
		add_filter( 'bricks/content/attributes', [ $this, 'filter_content_attributes' ] );
	}

	/**
	 * Remember Bricks after its authenticated AJAX save reaches WordPress.
	 *
	 * @param mixed $post_id Saved post ID.
	 * @param mixed $post    Saved post object.
	 * @param mixed $update  Whether this was an update.
	 *
	 * @return void
	 */
	public function remember_saved_document( $post_id, $post = null, $update = false ) {
		if ( ! wp_doing_ajax() || ! is_numeric( $post_id ) ) {
			return;
		}

		// Bricks verifies the same nonce and post access before saving. Repeat the
		// request identity checks because this callback is attached to a core hook.
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		if (
			! isset( $_POST['action'], $_POST['postId'], $_POST['nonce'] ) ||
			! is_scalar( $_POST['action'] ) ||
			! is_scalar( $_POST['postId'] ) ||
			! is_scalar( $_POST['nonce'] ) ||
			'bricks_save_post' !== sanitize_key( wp_unslash( $_POST['action'] ) ) ||
			absint( wp_unslash( $_POST['postId'] ) ) !== absint( $post_id ) ||
			! check_ajax_referer( 'bricks-nonce-builder', 'nonce', false )
		) {
			return;
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		$this->remember_document_owner( absint( $post_id ) );
	}

	/**
	 * Style Bricks' canvas content element as the popup container.
	 *
	 * @param mixed $attributes Bricks content element attributes.
	 *
	 * @return mixed Filtered attributes.
	 */
	public function filter_content_attributes( $attributes ) {
		if ( ! is_array( $attributes ) || ! $this->get_popup_theme_classes() ) {
			return $attributes;
		}

		$raw_classes = isset( $attributes['class'] ) && is_array( $attributes['class'] )
			? $attributes['class']
			: [ $attributes['class'] ?? '' ];
		$classes     = [];

		foreach ( $raw_classes as $raw_class ) {
			if ( ! is_scalar( $raw_class ) ) {
				continue;
			}

			$tokens  = preg_split( '/\s+/', trim( (string) $raw_class ), -1, PREG_SPLIT_NO_EMPTY );
			$classes = array_merge( $classes, is_array( $tokens ) ? $tokens : [] );
		}

		$attributes['class'] = array_values( array_unique( array_merge( $classes, [ 'pum-container', 'pum-content' ] ) ) );

		return $attributes;
	}

	/**
	 * Add the popup theme classes to Bricks' canvas body.
	 *
	 * Bricks replaces the popup wrapper after mounting, so the theme classes must
	 * remain on an ancestor of its own content element.
	 *
	 * @param mixed $classes Body classes.
	 *
	 * @return mixed Filtered body classes.
	 */
	public function filter_canvas_body_classes( $classes ) {
		$popup = $this->get_canvas_popup();

		if ( ! is_array( $classes ) || ! pum_is_popup( $popup ) ) {
			return $classes;
		}

		$theme_classes = array_map( 'sanitize_html_class', $this->get_popup_theme_classes() );

		if ( $popup->get_setting( 'overlay_disabled' ) ) {
			$theme_classes[] = 'pum-overlay-disabled';
		}

		return array_values(
			array_unique(
				array_merge( $classes, $theme_classes )
			)
		);
	}

	/**
	 * Get the popup when Bricks owns the active isolated canvas.
	 *
	 * @return \PUM_Model_Popup|null
	 */
	private function get_canvas_popup() {
		if ( ! $this->is_available() || ! $this->is_canvas_request() ) {
			return null;
		}

		$builders = $this->container->get_controller( 'Builders' );

		if ( ! $builders instanceof \PopupMaker\Controllers\Builders ) {
			return null;
		}

		$popup_id = $builders->get_canvas_popup_id();

		if ( ! $popup_id || $popup_id !== $this->get_requested_popup_id() ) {
			return null;
		}

		$popup = pum_get_popup( $popup_id );

		return pum_is_popup( $popup ) ? $popup : null;
	}

	/**
	 * Get the popup theme classes for the canvas body.
	 *
	 * @return string[]
	 */
	private function get_popup_theme_classes() {
		$popup = $this->get_canvas_popup();

		if ( ! pum_is_popup( $popup ) ) {
			return [];
		}

		$theme_id = absint( $popup->get_theme_id() );
		$classes  = [];

		if ( $theme_id ) {
			$classes[] = 'pum-theme-' . $theme_id;

			$theme = get_post( $theme_id );

			if ( $theme instanceof \WP_Post && $theme->post_name ) {
				$classes[] = 'pum-theme-' . $theme->post_name;
			}
		}

		return $classes;
	}

	/**
	 * Opt the popup post type into Bricks.
	 *
	 * Bricks only offers "Edit with Bricks" for post types present in its
	 * `postTypes` setting (`Helpers::is_post_type_supported()`,
	 * `helpers.php:317-334`). The setting is filtered on read rather than
	 * written, and the injected value is stripped again on write, so Popup Maker
	 * does not leave a permanent change in another product's stored options.
	 *
	 * The `public => true` gate in `Helpers::get_registered_post_types()`
	 * already passes: popups are registered public but not publicly queryable.
	 *
	 * @return void
	 */
	public function register_post_type_support() {
		$option = defined( 'BRICKS_DB_GLOBAL_SETTINGS' ) ? BRICKS_DB_GLOBAL_SETTINGS : 'bricks_global_settings';

		add_filter( 'option_' . $option, [ $this, 'filter_global_settings' ] );

		/**
		 * Bricks has read-modify-write paths that `get_option()` these settings
		 * and `update_option()` the whole array back — refreshing its Instagram
		 * token, for example (`integrations/instagram/instagram.php:95-101`).
		 * Without this, the injected post type would be written to Bricks' stored
		 * settings and survive Popup Maker's deactivation.
		 */
		add_filter( 'pre_update_option_' . $option, [ $this, 'strip_injected_post_type' ], 10, 2 );

		// Bricks caches the option into `Database::$global_settings` when it
		// loads, which happens before this provider registers. Refresh that copy
		// so the running request sees the added post type too.
		$this->refresh_cached_settings();
	}

	/**
	 * Add the popup post type to Bricks' supported list.
	 *
	 * Filtering the option leaves Bricks' stored settings untouched, so Popup
	 * Maker never mutates another product's saved configuration.
	 *
	 * @param mixed $settings Stored Bricks global settings.
	 *
	 * @return mixed Filtered settings.
	 */
	public function filter_global_settings( $settings ) {
		if ( ! is_array( $settings ) ) {
			$settings = [];
		}

		$post_types = isset( $settings['postTypes'] ) && is_array( $settings['postTypes'] )
			? $settings['postTypes']
			: [];

		if ( null === $this->stored_popup_support ) {
			$this->stored_popup_support = in_array( 'popup', $post_types, true );
		}

		if ( ! in_array( 'popup', $post_types, true ) ) {
			$post_types[]          = 'popup';
			$settings['postTypes'] = $post_types;
		}

		return $settings;
	}

	/**
	 * Keep the injected post type out of Bricks' stored settings.
	 *
	 * Only removes `popup` when it was not already saved, so a site owner who
	 * genuinely ticked the box in Bricks' settings keeps their choice.
	 *
	 * @param mixed $value     Settings about to be written.
	 * @param mixed $old_value Settings currently stored.
	 *
	 * @return mixed Filtered settings.
	 */
	public function strip_injected_post_type( $value, $old_value = null ) {
		if ( ! is_array( $value ) || ! isset( $value['postTypes'] ) || ! is_array( $value['postTypes'] ) ) {
			return $value;
		}

		$stored = is_array( $old_value ) && isset( $old_value['postTypes'] ) && is_array( $old_value['postTypes'] )
			? $old_value['postTypes']
			: [];

		$owner_enabled = null !== $this->stored_popup_support
			? $this->stored_popup_support
			: in_array( 'popup', $stored, true );

		// The owner opted in themselves, so leave the value alone.
		if ( $owner_enabled ) {
			return $value;
		}

		$value['postTypes'] = array_values(
			array_filter(
				$value['postTypes'],
				function ( $post_type ) {
					return 'popup' !== $post_type;
				}
			)
		);

		return $value;
	}

	/**
	 * Add the popup post type to Bricks' already-loaded settings cache.
	 *
	 * @return void
	 */
	private function refresh_cached_settings() {
		if ( ! isset( \Bricks\Database::$global_settings ) || ! is_array( \Bricks\Database::$global_settings ) ) {
			return;
		}

		$post_types = isset( \Bricks\Database::$global_settings['postTypes'] ) &&
			is_array( \Bricks\Database::$global_settings['postTypes'] )
				? \Bricks\Database::$global_settings['postTypes']
				: [];

		if ( in_array( 'popup', $post_types, true ) ) {
			return;
		}

		$post_types[] = 'popup';

		\Bricks\Database::$global_settings['postTypes'] = $post_types;
	}

	/**
	 * Get the popup ID Bricks is requesting.
	 *
	 * Authorization is intentionally absent; the coordinator performs it.
	 *
	 * @return int
	 */
	public function get_requested_popup_id() {
		// Bricks defines these argument names, but the request must still be
		// recognizable before Bricks loads, so the literals are used as a
		// fallback rather than requiring the constants.
		$builder_arg = defined( 'BRICKS_BUILDER_PARAM' ) ? BRICKS_BUILDER_PARAM : 'bricks';

		// Bricks' editor and toolbar preview requests carry no nonce; access rests
		// on the per-popup capability check the coordinator applies.
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$is_editor  = isset( $_GET[ $builder_arg ] ) &&
			is_scalar( $_GET[ $builder_arg ] ) &&
			'run' === sanitize_key( wp_unslash( $_GET[ $builder_arg ] ) );
		$is_preview = isset( $_GET['bricks_preview'] ) && is_scalar( $_GET['bricks_preview'] );

		if ( ! $is_editor && ! $is_preview ) {
			return 0;
		}

		// Bricks addresses the document by permalink, so the queried popup is
		// the target. Query-style permalinks and signed previews also carry `p`.
		$popup_id = isset( $_GET['p'] ) && is_scalar( $_GET['p'] )
			? absint( wp_unslash( $_GET['p'] ) )
			: 0;
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		if ( $popup_id ) {
			return $popup_id;
		}

		$queried = absint( get_queried_object_id() );

		return $queried && 'popup' === get_post_type( $queried ) ? $queried : 0;
	}

	/**
	 * Honor Bricks' own role and post-type access rules.
	 *
	 * @param int $popup_id Popup ID.
	 *
	 * @return bool
	 */
	public function can_edit_document( $popup_id ) {
		if ( ! class_exists( '\Bricks\Capabilities' ) || ! method_exists( '\Bricks\Capabilities', 'current_user_can_use_builder' ) ) {
			return false;
		}

		try {
			return (bool) \Bricks\Capabilities::current_user_can_use_builder( absint( $popup_id ) );
		} catch ( \Throwable $error ) {
			return false;
		}
	}

	/**
	 * Whether this request is the editor canvas rather than the editor shell.
	 *
	 * Bricks marks the canvas iframe with `brickspreview` and its toolbar preview
	 * with `bricks_preview`; the editor shell must keep its own markup. Signed
	 * previews are recognized as canvases by the coordinator before this method.
	 *
	 * @return bool
	 */
	public function is_canvas_request() {
		$iframe_arg = defined( 'BRICKS_BUILDER_IFRAME_PARAM' ) ? BRICKS_BUILDER_IFRAME_PARAM : 'brickspreview';

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Presence checks only.
		return isset( $_GET[ $iframe_arg ] ) || isset( $_GET['bricks_preview'] );
	}

	/**
	 * Whether a popup's content is built with Bricks.
	 *
	 * @param int $popup_id Popup ID.
	 *
	 * @return bool
	 */
	public function owns_document( $popup_id ) {
		if ( ! $this->is_available() ) {
			return false;
		}

		return 'bricks' === \Bricks\Helpers::get_editor_mode( absint( $popup_id ) );
	}

	/**
	 * Render a popup's Bricks document.
	 *
	 * `Frontend::render_data()` returns the markup (verified against Bricks
	 * 1.12.4 and 2.4-beta2) and is the right primitive here.
	 * `render_content()` is deliberately avoided: it echoes and wraps output in
	 * `<main id="brx-content">`, which is wrong inside a popup container and
	 * would duplicate that ID when a Bricks page renders on the same request.
	 *
	 * @param int  $popup_id         Popup ID.
	 * @param bool $is_editor_canvas Whether this is the native editor canvas.
	 *
	 * @return string|null
	 */
	public function render_document( $popup_id, $is_editor_canvas = false ) {
		if ( ! $this->is_available() ) {
			return null;
		}

		$popup_id = absint( $popup_id );

		if ( 'bricks' !== \Bricks\Helpers::get_editor_mode( $popup_id ) ) {
			return null;
		}

		$elements = \Bricks\Database::get_data( $popup_id, 'content' );

		if ( [] === $elements ) {
			if ( ! $is_editor_canvas ) {
				$this->collect_document_assets( $popup_id );
				$this->finalize_document_assets( did_action( 'wp_head' ) && ! doing_action( 'wp_head' ) );
			}

			return '';
		}

		if ( ! is_array( $elements ) ) {
			return null;
		}

		// Bricks' Vue canvas renders the editable tree into `#brx-content`.
		if ( $is_editor_canvas ) {
			return '';
		}

		$popup_post = get_post( $popup_id );

		if ( ! $popup_post instanceof \WP_Post ) {
			return null;
		}

		/**
		 * Bricks elements resolve dynamic data against the current post, so the
		 * popup is made current for the render and the previous post restored
		 * afterwards. `set_active_templates()` is deliberately not called; it
		 * self-gates and owns the host page's template resolution.
		 */
		global $post;

		$previous_post = $post;

		/**
		 * `render_data()` overwrites `Frontend::$elements` and `$area` with the
		 * tree it is rendering and does not restore the previous values. This
		 * render can be nested — preloading a popup discovered from a
		 * `popmake-ID` trigger happens while the host page's own element tree is
		 * still rendering — so the previous state is snapshotted here. Bricks
		 * takes the same precaution around its nested template renders
		 * (`templates.php:263-268`).
		 */
		$previous_elements = \Bricks\Frontend::$elements;
		$previous_area     = \Bricks\Frontend::$area;

		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restored below.
		$post = $popup_post;

		setup_postdata( $post );

		try {
			$rendered = \Bricks\Frontend::render_data( $elements, 'content' );
		} finally {
			// A throwing element or filter must not leave the host page pointed
			// at the popup, or missing the element map it was rendering.
			\Bricks\Frontend::$elements = $previous_elements;
			\Bricks\Frontend::$area     = $previous_area;

			// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restoring prior state.
			$post = $previous_post;

			if ( $post instanceof \WP_Post ) {
				setup_postdata( $post );
			} else {
				wp_reset_postdata();
			}
		}

		$this->collect_document_assets( $popup_id );
		$this->finalize_document_assets( did_action( 'wp_head' ) && ! doing_action( 'wp_head' ) );

		return is_string( $rendered ) ? $rendered : null;
	}

	/**
	 * Generate CSS for one popup document.
	 *
	 * Element CSS is generated here, while the document's render-time state is
	 * still valid, and emitted later by the coordinator's flush. Bricks' own
	 * popup CSS is collected the same way (`assets.php:474-500`).
	 *
	 * `Assets::$inline_css['popup']` is Bricks' *shared* popup bucket: Bricks
	 * appends its own native popup CSS to it (`assets.php:474`) and reads it back
	 * in two places (`assets.php:572`, `popups.php:928`) without ever clearing
	 * it. So this method takes only the delta it generated and restores the
	 * bucket to its previous contents. Popup Maker must never emit or discard CSS
	 * it did not create.
	 *
	 * Popup IDs are also *not* pushed into `Database::$active_templates['popup']`,
	 * even though that is how Bricks collects its own popups: Bricks'
	 * `Popups::render_popups()` iterates that array on `wp_footer` and would emit
	 * `.brx-popup` wrappers around Popup Maker popups.
	 *
	 * @param int $popup_id Popup ID.
	 *
	 * @return void
	 */
	public function collect_document_assets( $popup_id ) {
		if ( ! $this->can_generate_css() ) {
			return;
		}

		$popup_id = absint( $popup_id );

		if ( isset( $this->emitted[ $popup_id ] ) ) {
			return;
		}

		$elements = \Bricks\Database::get_data( $popup_id, 'content' );

		if ( ! is_array( $elements ) ) {
			return;
		}

		$this->emitted[ $popup_id ] = true;

		/**
		 * Bricks generates page-level CSS and scripts only for post IDs present
		 * in this list (`assets.php:857`), so a popup's own Bricks page settings
		 * would otherwise be skipped.
		 */
		if ( isset( \Bricks\Assets::$page_settings_post_ids ) && is_array( \Bricks\Assets::$page_settings_post_ids ) ) {
			if ( ! in_array( $popup_id, \Bricks\Assets::$page_settings_post_ids, true ) ) {
				\Bricks\Assets::$page_settings_post_ids[] = $popup_id;
			}
		}

		$this->collect_late_page_settings( $popup_id );

		if ( [] === $elements ) {
			return;
		}

		$before = isset( \Bricks\Assets::$inline_css['popup'] ) && is_string( \Bricks\Assets::$inline_css['popup'] )
			? \Bricks\Assets::$inline_css['popup']
			: '';

		\Bricks\Assets::generate_css_from_elements( $elements, 'popup' );

		$after = isset( \Bricks\Assets::$inline_css['popup'] ) && is_string( \Bricks\Assets::$inline_css['popup'] )
			? \Bricks\Assets::$inline_css['popup']
			: '';

		// Take only what this call appended, then hand the bucket back untouched.
		if ( '' !== $after && 0 === strpos( $after, $before ) ) {
			$this->pending_css .= substr( $after, strlen( $before ) );

			\Bricks\Assets::$inline_css['popup'] = $before;

			return;
		}

		// Bricks reordered or replaced the bucket rather than appending. Leave it
		// alone and let Bricks emit the result through its own passes.
	}

	/**
	 * Collect page settings whose normal Bricks output hooks already ran.
	 *
	 * @param int $popup_id Popup ID.
	 *
	 * @return void
	 */
	private function collect_late_page_settings( $popup_id ) {
		if ( ! did_action( 'wp_head' ) || doing_action( 'wp_head' ) ) {
			return;
		}

		if ( ! isset( \Bricks\Assets::$page_settings_post_ids ) || ! is_array( \Bricks\Assets::$page_settings_post_ids ) ) {
			return;
		}

		$previous_ids = \Bricks\Assets::$page_settings_post_ids;
		$had_page_css = is_array( \Bricks\Assets::$inline_css ) && array_key_exists( 'page', \Bricks\Assets::$inline_css );
		$previous_css = $had_page_css && is_string( \Bricks\Assets::$inline_css['page'] )
			? \Bricks\Assets::$inline_css['page']
			: '';

		\Bricks\Assets::$page_settings_post_ids = [ $popup_id ];
		\Bricks\Assets::$inline_css['page']     = '';

		try {
			if ( method_exists( '\Bricks\Assets', 'generate_inline_css_page_settings' ) ) {
				$css = \Bricks\Assets::generate_inline_css_page_settings();

				if ( is_string( $css ) ) {
					$this->pending_css .= $css;
				}
			}

			if ( method_exists( '\Bricks\Assets', 'get_page_settings_scripts' ) ) {
				$scripts        = '';
				$header_scripts = \Bricks\Assets::get_page_settings_scripts( 'customScriptsHeader' );

				if ( is_string( $header_scripts ) ) {
					$scripts .= $header_scripts;
				}

				if ( did_action( 'wp_body_open' ) && ! doing_action( 'wp_body_open' ) ) {
					$body_header_scripts = \Bricks\Assets::get_page_settings_scripts( 'customScriptsBodyHeader' );

					if ( is_string( $body_header_scripts ) ) {
						$scripts .= $body_header_scripts;
					}
				}

				$this->pending_scripts .= $scripts;
			}
		} finally {
			\Bricks\Assets::$page_settings_post_ids = $previous_ids;

			if ( $had_page_css ) {
				\Bricks\Assets::$inline_css['page'] = $previous_css;
			} else {
				unset( \Bricks\Assets::$inline_css['page'] );
			}
		}
	}

	/**
	 * Emit CSS for every popup collected since the last flush.
	 *
	 * Before head output the CSS is enqueued so it lands in `<head>`. After
	 * head output enqueueing no longer reaches the browser, so the CSS is
	 * printed inline — the same fallback Bricks uses for its own popups in file
	 * mode (`popups.php:925-930`).
	 *
	 * @param bool $after_head Whether head output has already been sent.
	 *
	 * @return bool
	 */
	public function finalize_document_assets( $after_head ) {
		if ( ! $this->can_generate_css() ) {
			return false;
		}

		$css     = $this->pending_css;
		$scripts = $this->pending_scripts;

		// Clear before emitting so a later flush cannot repeat this batch.
		$this->pending_css     = '';
		$this->pending_scripts = '';

		if ( '' === trim( $css ) && '' === trim( $scripts ) ) {
			return true;
		}

		if ( '' !== trim( $css ) ) {
			if ( method_exists( '\Bricks\Assets', 'minify_css' ) ) {
				$minified = \Bricks\Assets::minify_css( $css );

				if ( is_string( $minified ) ) {
					$css = $minified;
				}
			}

			if ( ! $after_head ) {
				$handle = 'pum-bricks-popup-inline';

				if ( ! wp_style_is( $handle, 'registered' ) ) {
					wp_register_style( $handle, false, [], \Popup_Maker::$VER );
				}

				wp_enqueue_style( $handle );
				wp_add_inline_style( $handle, $css );
			} else {
				printf(
					'<style id="pum-bricks-popup-css-%1$d">%2$s</style>',
					absint( $this->finalize_sequence() ),
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Builder-generated CSS.
					$css
				);
			}
		}

		if ( '' !== trim( $scripts ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Builder-generated page scripts.
			echo $scripts;
		}

		return true;
	}

	/**
	 * Load the Bricks-owned canvas presentation.
	 *
	 * @return void
	 */
	public function enqueue_canvas_assets() {
		$popup = $this->get_canvas_popup();

		if ( ! pum_is_popup( $popup ) ) {
			return;
		}

		if ( ! $this->enqueue_owned_canvas_preview( $popup->ID, [ 'canvas_selector' => '#brx-content' ] ) ) {
			return;
		}

		wp_add_inline_style(
			'popup-maker-builder-preview',
			// Bricks' canvas stretches its content wrapper with flexbox.
			'body.pum-builder-preview #brx-content { flex: initial; }' .
			'body.pum-builder-preview .pum-container .brxe-container { max-width: 100%; }' .
			'body.pum-builder-preview > .pum > .pum-container { display: none !important; }' .
			'body.pum-builder-preview > .pum { pointer-events: none !important; z-index: 0 !important; }' .
			'body.pum-builder-preview > .brx-body { position: relative; z-index: 1; }'
		);
	}

	/**
	 * Whether Bricks' CSS generation APIs are usable.
	 *
	 * @return bool
	 */
	private function can_generate_css() {
		return $this->is_available() &&
			class_exists( '\Bricks\Assets' ) &&
			method_exists( '\Bricks\Assets', 'generate_css_from_elements' ) &&
			isset( \Bricks\Assets::$inline_css );
	}

	/**
	 * Sequence number used to keep printed style IDs unique.
	 *
	 * @return int
	 */
	private function finalize_sequence() {
		static $sequence = 0;

		return ++$sequence;
	}
}
