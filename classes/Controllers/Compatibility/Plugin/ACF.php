<?php
/**
 * Advanced Custom Fields (ACF) Compatibility Controller.
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

namespace PopupMaker\Controllers\Compatibility\Plugin;

use PopupMaker\Plugin\Controller;

defined( 'ABSPATH' ) || exit;

/**
 * ACF Compatibility Controller.
 *
 * Since ACF 6.3.4, `[acf]` shortcodes bail on posts that fail
 * `is_post_publicly_viewable()`. Popups fail that check by design, so ACF
 * fields stop rendering inside popup content. This controller loosens that
 * guard only while popup content is being rendered, so ACF shortcodes resolve
 * in popups without exposing non-public field access anywhere else.
 *
 * @since 1.23.0
 */
class ACF extends Controller {

	/**
	 * Check if controller should be enabled.
	 *
	 * @return bool
	 */
	public function controller_enabled() {
		// The `acf` function ships with both ACF and ACF Pro.
		return function_exists( 'acf' )
			&& apply_filters( 'popup_maker/enable_acf_shortcodes_in_popups', true );
	}

	/**
	 * Init controller.
	 *
	 * @return void
	 */
	public function init() {
		// Bracket the popup content filter chain: enable before the shortcode
		// pass (runs at priority 11) and restore immediately after.
		add_filter( 'pum_popup_content', [ $this, 'enable_field_access' ], 1 );
		add_filter( 'pum_popup_content', [ $this, 'restore_field_access' ], 12 );
	}

	/**
	 * Permit ACF shortcode field access for the duration of popup content
	 * rendering. Paired with restore_field_access().
	 *
	 * @param string $content Popup content (passed through unchanged).
	 *
	 * @return string
	 */
	public function enable_field_access( $content ) {
		add_filter( 'acf/shortcode/prevent_access_to_fields_on_non_public_posts', '__return_false' );

		return $content;
	}

	/**
	 * Restore the default ACF shortcode field-access behavior after popup
	 * content rendering. Paired with enable_field_access().
	 *
	 * @param string $content Popup content (passed through unchanged).
	 *
	 * @return string
	 */
	public function restore_field_access( $content ) {
		remove_filter( 'acf/shortcode/prevent_access_to_fields_on_non_public_posts', '__return_false' );

		return $content;
	}
}
