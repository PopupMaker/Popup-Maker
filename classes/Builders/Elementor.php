<?php
/**
 * Elementor page builder integration.
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2026, Code Atlantic LLC
 */

namespace PopupMaker\Builders;

use PopupMaker\Base\PageBuilder;
use PopupMaker\Services\BuilderPreviewUrl;

defined( 'ABSPATH' ) || exit;

/**
 * Adds complete Popup Maker document support to Elementor.
 *
 * @since 1.25.0
 */
class Elementor extends PageBuilder {

	/**
	 * Stable builder key.
	 *
	 * @var string
	 */
	protected $key = 'elementor';

	/**
	 * Whether Elementor's document and rendering APIs are usable.
	 *
	 * @return bool
	 */
	public function is_available() {
		if ( ! did_action( 'elementor/loaded' ) || ! class_exists( '\Elementor\Plugin' ) ) {
			return false;
		}

		$elementor = \Elementor\Plugin::$instance;

		return isset( $elementor->documents, $elementor->frontend ) &&
			method_exists( $elementor->documents, 'get' );
	}

	/**
	 * Register Elementor-specific hooks.
	 *
	 * @return void
	 */
	protected function register_hooks() {
		add_post_type_support( 'popup', 'elementor' );
		add_filter( 'elementor/document/urls/wp_preview', [ $this, 'filter_preview_url' ], 10, 2 );
	}

	/**
	 * Point Elementor's preview button at an authorized standalone preview.
	 *
	 * @param mixed $url      WordPress preview URL.
	 * @param mixed $document Elementor document.
	 *
	 * @return mixed
	 */
	public function filter_preview_url( $url, $document ) {
		if ( ! is_object( $document ) || ! method_exists( $document, 'get_main_id' ) ) {
			return $url;
		}

		$popup_id = absint( $document->get_main_id() );

		if (
			! $popup_id ||
			'popup' !== get_post_type( $popup_id ) ||
			! current_user_can( 'edit_post', $popup_id )
		) {
			return $url;
		}

		return BuilderPreviewUrl::create( $popup_id, $this->key() );
	}

	/**
	 * Read the popup ID from Elementor's preview iframe request.
	 *
	 * Authorization is intentionally centralized in the builder controller.
	 *
	 * @return int
	 */
	public function get_requested_popup_id() {
		// Elementor's iframe carries no nonce; access rests on the per-popup
		// capability check applied by the controller.
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

	/**
	 * Elementor's front-end preview request is its editing canvas.
	 *
	 * @return bool
	 */
	public function is_canvas_request() {
		return true;
	}

	/**
	 * Whether a popup is built with Elementor.
	 *
	 * @param int $popup_id Popup ID.
	 *
	 * @return bool
	 */
	public function owns_document( $popup_id ) {
		$document = $this->get_document( $popup_id );

		return $document &&
			method_exists( $document, 'is_built_with_elementor' ) &&
			$document->is_built_with_elementor();
	}

	/**
	 * Render a popup through Elementor's public frontend API.
	 *
	 * @param int  $popup_id        Popup ID.
	 * @param bool $is_editor_canvas Whether Elementor is rendering its editor canvas.
	 *
	 * @return string|null
	 */
	public function render_document( $popup_id, $is_editor_canvas = false ) {
		if ( ! $this->is_ready() ) {
			return null;
		}

		$popup_id          = absint( $popup_id );
		$frontend          = \Elementor\Plugin::$instance->frontend;
		$is_signed_preview = absint( BuilderPreviewUrl::read_request( $this->key() ) ) === $popup_id;

		// Lets Elementor and third-party widgets register state for this
		// secondary document before its markup is generated.
		do_action( 'elementor/post/render', $popup_id );

		if ( ( $is_editor_canvas || $is_signed_preview ) && method_exists( $frontend, 'get_builder_content' ) ) {
			// Elementor's display helper intentionally returns empty when the
			// requested document is also the current standalone preview document.
			$rendered = $frontend->get_builder_content(
				$popup_id,
				$is_signed_preview && (bool) did_action( 'wp_head' )
			);
		} elseif ( method_exists( $frontend, 'get_builder_content_for_display' ) ) {
			// Elementor enqueues document CSS before the head and prints it inline
			// when Popup Maker discovers the document after the head has passed.
			$rendered = $frontend->get_builder_content_for_display(
				$popup_id,
				(bool) did_action( 'wp_head' )
			);
		} else {
			return null;
		}

		return is_string( $rendered ) ? $rendered : null;
	}

	/**
	 * Get an Elementor document for a popup.
	 *
	 * @param int $popup_id Popup ID.
	 *
	 * @return object|null
	 */
	private function get_document( $popup_id ) {
		if ( ! $this->is_ready() ) {
			return null;
		}

		$popup_id = absint( $popup_id );

		if ( ! $popup_id || 'popup' !== get_post_type( $popup_id ) ) {
			return null;
		}

		$document = \Elementor\Plugin::$instance->documents->get( $popup_id );

		return is_object( $document ) ? $document : null;
	}
}
