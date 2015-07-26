<?php
/**
 * Tracking functions for reporting plugin usage to the Popup Maker site for users that have opted in
 *
 * @package     Popup Maker
 * @subpackage  Admin
 * @copyright   Copyright (c) 2015, Daniel Iser
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.3.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Usage tracking
 *
 * @access public
 * @since  1.3.0
 * @return void
 */
class Popmake_Tracking {

	/**
	 * The data to send to the Popup Maker site
	 *
	 * @access private
	 */
	private $data;

	/**
	 * Get things going
	 *
	 * @access public
	 */
	public function __construct() {

		$this->schedule_send();

		add_action( 'popmake_settings_general_sanitize', array( $this, 'check_for_settings_optin' ) );
		add_action( 'popmake_opt_into_tracking', array( $this, 'check_for_optin' ) );
		add_action( 'popmake_opt_out_of_tracking', array( $this, 'check_for_optout' ) );
		add_action( 'admin_notices', array( $this, 'admin_notice' ) );

	}

	/**
	 * Check if the user has opted into tracking
	 *
	 * @access private
	 * @return bool
	 */
	private function tracking_allowed() {
		$allow_tracking = popmake_get_option( 'allow_tracking', false );

		return isset( $allow_tracking );
	}

	/**
	 * Setup the data that is going to be tracked
	 *
	 * @access private
	 * @return void
	 */
	private function setup_data() {
		global $wpdb;

		$data = array();

		// Retrieve current theme info
		if ( get_bloginfo( 'version' ) < '3.4' ) {
			$theme_data = get_theme_data( get_stylesheet_directory() . '/style.css' );
			$theme      = $theme_data['Name'] . ' ' . $theme_data['Version'];
		} else {
			$theme_data = wp_get_theme();
			$theme      = $theme_data->Name . ' ' . $theme_data->Version;
		}

		$data['url']           = home_url();
		$data['wp_version']    = get_bloginfo( 'version' );
		$data['version']       = POPMAKE_VERSION;
		$data['theme']         = $theme;
		$data['email']         = get_bloginfo( 'admin_email' );
		$data["mysql_version"] = $wpdb->db_version();
		$data['php_version']   = PHP_VERSION;

		// Retrieve current plugin information
		if ( ! function_exists( 'get_plugins' ) ) {
			include ABSPATH . '/wp-admin/includes/plugin.php';
		}

		$plugins        = array_keys( get_plugins() );
		$active_plugins = get_option( 'active_plugins', array() );

		foreach ( $plugins as $key => $plugin ) {
			if ( in_array( $plugin, $active_plugins ) ) {
				// Remove active plugins from list so we can show active and inactive separately
				unset( $plugins[ $key ] );
			}
		}


		$data['active_plugins']   = $active_plugins;
		$data['inactive_plugins'] = array_values( $plugins );
		$data['popups']           = wp_count_posts( 'popup' )->publish;
		$data['popup_themes']     = wp_count_posts( 'popup_theme' )->publish;

		$this->data = $data;
	}

	/**
	 * Send the data to the Popup Maker server
	 *
	 * @access private
	 *
	 * @param bool $override
	 */
	public function send_checkin( $override = false ) {

		if ( ! $this->tracking_allowed() && ! $override ) {
			return;
		}

		// Send a maximum of once per week
		$last_send = $this->get_last_send();
		if ( $last_send && $last_send > strtotime( '-1 week' ) ) {
			return;
		}

		$this->setup_data();

		wp_remote_post( 'https://wppopupmaker.com/?edd_action=checkin', array(
			'method'      => 'POST',
			'timeout'     => 20,
			'redirection' => 5,
			'httpversion' => '1.1',
			'blocking'    => true,
			'body'        => $this->data,
			'user-agent'  => 'POPMAKE/' . POPMAKE_VERSION . '; ' . get_bloginfo( 'url' )
		) );

		update_option( 'popmake_tracking_last_send', time() );

	}

	/**
	 * Check for a new opt-in on settings save
	 *
	 * This runs during the sanitation of General settings, thus the return
	 *
	 * @access public
	 *
	 * @param $input
	 *
	 * @return array
	 */
	public function check_for_settings_optin( $input ) {
		// Send an initial check in on settings save

		if ( isset( $input['allow_tracking'] ) ) {
			$this->send_checkin( true );
		}

		return $input;

	}

	/**
	 * Check for a new opt-in via the admin notice
	 *
	 * @access public
	 * @return void
	 */
	public function check_for_optin() {

		global $popmake_options;

		$popmake_options['allow_tracking'] = '1';

		update_option( 'popmake_settings', $popmake_options );

		$this->send_checkin( true );

		update_option( 'popmake_tracking_notice', '1' );

	}

	/**
	 * Check for a new opt-in via the admin notice
	 *
	 * @access public
	 * @return void
	 */
	public function check_for_optout() {

		global $popmake_options;
		if ( isset( $popmake_options['allow_tracking'] ) ) {
			unset( $popmake_options['allow_tracking'] );
			update_option( 'popmake_settings', $popmake_options );
		}

		update_option( 'popmake_tracking_notice', '1' );

		wp_redirect( remove_query_arg( 'popmake_action' ) );
		exit;

	}

	/**
	 * Get the last time a checkin was sent
	 *
	 * @access private
	 * @return false|string
	 */
	private function get_last_send() {
		return get_option( 'popmake_tracking_last_send' );
	}

	/**
	 * Schedule a weekly checkin
	 *
	 * @access private
	 * @return void
	 */
	private function schedule_send() {
		// We send once a week (while tracking is allowed) to check in, which can be used to determine active sites
		add_action( 'popmake_weekly_scheduled_events', array( $this, 'send_checkin' ) );
	}

	/**
	 * Display the admin notice to users that have not opted-in or out
	 *
	 * @access public
	 * @return void
	 */
	public function admin_notice() {
		$hide_notice = get_option( 'popmake_tracking_notice' );

		if ( $hide_notice ) {
			return;
		}

		if ( popmake_get_option( 'allow_tracking', false ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if (
			stristr( network_site_url( '/' ), 'dev' ) !== false ||
			stristr( network_site_url( '/' ), 'localhost' ) !== false ||
			stristr( network_site_url( '/' ), ':8888' ) !== false // This is common with MAMP on OS X
		) {
			update_option( 'popmake_tracking_notice', '1' );
		} else {
			$optin_url  = add_query_arg( 'popmake_action', 'opt_into_tracking' );
			$optout_url = add_query_arg( 'popmake_action', 'opt_out_of_tracking' );

			echo '<div class="updated"><p>';
			echo __( 'Allow Popup Maker to track plugin usage? Opt-in to tracking and our newsletter and we will immediately e-mail you a 20% discount which you can use on any of our extensions. No sensitive data is tracked.', 'popup-maker' );
			echo '</p><p>';
			echo '&nbsp;<a href="' . esc_url( $optin_url ) . '" class="button-primary">' . __( 'Allow tracking', 'popup-maker' ) . '</a>';
			echo '&nbsp;<a style="opacity:0.7;" href="' . esc_url( $optout_url ) . '" class="button-secondary">' . __( 'Do not allow tracking', 'popup-maker' ) . '</a>';
			echo '</p></div>';
		}
	}

}

$popmake_tracking = new Popmake_Tracking;
