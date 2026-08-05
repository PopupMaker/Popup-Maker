<?php
/**
 * Elementor Builder Compatibility Controller.
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2026, Code Atlantic LLC
 */

namespace PopupMaker\Controllers\Compatibility\Builder;

defined( 'ABSPATH' ) || exit;

/**
 * Enables Elementor's preview iframe for popup posts.
 *
 * Popup posts are intentionally not publicly queryable. Elementor previews a
 * document through a front-end request, so restore the popup post type only for
 * the matching popup and an authenticated user who can edit it.
 */
class Elementor extends Preview {

	/**
	 * Whether rendered Elementor popups have styles waiting to be finalized.
	 *
	 * @var bool
	 */
	private $frontend_styles_pending = false;

	/**
	 * Initialize Elementor-specific preview hooks.
	 *
	 * @return void
	 */
	public function init() {
		parent::init();

		add_action( 'wp_enqueue_scripts', [ $this, 'finalize_frontend_styles' ], 12 );
		add_action( 'wp_footer', [ $this, 'finalize_frontend_styles' ], 0 );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_preview_styles' ], 20 );
		add_filter( 'elementor/document/urls/wp_preview', [ $this, 'filter_wp_preview_url' ], 10, 2 );
		add_filter( 'pum_popup_content', [ $this, 'render_popup_content' ], 1000, 2 );
	}

	/**
	 * Use Elementor's builder preview when WordPress cannot preview popups.
	 *
	 * Popup posts are not publicly queryable, so WordPress returns an empty
	 * preview URL. This authenticated URL renders the saved document through the
	 * isolated popup canvas instead of Elementor's editor-only iframe shell.
	 *
	 * @param mixed $url      WordPress preview URL.
	 * @param mixed $document Elementor document object.
	 *
	 * @return mixed Filtered preview URL.
	 */
	public function filter_wp_preview_url( $url, $document ) {
		if (
			! is_object( $document ) ||
			! method_exists( $document, 'get_main_id' )
		) {
			return $url;
		}

		$post_id = absint( $document->get_main_id() );

		if (
			! $post_id ||
			'popup' !== get_post_type( $post_id ) ||
			! current_user_can( 'edit_post', $post_id )
		) {
			return $url;
		}

		return $this->get_standalone_preview_url( $post_id, 'elementor' );
	}

	/**
	 * Render Elementor popup documents before frontend assets are printed.
	 *
	 * @param mixed $content  Popup post content.
	 * @param mixed $popup_id Popup post ID.
	 *
	 * @return mixed Elementor markup, or the original popup content.
	 */
	public function render_popup_content( $content, $popup_id = 0 ) {
		if (
			! is_string( $content ) ||
			! is_numeric( $popup_id ) ||
			( is_admin() && ! wp_doing_ajax() ) ||
			! did_action( 'elementor/loaded' ) ||
			! class_exists( '\\Elementor\\Plugin' )
		) {
			return $content;
		}

		$popup_id  = absint( $popup_id );
		$elementor = \Elementor\Plugin::$instance;

		if (
			! $popup_id ||
			'popup' !== get_post_type( $popup_id ) ||
			! isset( $elementor->documents, $elementor->frontend ) ||
			! method_exists( $elementor->documents, 'get' )
		) {
			return $content;
		}

		$document = $elementor->documents->get( $popup_id );

		if (
			! is_object( $document ) ||
			! method_exists( $document, 'is_built_with_elementor' ) ||
			! $document->is_built_with_elementor()
		) {
			return $content;
		}

		do_action( 'elementor/post/render', $popup_id );
		$this->frontend_styles_pending = true;

		if (
			$popup_id === $this->get_current_popup_preview_id() &&
			method_exists( $elementor->frontend, 'get_builder_content' )
		) {
			$builder_content = $elementor->frontend->get_builder_content( $popup_id );
		} elseif ( method_exists( $elementor->frontend, 'get_builder_content_for_display' ) ) {
			$builder_content = $elementor->frontend->get_builder_content_for_display( $popup_id );
		} else {
			$builder_content = $content;
		}

		return $builder_content;
	}

	/**
	 * Finalize styles once for all Elementor popups rendered in this phase.
	 *
	 * The priority-12 pass handles normal popup preloading. The footer pass
	 * batches popups discovered while rendering the main page content.
	 *
	 * @return void
	 */
	public function finalize_frontend_styles() {
		if (
			! $this->frontend_styles_pending ||
			! did_action( 'elementor/loaded' ) ||
			! class_exists( '\\Elementor\\Plugin' )
		) {
			return;
		}

		$elementor = \Elementor\Plugin::$instance;

		if ( ! isset( $elementor->frontend ) ) {
			return;
		}

		$styles_finalized = did_action( 'elementor/frontend/after_enqueue_post_styles' );

		if ( method_exists( $elementor->frontend, 'enqueue_styles' ) ) {
			$elementor->frontend->enqueue_styles();
		}

		// Elementor's style enqueue method is one-shot. Re-run its registered
		// finalizers once when this batch was rendered after that method ran.
		if (
			has_action( 'elementor/frontend/after_enqueue_post_styles' ) &&
			did_action( 'elementor/frontend/after_enqueue_post_styles' ) === $styles_finalized
		) {
			do_action( 'elementor/frontend/after_enqueue_post_styles' );
		}

		$this->frontend_styles_pending = false;
	}

	/**
	 * Remove page-canvas spacing from Elementor's empty section control.
	 *
	 * @return void
	 */
	public function enqueue_preview_styles() {
		if ( ! $this->get_current_popup_preview_id() ) {
			return;
		}

		wp_add_inline_style(
			'popup-maker-site',
			'body.pum-builder-preview.elementor-editor-active #elementor-add-new-section { margin: 0 auto; }'
		);
	}

	/**
	 * Get the popup ID from a matching Elementor preview request.
	 *
	 * @return int Popup ID, or 0 when the request is not a valid match.
	 */
	protected function get_popup_id_from_request() {
		$standalone_id = $this->get_standalone_popup_id_from_request( 'elementor' );

		if ( $standalone_id ) {
			return $standalone_id;
		}

		// Elementor iframe requests do not include a WordPress nonce. Access is
		// restricted by the per-popup capability check in the calling method.
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if (
			! isset( $_GET['elementor-preview'], $_GET['p'], $_GET['post_type'] ) ||
			! is_scalar( $_GET['elementor-preview'] ) ||
			! is_scalar( $_GET['p'] ) ||
			! is_scalar( $_GET['post_type'] )
		) {
			return 0;
		}

		$preview_id = absint( wp_unslash( $_GET['elementor-preview'] ) );
		$post_id    = absint( wp_unslash( $_GET['p'] ) );
		$post_type  = sanitize_key( wp_unslash( $_GET['post_type'] ) );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		if ( 'popup' !== $post_type || ! $preview_id || $preview_id !== $post_id ) {
			return 0;
		}

		return $preview_id;
	}
}
