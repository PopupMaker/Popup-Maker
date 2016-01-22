<?php
/**
 * Upgrade Routine 6 - Clean up old data and verify data integrity.
 *
 * @package     PUM
 * @subpackage  Admin/Upgrades
 * @copyright   Copyright (c) 2016, Daniel Iser
 * @license     http://opensource.org/licenses/gpl-3.0.php GNU Public License
 * @since       1.4.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'PUM_Admin_Upgrade_Routine' ) ) {
	require_once POPMAKE_DIR . "includes/admin/upgrades/class-pum-admin-upgrade-routine.php";
}

/**
 * Class PUM_Admin_Upgrade_Routine_6
 */
final class PUM_Admin_Upgrade_Routine_6 extends PUM_Admin_Upgrade_Routine {

	public static $valid_themes = null;

	public static $default_theme = null;

	/**
	 * @return mixed|void
	 */
	public static function description() {
		return __( 'Clean up old data and verify data integrity.', 'popup-maker' );
	}

	/**
	 *
	 */
	public static function run() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have permission to do upgrades', 'popup-maker' ), __( 'Error', 'popup-maker' ), array( 'response' => 403 ) );
		}

		ignore_user_abort( true );

		if ( ! pum_is_func_disabled( 'set_time_limit' ) && ! ini_get( 'safe_mode' ) ) {
			@set_time_limit( 0 );
		}

		$upgrades  = PUM_Admin_Upgrades::instance();
		$completed = $upgrades->get_arg( 'completed' );
		$total     = $upgrades->get_arg( 'total' );

		// Install new themes
		pum_install_built_in_themes();

		// Refresh CSS transients
		pum_force_theme_css_refresh();

		// Set the correct total.
		if ( $total <= 1 ) {
			$popups = wp_count_posts( 'popup' );
			$total  = 0;
			foreach ( $popups as $status ) {
				$total += $status;
			}
			$upgrades->set_arg( 'total', $total );
		}

		$popups = new PUM_Popup_Query( array(
			'number' => $upgrades->get_arg( 'number' ),
			'page'   => $upgrades->get_arg( 'step' ),
			'status' => array( 'any', 'trash', 'auto-draft' ),
			'order'  => 'ASC',
		) );
		$popups = $popups->get_popups();

		static::setup_valid_themes();

		// Delete All old meta keys.
		static::delete_all_old_meta_keys();

		// Delete All orphaned meta keys.
		static::delete_all_orphaned_meta_keys();

		if ( $popups ) {

			foreach ( $popups as $popup ) {

				// Check that each popup has a valid theme id
				if ( ! array_key_exists( $popup->get_theme_id(), static::$valid_themes ) ) {
					// Set a valid theme.
					update_post_meta( $popup->ID, 'popup_theme', static::$default_theme );
				}

				$completed ++;
			}

			if ( $completed < $total ) {
				$upgrades->set_arg( 'completed', $completed );
				static::next_step();
			}

		}

		// TODO Set up option that indicates its safe to disable loading of deprecated functions & filters later.

		static::done();
	}

	public static function setup_valid_themes() {
		static::$valid_themes = array();

		foreach ( popmake_get_all_popup_themes() as $theme ) {
			static::$valid_themes[ $theme->ID ] = $theme;
			if ( popmake_get_default_popup_theme() == $theme->ID ) {
				static::$default_theme = $theme->ID;
			}
		}


		if ( ! static::$default_theme ) {
			reset( static::$valid_themes );
			static::$default_theme = static::$valid_themes[ key( static::$valid_themes ) ];
		}
	}

	public static function delete_all_orphaned_meta_keys() {
		global $wpdb;

		$wpdb->query( "
			DELETE pm
			FROM $wpdb->postmeta pm
			LEFT JOIN $wpdb->posts wp ON wp.ID = pm.post_id
			WHERE wp.ID IS NULL
			AND pm.meta_key LIKE 'popup_%'"
		);
	}

	public static function delete_all_old_meta_keys() {
		global $wpdb;

		$wpdb->query( "
			DELETE FROM $wpdb->postmeta
			WHERE meta_key LIKE 'popup_display_%'
			OR meta_key LIKE 'popup_close_%'
			OR meta_key LIKE 'popup_auto_open_%'
			OR meta_key LIKE 'popup_click_open_%'
			OR meta_key LIKE 'popup_targeting_condition_%'
			OR meta_key = 'popup_admin_debug'
			OR meta_key = 'popup_defaults_set'
			OR meta_key LIKE 'popup_display_%'
			OR meta_key = 'popup_auto_open'
			OR meta_key = 'popup_click_open'
			OR meta_key LIKE 'popup_theme_overlay_%'
			OR meta_key LIKE 'popup_theme_container_%'
			OR meta_key LIKE 'popup_theme_title_%'
			OR meta_key LIKE 'popup_theme_content_%'
			OR meta_key LIKE 'popup_theme_close_%'
			OR meta_key = 'popmake_default_theme'
			OR meta_key = 'popup_theme_defaults_set'
			"
		);
	}

}
