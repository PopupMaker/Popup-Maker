<?php
/**
 * Elementor builder provider.
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2026, Code Atlantic LLC
 */

namespace PopupMaker\Builders;

use PopupMaker\Interfaces\BuilderProvider;
use PopupMaker\Services\BuilderPreviewUrl;

defined( 'ABSPATH' ) || exit;

/**
 * Elementor support for popup documents.
 *
 * Elementor defaults to supporting posts and pages only, so this provider adds
 * runtime support for popups. It also restores the popup query for Elementor's
 * preview iframe and renders popup documents through Elementor's API.
 *
 * @since 1.25.0
 */
class Elementor implements BuilderProvider {

	/**
	 * Plugin container.
	 *
	 * @var \PopupMaker\Plugin\Core
	 */
	private $container;

	/**
	 * Construct the provider.
	 *
	 * @param \PopupMaker\Plugin\Core $container Plugin container.
	 */
	public function __construct( $container ) {
		$this->container = $container;
	}

	/**
	 * Provider key.
	 *
	 * @return string
	 */
	public function key() {
		return 'elementor';
	}

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
	}

	/**
	 * Point Elementor's preview button at the signed standalone preview.
	 *
	 * @return void
	 */
	public function register_preview_url() {
		add_filter( 'elementor/document/urls/wp_preview', [ $this, 'filter_preview_url' ], 10, 2 );
	}

	/**
	 * Replace an empty WordPress preview URL for popups.
	 *
	 * Popups are not publicly queryable, so WordPress produces no usable
	 * preview URL and Elementor's preview button does nothing.
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

		if ( ! $popup_id || 'popup' !== get_post_type( $popup_id ) ) {
			return $url;
		}

		if ( ! current_user_can( 'edit_post', $popup_id ) ) {
			return $url;
		}

		return BuilderPreviewUrl::create( $popup_id, $this->key() );
	}

	/**
	 * Get the popup ID Elementor is requesting.
	 *
	 * Authorization is intentionally absent; the coordinator performs it.
	 *
	 * @return int
	 */
	public function get_requested_popup_id() {
		// Elementor's preview iframe carries no nonce; access rests on the
		// per-popup capability check the coordinator applies.
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
	 * Whether this request renders the isolated canvas.
	 *
	 * Elementor serves its editor shell from wp-admin and requests the document
	 * itself through the front-end preview iframe, so every front-end request
	 * this provider claims is already a canvas.
	 *
	 * @return bool
	 */
	public function is_canvas_request() {
		return true;
	}

	/**
	 * Whether a popup's content is built with Elementor.
	 *
	 * @param int $popup_id Popup ID.
	 *
	 * @return bool
	 */
	public function is_builder_document( $popup_id ) {
		$document = $this->get_document( $popup_id );

		return $document && method_exists( $document, 'is_built_with_elementor' ) &&
			$document->is_built_with_elementor();
	}

	/**
	 * Render a popup's Elementor document.
	 *
	 * @param int $popup_id Popup ID.
	 *
	 * @return string|null
	 */
	public function render_document( $popup_id ) {
		if ( ! $this->is_available() ) {
			return null;
		}

		$popup_id = absint( $popup_id );
		$frontend = \Elementor\Plugin::$instance->frontend;

		/**
		 * Lets Elementor's atomic widgets, components, and third-party widgets
		 * register state for this secondary document before it renders.
		 */
		do_action( 'elementor/post/render', $popup_id );

		/**
		 * Inside the builder canvas the document must render unfiltered so edits
		 * appear live. Elsewhere the display variant is correct, since it applies
		 * the filters a visitor would see.
		 */
		if (
			$popup_id === $this->get_canvas_popup_id() &&
			method_exists( $frontend, 'get_builder_content' )
		) {
			$rendered = $frontend->get_builder_content( $popup_id );
		} elseif ( method_exists( $frontend, 'get_builder_content_for_display' ) ) {
			$rendered = $frontend->get_builder_content_for_display( $popup_id );
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

	/**
	 * Get the popup ID currently rendering in the isolated canvas.
	 *
	 * @return int
	 */
	private function get_canvas_popup_id() {
		$builders = $this->container->get_controller( 'Builders' );

		return $builders instanceof \PopupMaker\Controllers\Builders
			? $builders->get_canvas_popup_id()
			: 0;
	}
}
