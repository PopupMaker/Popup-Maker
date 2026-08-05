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
	 * Get the popup ID from a matching Elementor preview request.
	 *
	 * @return int Popup ID, or 0 when the request is not a valid match.
	 */
	protected function get_popup_id_from_request() {
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
