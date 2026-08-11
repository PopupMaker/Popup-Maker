<?php
/**
 * Page builder integration base.
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2026, Code Atlantic LLC
 */

namespace PopupMaker\Base;

defined( 'ABSPATH' ) || exit;

/**
 * Minimal contract for bundled page builder integrations.
 *
 * Builders own their third-party APIs. The Builders controller owns the shared
 * WordPress request and popup rendering lifecycle.
 *
 * @since 1.25.0
 */
abstract class PageBuilder {

	/**
	 * Plugin container.
	 *
	 * @var \PopupMaker\Plugin\Core
	 */
	protected $container;

	/**
	 * Store shared dependencies.
	 *
	 * @param \PopupMaker\Plugin\Core $container Plugin container.
	 */
	public function __construct( $container ) {
		$this->container = $container;
	}

	/**
	 * Whether the third-party APIs used by this integration exist.
	 *
	 * @return bool
	 */
	abstract public function is_available();

	/**
	 * Register builder-specific hooks.
	 *
	 * @return void
	 */
	public function register_hooks() {}

	/**
	 * Read a popup ID from the builder's native canvas request.
	 *
	 * Authorization belongs to the controller.
	 *
	 * @return int
	 */
	public function get_requested_popup_id() {
		return 0;
	}

	/**
	 * Whether the current user satisfies this builder's own access rules.
	 *
	 * The controller already verifies WordPress's per-post capability. Override
	 * this only when the builder applies an additional permission layer.
	 *
	 * @param int $popup_id Popup ID.
	 *
	 * @return bool
	 */
	public function can_edit_document( $popup_id ) {
		return true;
	}

	/**
	 * Remember this builder after its native editor saves a popup.
	 *
	 * @param int $popup_id Popup ID.
	 *
	 * @return void
	 */
	protected function remember_document_owner( $popup_id ) {
		$builders = $this->container->get_controller( 'Builders' );

		if ( $builders instanceof \PopupMaker\Controllers\Builders ) {
			$builders->remember_document_owner( $this, $popup_id );
		}
	}

	/**
	 * Present a builder-owned content area as a Popup Maker canvas.
	 *
	 * The builder keeps ownership of its editable DOM. Popup Maker supplies the
	 * surrounding theme classes, display settings, title, and inert close button.
	 *
	 * @param int                 $popup_id Popup ID.
	 * @param array<string,mixed> $canvas   Builder canvas selectors.
	 *
	 * @return bool Whether the preview was enqueued.
	 */
	protected function enqueue_owned_canvas_preview( $popup_id, $canvas ) {
		$popup_id = absint( $popup_id );

		if (
			! $popup_id ||
			! is_array( $canvas ) ||
			empty( $canvas['canvas_selector'] ) ||
			! is_string( $canvas['canvas_selector'] )
		) {
			return false;
		}

		$popup = pum_get_popup( $popup_id );

		if ( ! pum_is_popup( $popup ) ) {
			return false;
		}

		wp_enqueue_style( 'popup-maker-builder-preview' );
		wp_enqueue_script( 'popup-maker-builder-preview' );

		$theme_css = pum_get_rendered_theme_styles( $popup->get_theme_id() );

		if ( '' !== $theme_css ) {
			wp_add_inline_style( 'popup-maker-builder-preview', $theme_css );
		}

		$style_selectors = isset( $canvas['style_selectors'] ) && is_array( $canvas['style_selectors'] )
			? array_values( array_filter( $canvas['style_selectors'], 'is_string' ) )
			: [];

		$style_selectors[] = '#popup-maker-builder-preview-inline-css';
		$style_selectors   = array_values( array_unique( $style_selectors ) );

		ob_start();
		pum_popup_close_text( $popup_id );
		$close_content = ob_get_clean();

		wp_localize_script(
			'popup-maker-builder-preview',
			'pumBuilderOwnedCanvas',
			[
				'canvas_selector'      => $canvas['canvas_selector'],
				'iframe_selector'      => isset( $canvas['iframe_selector'] ) && is_string( $canvas['iframe_selector'] )
					? $canvas['iframe_selector']
					: '',
				'style_selectors'      => $style_selectors,
				'popup_id'             => $popup_id,
				'overlay_classes'      => implode( ' ', $popup->get_classes( 'overlay' ) ),
				'container_classes'    => implode( ' ', $popup->get_classes( 'container' ) ),
				'content_classes'      => implode( ' ', $popup->get_classes( 'content' ) ),
				'title_text'           => (string) $popup->get_title(),
				'title_classes'        => implode( ' ', $popup->get_classes( 'title' ) ),
				'size'                 => (string) $popup->get_setting( 'size', 'medium' ),
				'location'             => (string) $popup->get_setting( 'location', 'center top' ),
				'custom_width'         => (string) $popup->get_setting( 'custom_width', '640px' ),
				'custom_height_auto'   => (bool) $popup->get_setting( 'custom_height_auto', false ),
				'custom_height'        => (string) $popup->get_setting( 'custom_height', '380px' ),
				'responsive_min_width' => (string) $popup->get_setting( 'responsive_min_width', '0%' ),
				'responsive_max_width' => (string) $popup->get_setting( 'responsive_max_width', '100%' ),
				'position_top'         => (string) $popup->get_setting( 'position_top', '100' ),
				'position_bottom'      => (string) $popup->get_setting( 'position_bottom', '0' ),
				'position_left'        => (string) $popup->get_setting( 'position_left', '0' ),
				'position_right'       => (string) $popup->get_setting( 'position_right', '0' ),
				'position_fixed'       => (bool) $popup->get_setting( 'position_fixed', false ),
				'scrollable'           => (bool) $popup->get_setting( 'scrollable_content', false ),
				'show_close'           => $popup->show_close_button(),
				'close_content'        => is_string( $close_content ) ? $close_content : '',
				'close_classes'        => implode( ' ', $popup->get_classes( 'close' ) ),
				'close_label'          => esc_html__( 'Close', 'popup-maker' ),
			]
		);

		return true;
	}

	/**
	 * Whether the native builder request is its editable canvas.
	 *
	 * Frontend builders may claim a separate shell request and return false.
	 *
	 * @return bool
	 */
	public function is_canvas_request() {
		return true;
	}

	/**
	 * Whether this builder owns a popup document.
	 *
	 * @param int $popup_id Popup ID.
	 *
	 * @return bool
	 */
	public function owns_document( $popup_id ) {
		return false;
	}

	/**
	 * Render a builder document.
	 *
	 * Return null to preserve WordPress's normal content pipeline.
	 *
	 * @param int  $popup_id        Popup ID.
	 * @param bool $is_editor_canvas Whether this is the native editor canvas.
	 *
	 * @return string|null
	 */
	public function render_document( $popup_id, $is_editor_canvas = false ) {
		return null;
	}
}
