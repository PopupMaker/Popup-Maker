<?php
/**
 * Upgrade Routine 2
 *
 * @package     PUM
 * @subpackage  Admin/Upgrades
 * @copyright   Copyright (c) 2016, Daniel Iser
 * @license     http://opensource.org/licenses/gpl-3.0.php GNU Public License
 * @since       1.4
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'PUM_Admin_Upgrade_Routine' ) ) {
	require_once POPMAKE_DIR . "includes/admin/upgrades/class-pum-admin-upgrade-routine.php";
}

/**
 * Class PUM_Admin_Upgrade_Routine_2
 */
final class PUM_Admin_Upgrade_Routine_2 extends PUM_Admin_Upgrade_Routine {

	public static function description() {
		return __( 'Update your popups & themes settings.', 'popup-maker' );
	}

	public static function run() {
		if ( ! current_user_can( PUM_Admin_Upgrades::instance()->required_cap ) ) {
			wp_die( __( 'You do not have permission to do upgrades', 'popup-maker' ), __( 'Error', 'popup-maker' ), array( 'response' => 403 ) );
		}

		ignore_user_abort( true );

		if ( ! pum_is_func_disabled( 'set_time_limit' ) ) {
			@set_time_limit( 0 );
		}

		PUM_Admin_Upgrade_Routine_2::process_popups();
		PUM_Admin_Upgrade_Routine_2::process_popup_themes();
		PUM_Admin_Upgrade_Routine_2::cleanup_old_data();
	}

	public static function process_popups() {

		$popups = get_posts( array(
			'post_type'      => 'popup',
			'post_status'    => array( 'any', 'trash' ),
			'posts_per_page' => - 1,
		) );

		$popup_groups = array(
			'display'     => popmake_popup_display_defaults(),
			'close'       => popmake_popup_close_defaults(),
			'click_open'  => popmake_popup_click_open_defaults(),
			'auto_open'   => popmake_popup_auto_open_defaults(),
			'admin_debug' => popmake_popup_admin_debug_defaults(),
		);

		foreach ( $popups as $popup ) {

			foreach ( $popup_groups as $group => $defaults ) {
				$values = array_merge( $defaults, popmake_get_popup_meta_group( $group, $popup->ID ) );
				update_post_meta( $popup->ID, "popup_{$group}", $values );
			}

		}

	}

	public static function process_popup_themes() {

		$themes = get_posts( array(
			'post_type'      => 'popup_theme',
			'post_status'    => array( 'any', 'trash' ),
			'posts_per_page' => - 1,
		) );

		$theme_groups = array(
			'overlay'   => popmake_popup_theme_overlay_defaults(),
			'container' => popmake_popup_theme_container_defaults(),
			'title'     => popmake_popup_theme_title_defaults(),
			'content'   => popmake_popup_theme_content_defaults(),
			'close'     => popmake_popup_theme_close_defaults(),
		);

		foreach ( $themes as $theme ) {

			foreach ( $theme_groups as $group => $defaults ) {
				$values = array_merge( $defaults, popmake_get_popup_theme_meta_group( $group, $theme->ID ) );
				update_post_meta( $theme->ID, "popup_theme_{$group}", $values );
			}

		}

	}

	public static function cleanup_old_data() {
		global $wpdb;

		$popup_groups = array(
			'display',
			'close',
			'click_open',
			'auto_open',
			'admin_debug',
		);

		$popup_fields = array();

		foreach ( $popup_groups as $group ) {
			foreach ( apply_filters( 'popmake_popup_meta_field_group_' . $group, array() ) as $field ) {
				$popup_fields[] = 'popup_' . $group . '_' . $field;
			}
		}

		$popup_fields = implode( "','", $popup_fields );

		$wpdb->query( "DELETE FROM $wpdb->postmeta WHERE meta_key IN('$popup_fields');" );


		$theme_groups = array(
			'overlay',
			'container',
			'title',
			'content',
			'close',
		);

		$theme_fields = array();

		foreach ( $theme_groups as $group ) {
			foreach ( apply_filters( 'popmake_popup_theme_meta_field_group_' . $group, array() ) as $field ) {
				$theme_fields[] = 'popup_theme_' . $group . '_' . $field;
			}
		}

		$theme_fields = implode( "','", $theme_fields );

		$wpdb->query( "DELETE FROM $wpdb->postmeta WHERE meta_key IN('$theme_fields');" );

	}

}