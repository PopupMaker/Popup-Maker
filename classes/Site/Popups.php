<?php
/**
 * Site Popups
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Post_Types
 */
class PUM_Site_Popups {

	/**
	 * @var PUM_Popup|null
	 *
	 * @deprecated 1.8.0
	 */
	public static $current;

	/**
	 * @var WP_Query|null
	 */
	public static $loaded;

	/**
	 * @var array
	 */
	public static $cached_content = [];

	/**
	 * @var array
	 */
	public static $loaded_ids = [];

	/**
	 * Hook the initialize method to the WP init action.
	 */
	public static function init() {
	}

	/**
	 * Returns the current popup.
	 *
	 * @param bool|object|null $new_popup
	 *
	 * @return null|PUM_Model_Popup
	 *
	 * @deprecated 1.8.0 Use pum()->current_popup directly or PopupMaker\set_current_popup()
	 */
	public static function current_popup( $new_popup = false ) {
		return \PopupMaker\get_current_popup();
	}

	/**
	 * Gets the loaded popup query.
	 *
	 * @return null|WP_Query
	 * @deprecated 1.21.0 Use \PopupMaker\plugin()->get_controller( 'Frontend\Popups' )->get_loaded_popups
	 */
	public static function get_loaded_popups() {
		return \PopupMaker\plugin()->get_controller( 'Frontend\Popups' )->get_loaded_popups();
	}

	/**
	 * Preload popups in the head and determine if they will be rendered or not.
	 *
	 * @uses `pum_preload_popup` filter
	 * @uses `popmake_preload_popup` filter
	 *
	 * @deprecated 1.21.0 Use \PopupMaker\plugin()->get_controller( 'Frontend\Popups' )->preload_popups
	 */
	public static function load_popups() {
		\PopupMaker\plugin()->get_controller( 'Frontend\Popups' )->preload_popups();
	}

	/**
	 * Checks post content to see if there are popups we need to automagically load
	 *
	 * @param string $content The content from the filter.
	 * @return string The content.
	 * @since 1.15
	 * @deprecated 1.21.0 Use \PopupMaker\plugin()->get_controller( 'Frontend\Popups' )->check_content_for_popups
	 */
	public static function check_content_for_popups( $content ) {
		return \PopupMaker\plugin()->get_controller( 'Frontend\Popups' )->check_content_for_popups( $content );
	}

	/**
	 * Preloads popup, if enabled
	 *
	 * @param int $popup_id The popup's ID.
	 * @since 1.15
	 * @deprecated 1.21.0 Use \PopupMaker\plugin()->get_controller( 'Frontend\Popups' )->preload_popup_by_id_if_enabled
	 */
	public static function preload_popup_by_id_if_enabled( $popup_id ) {
		\PopupMaker\plugin()->get_controller( 'Frontend\Popups' )->maybe_preload_popup( $popup_id );
	}

	/**
	 * Preload a popup.
	 *
	 * @param PUM_Model_Popup $popup
	 * @deprecated 1.21.0 Use \PopupMaker\plugin()->get_controller( 'Frontend\Popups' )->preload_popup
	 */
	public static function preload_popup( $popup ) {
		\PopupMaker\plugin()->get_controller( 'Frontend\Popups' )->preload_popup( $popup );
	}

	/**
	 * @deprecated 1.8.0 Use \PopupMaker\plugin()->get_controller( 'Frontend\Popups' )->preload_popup
	 */
	public static function load_popup( $id ) {
		$popup = pum_get_popup( $id );
		if ( $popup && $popup->is_valid() ) {
			\PopupMaker\plugin()->get_controller( 'Frontend\Popups' )->preload_popup( $popup );
		}
	}


	/**
	 * Render the popups in the footer.
	 *
	 * @deprecated 1.21.0 Use \PopupMaker\plugin()->get_controller( 'Frontend\Popups' )->render_popups
	 */
	public static function render_popups() {
		\PopupMaker\plugin()->get_controller( 'Frontend\Popups' )->render_popups();
	}

	/**
	 * @param $popup_id
	 *
	 * @return string|bool
	 *
	 * @deprecated 1.21.0 Use \PopupMaker\plugin()->get_controller( 'Frontend\Popups' )->get_content_cache
	 */
	public static function get_cache_content( $popup_id ) {
		return \PopupMaker\plugin()->get_controller( 'Frontend\Popups' )->get_content_cache( $popup_id );
	}
}
