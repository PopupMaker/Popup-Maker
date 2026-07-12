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
	 * The popup ID currently being rendered, used to scope ACF field access.
	 *
	 * @var int
	 */
	private $current_popup_id = 0;

	/**
	 * Init controller.
	 *
	 * @return void
	 */
	public function init() {
		// Bracket the popup content filter chain: enable before the shortcode
		// pass (runs at priority 11) and restore immediately after.
		add_filter( 'pum_popup_content', [ $this, 'enable_field_access' ], 1, 2 );
		add_filter( 'pum_popup_content', [ $this, 'restore_field_access' ], 12 );
	}

	/**
	 * Permit ACF shortcode field access for the duration of popup content
	 * rendering. Paired with restore_field_access().
	 *
	 * @param string $content  Popup content (passed through unchanged).
	 * @param int    $popup_id ID of the popup being rendered.
	 *
	 * @return string
	 */
	public function enable_field_access( $content, $popup_id = 0 ) {
		$this->current_popup_id = (int) $popup_id;

		add_filter( 'acf/shortcode/prevent_access_to_fields_on_non_public_posts', [ $this, 'allow_current_popup_fields' ], 10, 2 );

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
		remove_filter( 'acf/shortcode/prevent_access_to_fields_on_non_public_posts', [ $this, 'allow_current_popup_fields' ], 10 );

		$this->current_popup_id = 0;

		return $content;
	}

	/**
	 * Relax ACF's non-public-post guard only for the popup currently rendering.
	 *
	 * Scoped to the popup's own ID so `[acf post_id=<other private post>]` can't
	 * read fields from unrelated posts.
	 *
	 * @param bool       $prevent_access Whether ACF should block field access.
	 * @param int|string $post_id        Post the `[acf]` shortcode is reading from.
	 *
	 * @return bool
	 */
	public function allow_current_popup_fields( $prevent_access, $post_id = 0 ) {
		if ( $this->current_popup_id && is_numeric( $post_id ) && (int) $post_id === $this->current_popup_id ) {
			return false;
		}

		return $prevent_access;
	}
}
