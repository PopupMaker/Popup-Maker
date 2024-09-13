<?php
/**
 * Upgrade Routine 2
 *
 * @package     PopupMaker
 * @copyright   Copyright (c) 2024, Code Atlantic LLC
 * @subpackage  Admin/Upgrades
 * @license     http://opensource.org/licenses/gpl-3.0.php GNU Public License
 * @since       1.4
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'PUM_Admin_Upgrade_Routine' ) ) {
	require_once POPMAKE_DIR . 'includes/admin/upgrades/class-pum-admin-upgrade-routine.php';
}

/**
 * Class PUM_Admin_Upgrade_Routine_2
 */
final class PUM_Admin_Upgrade_Routine_2 extends PUM_Admin_Upgrade_Routine {

	public static function description() {
		return __( 'Update your popups settings.', 'popup-maker' );
	}

	public static function run() {
		if ( ! current_user_can( PUM_Admin_Upgrades::instance()->required_cap ) ) {
			wp_die( esc_html__( 'You do not have permission to do upgrades', 'popup-maker' ), esc_html__( 'Error', 'popup-maker' ), [ 'response' => 403 ] );
		}

		ignore_user_abort( true );

		if ( ! pum_is_func_disabled( 'set_time_limit' ) ) {
			@set_time_limit( 0 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		}

		self::process_popups();
		self::cleanup_old_data();
	}

	public static function process_popups() {

		$popups = get_posts(
			[
				'post_type'      => 'popup',
				'post_status'    => [ 'any', 'trash' ],
				'posts_per_page' => - 1,
			]
		);

		$popup_groups = [
			'display'     => popmake_popup_display_defaults(),
			'close'       => popmake_popup_close_defaults(),
			'click_open'  => [],
			'auto_open'   => [],
			'admin_debug' => [],
		];

		foreach ( $popups as $popup ) {
			foreach ( $popup_groups as $group => $defaults ) {
				$values = array_merge( $defaults, popmake_get_popup_meta_group( $group, $popup->ID ) );
				update_post_meta( $popup->ID, "popup_{$group}", $values );
			}
		}
	}

	public static function cleanup_old_data() {
		global $wpdb;

		$popup_groups = [
			'display',
			'close',
			'click_open',
			'auto_open',
			'admin_debug',
		];

		$popup_fields = [];

		foreach ( $popup_groups as $group ) {
			foreach ( apply_filters( 'popmake_popup_meta_field_group_' . $group, [] ) as $field ) {
				$popup_fields[] = 'popup_' . $group . '_' . $field;
			}
		}

		$placeholder = implode( ',', array_fill( 0, count( $popup_fields ), '%s' ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		$wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->postmeta WHERE meta_key IN($placeholder);", $popup_fields ) );
	}
}
