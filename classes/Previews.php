<?php
/**
 * Manage popup prevews.
 *
 * @package PopupMaker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PUM_Previews
 *
 * This class sets up the necessary changes to allow admins & editors to preview popups on the front end.
 */
class PUM_Previews {

	/**
	 * Initiator method.
	 */
	public static function init() {
		add_action( 'template_redirect', [ __CLASS__, 'force_load_preview' ] );
		add_filter( 'pum_popup_is_loadable', [ __CLASS__, 'is_loadable' ], 1000, 2 );
		add_filter( 'pum_popup_data_attr', [ __CLASS__, 'data_attr' ], 1000, 2 );
		add_filter( 'pum_popup_get_public_settings', [ __CLASS__, 'get_public_settings' ], 1000, 2 );
	}

	/**
	 * Get popup id for previewing.
	 *
	 * @return false|int
	 */
	public static function get_popup_preview() {
		static $preview_id;

		if ( isset( $preview_id ) ) {
			return $preview_id;
		}

		$preview_id = false;

		if (
			! isset( $_GET['popup_preview'] ) ||
			! isset( $_GET['popup'] ) ||
			// Overridden as wp_verify_nonce is already safe: https://github.com/WordPress/WordPress-Coding-Standards/issues/869#issuecomment-611782416.
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			! wp_verify_nonce( $_GET['popup_preview'], 'popup-preview' )
		) {
			return false;
		}

		$popup_id = sanitize_text_field( wp_unslash( $_GET['popup'] ) );

		if ( is_numeric( $_GET['popup'] ) && absint( $_GET['popup'] ) > 0 ) {
			$preview_id = absint( $_GET['popup'] );
		} else {
			$post       = get_page_by_path( $popup_id, OBJECT, 'popup' );
			$preview_id = $post->ID;
		}

		return $preview_id;
	}

	/**
	 * Sets the Popup Post Type public arg to true for content editors.
	 *
	 * This enables them to use the built in preview links.
	 *
	 * @param int $popup_id Popup ID.
	 *
	 * @return bool
	 */
	private static function is_previewing_popup( $popup_id = 0 ) {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return false;
		}

		$preview_id = static::get_popup_preview();

		return $popup_id === $preview_id && current_user_can( 'edit_post', $preview_id );
	}

	/**
	 * Force popup to load no matter its status if its supposed to be previewed.
	 */
	public static function force_load_preview() {
		$preview_id = static::get_popup_preview();

		$popup = pum_get_popup( $preview_id );

		if ( $popup->is_valid() && $preview_id === $popup->ID ) {
			PUM_Site_Popups::preload_popup( $popup );
		}
	}

	/**
	 * For popup previews this will force only the correct popup to load.
	 *
	 * @param bool $loadable Is popup loadable.
	 * @param int  $popup_id Popup ID.
	 *
	 * @return bool
	 */
	public static function is_loadable( $loadable, $popup_id ) {
		return self::is_previewing_popup( $popup_id ) ? true : $loadable;
	}

	/**
	 * On popup previews add an admin debug trigger.
	 *
	 * @deprecated 1.16.10 Use get_public_settings instead.
	 *
	 * @param array $data_attr Array of popup data attributes.
	 * @param int   $popup_id Popup ID.
	 *
	 * @return mixed
	 */
	public static function data_attr( $data_attr, $popup_id ) {
		if ( ! self::is_previewing_popup( $popup_id ) ) {
			return $data_attr;
		}

		$data_attr['triggers'] = [
			[
				'type' => 'admin_debug',
			],
		];

		return $data_attr;
	}

	/**
	 * On popup previews add an admin debug trigger.
	 *
	 * @param array           $settings Array of settigs.
	 * @param PUM_Model_Popup $popup Popup model object.
	 *
	 * @return array
	 */
	public static function get_public_settings( $settings, $popup ) {
		if ( ! self::is_previewing_popup( $popup->ID ) ) {
			return $settings;
		}

		$settings['triggers'] = [
			[
				'type' => 'admin_debug',
			],
		];

		return $settings;
	}
}
