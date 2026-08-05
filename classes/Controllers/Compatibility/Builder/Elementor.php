<?php
/**
 * Elementor Builder Compatibility Controller.
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2026, Code Atlantic LLC
 */

namespace PopupMaker\Controllers\Compatibility\Builder;

use PopupMaker\Plugin\Controller;

defined( 'ABSPATH' ) || exit;

/**
 * Enables Elementor's preview iframe for popup posts.
 *
 * Popup posts are intentionally not publicly queryable. Elementor previews a
 * document through a front-end request, so restore the popup post type only for
 * the matching popup and an authenticated user who can edit it.
 */
class Elementor extends Controller {

	/**
	 * Initialize compatibility hooks.
	 *
	 * @return void
	 */
	public function init() {
		add_filter( 'request', [ $this, 'allow_popup_preview_request' ] );
		add_filter( 'pum_popup_is_loadable', [ $this, 'disable_popups_in_preview' ], 9999 );
	}

	/**
	 * Restore the popup post type for an authorized Elementor preview request.
	 *
	 * WordPress removes non-publicly-queryable post types before applying the
	 * `request` filter. Adding it back here lets Elementor resolve its preview
	 * iframe without exposing popups to ordinary front-end requests.
	 *
	 * @param mixed $query_vars Parsed WordPress query variables.
	 *
	 * @return mixed Filtered query variables.
	 */
	public function allow_popup_preview_request( $query_vars ) {
		if ( ! is_array( $query_vars ) ) {
			return $query_vars;
		}

		$post_id = $this->get_authorized_popup_id_from_request();

		if ( ! $post_id ) {
			return $query_vars;
		}

		$query_vars['p']         = $post_id;
		$query_vars['post_type'] = 'popup';

		return $query_vars;
	}

	/**
	 * Prevent Popup Maker overlays from covering Elementor's editing canvas.
	 *
	 * @param bool $loadable Whether the popup is loadable.
	 *
	 * @return bool Whether the popup is loadable.
	 */
	public function disable_popups_in_preview( $loadable ) {
		return $this->get_authorized_popup_id_from_request() ? false : $loadable;
	}

	/**
	 * Get an Elementor preview popup the current user can edit.
	 *
	 * @return int Popup ID, or 0 when the request is not authorized.
	 */
	private function get_authorized_popup_id_from_request() {
		$post_id = $this->get_popup_id_from_request();

		if (
			! $post_id ||
			'popup' !== get_post_type( $post_id ) ||
			! is_user_logged_in() ||
			! current_user_can( 'edit_post', $post_id )
		) {
			return 0;
		}

		return $post_id;
	}

	/**
	 * Get the popup ID from a matching Elementor preview request.
	 *
	 * @return int Popup ID, or 0 when the request is not a valid match.
	 */
	private function get_popup_id_from_request() {
		// Elementor preview requests do not include a WordPress nonce. Access is
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
