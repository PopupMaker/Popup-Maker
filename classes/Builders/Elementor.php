<?php
/**
 * Elementor page builder integration.
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2026, Code Atlantic LLC
 */

namespace PopupMaker\Builders;

use PopupMaker\Base\PageBuilder;
use PopupMaker\Controllers\Previews;

defined( 'ABSPATH' ) || exit;

/**
 * Adds complete Popup Maker document support to Elementor.
 *
 * @since 1.25.0
 */
class Elementor extends PageBuilder {

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
	public function register_hooks() {
		add_post_type_support( 'popup', 'elementor' );
		add_filter( 'elementor/document/urls/wp_preview', [ $this, 'filter_preview_url' ], 10, 2 );
	}

	/**
	 * Point Elementor's preview button at Popup Maker's real-page preview.
	 *
	 * @param mixed $url      WordPress preview URL.
	 * @param mixed $document Elementor document.
	 *
	 * @return mixed
	 */
	public function filter_preview_url( $url, $document = null ) {
		if ( ! is_object( $document ) || ! method_exists( $document, 'get_main_id' ) ) {
			return $url;
		}

		$popup_id = absint( $document->get_main_id() );

		if ( ! $popup_id || 'popup' !== get_post_type( $popup_id ) ) {
			return $url;
		}

		$previews = $this->container->get_controller( 'Previews' );

		$preview_url = $previews instanceof Previews ? $previews->get_preview_url( $popup_id ) : '';

		return $preview_url ?: $url;
	}

	/**
	 * Read the popup ID from Elementor's preview iframe request.
	 *
	 * Authorization is intentionally centralized in the builder controller.
	 *
	 * @return int
	 */
	public function get_requested_popup_id() {
		// Elementor's iframe carries no nonce. The controller applies a per-popup
		// capability check.
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
	 * Honor Elementor's own role and document access rules.
	 *
	 * @param int $popup_id Popup ID.
	 *
	 * @return bool
	 */
	public function can_edit_document( $popup_id ) {
		if ( ! class_exists( '\Elementor\User' ) || ! method_exists( '\Elementor\User', 'is_current_user_can_edit' ) ) {
			return false;
		}

		try {
			return (bool) \Elementor\User::is_current_user_can_edit( absint( $popup_id ) );
		} catch ( \Throwable $error ) {
			unset( $error );

			return false;
		}
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
		if ( ! $this->is_available() ) {
			return null;
		}

		$popup_id = absint( $popup_id );
		$frontend = \Elementor\Plugin::$instance->frontend;

		// Lets Elementor and third-party widgets register state for this
		// secondary document before its markup is generated.
		do_action( 'elementor/post/render', $popup_id );

		if ( $is_editor_canvas && method_exists( $frontend, 'get_builder_content' ) ) {
			$rendered = $frontend->get_builder_content( $popup_id );
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
		if ( ! $this->is_available() ) {
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
