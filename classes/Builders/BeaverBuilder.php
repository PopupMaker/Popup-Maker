<?php
/**
 * Beaver Builder provider.
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2026, Code Atlantic LLC
 */

namespace PopupMaker\Builders;

use PopupMaker\Base\PageBuilder;
use PopupMaker\Controllers\Previews;

defined( 'ABSPATH' ) || exit;

/**
 * Registers Beaver Builder without duplicating its native Popup Maker support.
 *
 * Beaver Builder ships `extensions/fl-builder-popup-maker`, which owns the
 * integration: post type registration, front-end editing, popup rendering,
 * per-layout assets, and trigger preloading. Popup Maker only identifies its
 * native editor request and prevents its broad redirect from claiming another
 * builder's popup request.
 *
 * @since 1.25.0
 */
class BeaverBuilder extends PageBuilder {

	/** @var string */
	public $key = 'beaver-builder';

	/** @var string */
	protected $label = 'Beaver Builder';

	/**
	 * Whether Beaver's native Popup Maker integration is active.
	 *
	 * @return bool
	 */
	public function is_available() {
		return defined( 'FL_BUILDER_VERSION' ) &&
			class_exists( '\FLBuilder' ) &&
			class_exists( '\FLBuilderPopupMaker' );
	}

	/**
	 * Keep Beaver's native popup redirect away from other builders' requests.
	 *
	 * Beaver registers the rest of its integration hooks itself. Its generic
	 * non-builder redirect also matches other builders' popup canvases and signed
	 * previews, so remove it before the default-priority callback runs there.
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_action( 'wp', [ $this, 'preserve_builder_request' ], 0 );
	}

	/**
	 * Get the popup ID Beaver Builder is requesting.
	 *
	 * Authorization is intentionally absent; the coordinator performs it.
	 *
	 * @return int
	 */
	public function get_requested_popup_id() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Capability checked by the coordinator.
		if (
			! isset( $_GET['fl_builder'] ) ||
			! isset( $_GET['post_type'], $_GET['p'] ) ||
			! is_scalar( $_GET['post_type'] ) ||
			! is_scalar( $_GET['p'] ) ||
			'popup' !== sanitize_key( wp_unslash( $_GET['post_type'] ) )
		) {
			return 0;
		}

		return absint( wp_unslash( $_GET['p'] ) );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Honor Beaver Builder's own role-access setting.
	 *
	 * @param mixed $popup_id Popup ID.
	 *
	 * @return bool
	 */
	public function can_edit_document( $popup_id ) {
		if (
			! is_numeric( $popup_id ) ||
			! class_exists( '\\FLBuilderUserAccess' ) ||
			! method_exists( '\\FLBuilderUserAccess', 'current_user_can' )
		) {
			return false;
		}

		try {
			return (bool) \FLBuilderUserAccess::current_user_can( 'builder_access' );
		} catch ( \Throwable $error ) {
			return false;
		}
	}

	/**
	 * Whether Beaver is rendering the editable iframe or legacy canvas.
	 *
	 * Modern releases use a shell marked by `fl_builder_ui` and render the page
	 * in `fl_builder_ui_iframe`. Older releases render the canvas directly.
	 *
	 * @return bool
	 */
	public function is_canvas_request() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Capability checked by the coordinator.
		return isset( $_GET['fl_builder_ui_iframe'] ) || ! isset( $_GET['fl_builder_ui'] );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Let an authorized builder request continue to its own canvas.
	 *
	 * @return void
	 */
	public function preserve_builder_request() {
		$builders = $this->container->get_controller( 'Builders' );
		$previews = $this->container->get_controller( 'Previews' );
		$popup_id = $builders instanceof \PopupMaker\Controllers\Builders
			? $builders->get_edit_popup_id()
			: 0;

		if ( ! $popup_id && $previews instanceof Previews ) {
			$preview_id = $previews->get_popup_preview();
			$popup_id   = $preview_id &&
				'popup' === get_post_type( $preview_id ) &&
				current_user_can( 'edit_post', $preview_id )
				? $preview_id
				: 0;
		}

		if ( ! $popup_id ) {
			return;
		}

		if ( ! method_exists( '\FLBuilderPopupMaker', 'redirect_to_admin_edit' ) ) {
			return;
		}

		remove_action( 'wp', 'FLBuilderPopupMaker::redirect_to_admin_edit' );
	}
}
