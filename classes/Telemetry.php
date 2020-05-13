<?php
/*******************************************************************************
 * Copyright (c) 2019, Code Atlantic LLC
 ******************************************************************************/

if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly
	exit;
}

/**
 * Our telemetry class.
 *
 * Handles sending usage data back to our servers for those who have opted into our telemetry.
 *
 * @since 1.11.0
 */
class PUM_Telemetry {

	public static function init() {
		add_action( 'pum_daily_scheduled_events', array( __CLASS__, 'track_check' ) );
	}

	/**
	 * Prepares and sends data, if it is time to do so
	 * @since 1.11.0
	 */
	public static function track_check() {
		if ( self::is_time_to_send() ) {
			$data = self::setup_data();
			self::send_data( $data );
			set_transient( 'pum_tracking_last_send', true, 6 * DAY_IN_SECONDS );
		}
	}

	/**
	 * Prepares telemetry data to be sent
	 * @return array
	 * @since 1.11.0
	 */
	public static function setup_data() {
		global $wpdb;

		// Retrieve current theme info
		if ( get_bloginfo( 'version' ) < '3.4' ) {
			$theme_data = get_theme_data( get_stylesheet_directory() . '/style.css' );
			$theme      = $theme_data['Name'] . ' ' . $theme_data['Version'];
		} else {
			$theme_data = wp_get_theme();
			$theme      = $theme_data->Name . ' ' . $theme_data->Version;
		}

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

		$popups = 0;
		foreach ( wp_count_posts( 'popup' ) as $status ) {
			$popups += $status;
		}

		$popup_themes = 0;
		foreach ( wp_count_posts( 'popup_theme' ) as $status ) {
			$popup_themes += $status;
		}

		$user = PUM_Freemius::instance()->fs->get_user();

		$args = array(
			// UID
			'uid'              => md5( strtolower( ! empty( $user->email ) ? $user->email : '' ) ),

			// Language Info
			'language'         => get_bloginfo( 'language' ), // Language
			'charset'          => get_bloginfo( 'charset' ), // Character Set

			// Server Info
			'php_version'      => phpversion(),
			'mysql_version'    => $wpdb->db_version(),
			'is_localhost'     => self::is_localhost(),

			// WP Install Info
			'url'              => get_site_url(),
			'version'          => Popup_Maker::$VER, // Plugin Version
			'wp_version'       => get_bloginfo( 'version' ), // WP Version
			'theme'            => $theme,
			'active_plugins'   => $active_plugins,
			'inactive_plugins' => array_values( $plugins ),

			// Popup Metrics
			'popups'           => $popups,
			'popup_themes'     => $popup_themes,
			'open_count'       => get_option( 'pum_total_open_count', 0 ),
		);

		return $args;
	}

	/**
	 * Simple wrapper for sending check_in data
	 *
	 * @param array $data Telemetry data to send.
	 * @sice 1.11.0
	 */
	public static function send_data( $data = array() ) {
		self::api_call( 'check_in', $data );
	}

	/**
	 * Makes HTTP request to our API endpoint
	 *
	 * @param string $action The specific endpoint in our API.
	 * @param array $data Any data to send in the body.
	 * @return array|bool False if WP Error. Otherwise, array response from wp_remote_post.
	 * @since 1.11.0
	 */
	public static function api_call( $action = '', $data = array() ) {
		$response = wp_remote_post( 'https://api.wppopupmaker.com/wp-json/pmapi/v1/' . $action, array(
			'method'      => 'POST',
			'timeout'     => 20,
			'redirection' => 5,
			'httpversion' => '1.1',
			'blocking'    => false,
			'body'        => $data,
			'user-agent'  => 'POPMAKE/' . Popup_Maker::$VER . '; ' . get_site_url(),
		));

		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			PUM_Utils_Logging::instance()->log( sprintf( 'Cannot send telemetry data. Error received was: %s', esc_html( $error_message ) ) );
			return false;
		}

		return $response;
	}

	/**
	 * Determines if it is time to send telemetry data.
	 * @return bool True if it is time.
	 * @since 1.11.0
	 */
	public static function is_time_to_send() {
		// Send a maximum of once per week
		if ( get_transient( 'pum_tracking_last_send' ) ) {
			return false;
		}
		return true;
	}

	/**
	 * Determines if the site is in a local environment
	 * @return bool True for local
	 * @since 1.11.0
	 */
	public static function is_localhost() {
		$url = network_site_url( '/' );
		return stristr( $url, 'dev' ) !== false || stristr( $url, 'localhost' ) !== false || stristr( $url, ':8888' ) !== false;

	}
}
