<?php
/**
 * Upgrade Routine 5 - Initialize popup analytics.
 *
 * @package     PUM
 * @subpackage  Admin/Upgrades
 * @copyright   Copyright (c) 2019, Code Atlantic LLC
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
 * Class PUM_Admin_Upgrade_Routine_5
 */
final class PUM_Admin_Upgrade_Routine_5 extends PUM_Admin_Upgrade_Routine {

	/**
	 * @return mixed|void
	 */
	public static function description() {
		return __( 'Initialize popup analytics.', 'popup-maker' );
	}

	/**
	 *
	 */
	public static function run() {
		if ( ! current_user_can( PUM_Admin_Upgrades::instance()->required_cap ) ) {
			wp_die( __( 'You do not have permission to do upgrades', 'popup-maker' ), __( 'Error', 'popup-maker' ), array( 'response' => 403 ) );
		}

		ignore_user_abort( true );

		if ( ! pum_is_func_disabled( 'set_time_limit' ) ) {
			@set_time_limit( 0 );
		}

		$upgrades  = PUM_Admin_Upgrades::instance();
		$completed = $upgrades->get_arg( 'completed' );
		$total     = $upgrades->get_arg( 'total' );

		// Set the correct total.
		if ( $total <= 1 ) {
			$popups = wp_count_posts( 'popup' );
			$total  = 0;
			foreach ( $popups as $status ) {
				$total += $status;
			}
			$upgrades->set_arg( 'total', $total );
		}

		$popups = pum_get_popups( array(
			'number' => $upgrades->get_arg( 'number' ),
			'page'   => $upgrades->get_arg( 'step' ),
			'status' => array( 'any', 'trash', 'auto-draft' ),
			'order'  => 'ASC',
		) );

		if ( $popups ) {

			foreach ( $popups as $popup ) {

				/**
				 * Initialize the popup meta values for core analytics.
				 */
				PUM_Admin_Upgrade_Routine_5::initialize_analytics( $popup->ID );

				$completed ++;
			}

			if ( $completed < $total ) {
				$upgrades->set_arg( 'completed', $completed );
				PUM_Admin_Upgrade_Routine_5::next_step();
			}

		}

		// Check for popup analytics extension and import those stats if available.
		$total_open_count = get_option( 'popup_analytics_total_opened_count', 0 );

		// Set the sites total open count.
		update_option( 'pum_total_open_count', $total_open_count );

		// If is multisite add this blogs total to the site totals.
		if ( function_exists( 'is_multisite' ) && is_multisite() ) {
			$site_total_open_count = get_site_option( 'pum_site_total_open_count', 0 );
			update_site_option( 'pum_site_total_open_count', $site_total_open_count + $total_open_count );
		}

			/**
		 * TODO Move this to v1.5 routines.
		 */
		/*
		// Check for popup analytics extension and import those stats if available.
		$total_conversion_count = get_site_option( 'popup_analytics_total_conversion_count', 0 );

		// Set the sites total open count.
		update_site_option( 'pum_total_conversion_count', $total_conversion_count );
		 */

		PUM_Admin_Upgrade_Routine_5::done();
	}

	/**
	 * Imports Popup Analytic data if available and initializes all popup analytic meta data.
	 *
	 * @param $popup_id
	 */
	public static function initialize_analytics( $popup_id ) {
		// Open Count
		$open_count = get_post_meta( $popup_id, 'popup_analytic_opened_count', true );
		if ( ! $open_count ) {
			$open_count = 0;
		}

		// Last Open
		$last_open = get_post_meta( $popup_id, 'popup_analytic_last_opened', true );
		if ( ! $last_open ) {
			$last_open = 0;
		}

		// Add the meta.
		update_post_meta( $popup_id, 'popup_open_count', absint( $open_count ) );
		update_post_meta( $popup_id, 'popup_open_count_total', absint( $open_count ) );
		update_post_meta( $popup_id, 'popup_last_opened', absint( $last_open ) );

		/**
		 * TODO Move this to v1.5 routines.
		 */
		/*
		// Conversion Count
		$conversion_count = get_post_meta( $popup_id, 'popup_analytic_conversion_count', true );
		if ( ! $conversion_count ) {
			$conversion_count = 0;
		}

		// Last Conversion
		$last_conversion = get_post_meta( $popup_id, 'popup_analytic_last_conversion', true );
		if ( ! $last_conversion ) {
			$last_conversion = 0;
		}

		// Calculate and set the conversion rate.
		$conversion_rate = $conversion_count / $open_count * 100;

		// Add the meta.
		update_post_meta( $popup_id, 'popup_conversion_count', $conversion_count );
		update_post_meta( $popup_id, 'popup_last_conversion', $last_conversion );
		update_post_meta( $popup_id, 'popup_conversion_rate', $conversion_rate );
		*/
	}

}
