<?php
/**
 * Upgrade Routine 6 - Clean up old data and verify data integrity.
 *
 * @package     PUM
 * @subpackage  Admin/Upgrades
 * @copyright   Copyright (c) 2023, Code Atlantic LLC
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
 * Class PUM_Admin_Upgrade_Routine_6
 */
final class PUM_Admin_Upgrade_Routine_6 extends PUM_Admin_Upgrade_Routine {

	/**
	 * @var array
	 */
	public static $valid_themes;

	/**
	 * @var null
	 */
	public static $default_theme = null;

	/**
	 * Returns the description.
	 *
	 * @return mixed|void
	 */
	public static function description() {
		return __( 'Clean up old data and verify data integrity.', 'popup-maker' );
	}

	/**
	 * Run the update.
	 */
	public static function run() {
		if ( ! current_user_can( PUM_Admin_Upgrades::instance()->required_cap ) ) {
			wp_die( esc_html__( 'You do not have permission to do upgrades', 'popup-maker' ), esc_html__( 'Error', 'popup-maker' ), [ 'response' => 403 ] );
		}

		ignore_user_abort( true );

		if ( ! pum_is_func_disabled( 'set_time_limit' ) ) {
			// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			@set_time_limit( 0 );
		}

		$upgrades  = PUM_Admin_Upgrades::instance();
		$completed = $upgrades->get_arg( 'completed' );
		$total     = $upgrades->get_arg( 'total' );

		// Install new themes
		pum_install_built_in_themes();

		// Refresh CSS transients
		pum_reset_assets();

		// Set the correct total.
		if ( $total <= 1 ) {
			$popups = wp_count_posts( 'popup' );
			$total  = 0;
			foreach ( $popups as $status ) {
				$total += $status;
			}
			$upgrades->set_arg( 'total', $total );
		}

		$popups = pum_get_popups(
			[
				'number' => $upgrades->get_arg( 'number' ),
				'page'   => $upgrades->get_arg( 'step' ),
				'status' => [ 'any', 'trash', 'auto-draft' ],
				'order'  => 'ASC',
			]
		);

		self::setup_valid_themes();

		// Delete All old meta keys.
		self::delete_all_old_meta_keys();

		// Delete All orphaned meta keys.
		self::delete_all_orphaned_meta_keys();

		self::process_popup_cats_tags();

		if ( $popups ) {
			foreach ( $popups as $popup ) {

				// Check that each popup has a valid theme id
				if ( ! array_key_exists( $popup->get_theme_id(), self::$valid_themes ) ) {
					// Set a valid theme.
					update_post_meta( $popup->ID, 'popup_theme', self::$default_theme );
				}

				++$completed;
			}

			if ( $completed < $total ) {
				$upgrades->set_arg( 'completed', $completed );
				self::next_step();
			}
		}

		self::done();
	}

	/**
	 * Create a list of valid popup themes.
	 */
	public static function setup_valid_themes() {
		self::$valid_themes = [];

		foreach ( pum_get_all_themes() as $theme ) {
			self::$valid_themes[ $theme->ID ] = $theme;
			if ( pum_get_default_theme_id() === $theme->ID ) {
				self::$default_theme = $theme->ID;
			}
		}

		if ( ! self::$default_theme ) {
			reset( self::$valid_themes );
			self::$default_theme = self::$valid_themes[ key( self::$valid_themes ) ]->ID;
		}
	}

	/**
	 * Delete orphaned post meta keys.
	 */
	public static function delete_all_orphaned_meta_keys() {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->query( "DELETE pm FROM $wpdb->postmeta pm LEFT JOIN $wpdb->posts wp ON wp.ID = pm.post_id WHERE wp.ID IS NULL AND pm.meta_key LIKE 'popup_%'" );
	}

	/**
	 * Delete all no longer meta keys to clean up after ourselves.
	 *
	 * @return false|int
	 */
	public static function delete_all_old_meta_keys() {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$query = $wpdb->query(
			"
			DELETE FROM $wpdb->postmeta
			WHERE meta_key LIKE 'popup_display_%'
			OR meta_key LIKE 'popup_close_%'
			OR meta_key LIKE 'popup_auto_open_%'
			OR meta_key LIKE 'popup_click_open_%'
			OR meta_key LIKE 'popup_targeting_condition_%'
			OR meta_key LIKE 'popup_loading_condition_%'
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

		return $query;
	}

	/**
	 * Checks for popup taxonomy counts and disables popup taxonomies if none are found.
	 */
	public static function process_popup_cats_tags() {
		global $popmake_options;

		// Setup the Popup Taxonomies
		if ( function_exists( 'popmake_setup_taxonomies' ) ) {
			popmake_setup_taxonomies( true );
		}

		$categories = wp_count_terms( [
			'taxonomy'   => 'popup_category',
			'hide_empty' => true,
		] );

		$tags = wp_count_terms( [
			'taxonomy'   => 'popup_tag',
			'hide_empty' => true,
		] );

		if ( is_wp_error( $tags ) ) {
			$tags = 0;
		}

		if ( is_wp_error( $categories ) ) {
			$categories = 0;
		}

		$popmake_options['disable_popup_category_tag'] = 0 === $categories && 0 === $tags;

		update_option( 'popmake_settings', $popmake_options );
	}
}
