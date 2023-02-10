<?php
/**
 * Telemetry class
 *
 * @package   PUM
 * @copyright Copyright (c) 2023, Code Atlantic LLC
 */

if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
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

	/**
	 * Initialization method
	 */
	public static function init() {
		add_action( 'pum_daily_scheduled_events', [ __CLASS__, 'track_check' ] );
		if ( is_admin() && current_user_can( 'manage_options' ) ) {
			add_filter( 'pum_alert_list', [ __CLASS__, 'optin_alert' ] );
			add_action( 'pum_alert_dismissed', [ __CLASS__, 'optin_alert_check' ], 10, 2 );
		}
	}

	/**
	 * Prepares and sends data, if it is time to do so
	 *
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
	 *
	 * @return array
	 * @since 1.11.0
	 */
	public static function setup_data() {
		global $wpdb;

		// Retrieve current theme info.
		$theme_data = wp_get_theme();
		$theme      = $theme_data->Name . ' ' . $theme_data->Version;

		// Retrieve current plugin information.
		if ( ! function_exists( 'get_plugins' ) ) {
			include ABSPATH . '/wp-admin/includes/plugin.php';
		}

		$plugins        = array_keys( get_plugins() );
		$active_plugins = get_option( 'active_plugins', [] );

		foreach ( $plugins as $key => $plugin ) {
			if ( in_array( $plugin, $active_plugins ) ) {
				// Remove active plugins from list so we can show active and inactive separately.
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

		// Aggregates important settings across all popups.
		$all_popups = pum_get_all_popups();
		$triggers   = [];
		$cookies    = [];
		$conditions = [];
		$location   = [];
		$sizes      = [];
		$sounds     = [];

		// Cycle through each popup.
		foreach ( $all_popups as $popup ) {
			$settings = PUM_Admin_Popups::parse_values( $popup->get_settings() );

			// Cycle through each trigger to count the number of unique triggers.
			foreach ( $settings['triggers'] as $trigger ) {
				if ( isset( $triggers[ $trigger['type'] ] ) ) {
					$triggers[ $trigger['type'] ] += 1;
				} else {
					$triggers[ $trigger['type'] ] = 1;
				}
			}

			// Cycle through each cookie to count the number of unique cookie.
			foreach ( $settings['cookies'] as $cookie ) {
				if ( isset( $cookies[ $cookie['event'] ] ) ) {
					$cookies[ $cookie['event'] ] += 1;
				} else {
					$cookies[ $cookie['event'] ] = 1;
				}
			}

			// Cycle through each condition to count the number of unique condition.
			foreach ( $settings['conditions'] as $condition ) {
				foreach ( $condition as $target ) {
					if ( isset( $conditions[ $target['target'] ] ) ) {
						$conditions[ $target['target'] ] += 1;
					} else {
						$conditions[ $target['target'] ] = 1;
					}
				}
			}

			// Add locations setting.
			if ( isset( $location[ $settings['location'] ] ) ) {
				$location[ $settings['location'] ] += 1;
			} else {
				$location[ $settings['location'] ] = 1;
			}

			// Add size setting.
			if ( isset( $sizes[ $settings['size'] ] ) ) {
				$sizes[ $settings['size'] ] += 1;
			} else {
				$sizes[ $settings['size'] ] = 1;
			}

			// Add opening sound setting.
			if ( isset( $sounds[ $settings['open_sound'] ] ) ) {
				$sounds[ $settings['open_sound'] ] += 1;
			} else {
				$sounds[ $settings['open_sound'] ] = 1;
			}
		}

		return [
			// UID.
			'uid'                    => self::get_uuid(),

			// Language Info.
			'language'               => get_bloginfo( 'language' ),
			'charset'                => get_bloginfo( 'charset' ),

			// Server Info.
			'php_version'            => phpversion(),
			'mysql_version'          => $wpdb->db_version(),
			'is_localhost'           => self::is_localhost(),

			// WP Install Info.
			'url'                    => get_site_url(),
			'version'                => Popup_Maker::$VER,
			'wp_version'             => get_bloginfo( 'version' ),
			'theme'                  => $theme,
			'active_plugins'         => $active_plugins,
			'inactive_plugins'       => array_values( $plugins ),

			// Popup Metrics.
			'popups'                 => $popups,
			'popup_themes'           => $popup_themes,
			'open_count'             => get_option( 'pum_total_open_count', 0 ),

			// Popup Maker Settings.
			'block_editor_enabled'   => pum_get_option( 'gutenberg_support_enabled' ),
			'bypass_ad_blockers'     => pum_get_option( 'bypass_adblockers' ),
			'disable_taxonomies'     => pum_get_option( 'disable_popup_category_tag' ),
			'disable_asset_cache'    => pum_get_option( 'disable_asset_caching' ),
			'disable_open_tracking'  => pum_get_option( 'disable_popup_open_tracking' ),
			'default_email_provider' => pum_get_option( 'newsletter_default_provider', 'none' ),

			// Aggregate Popup Settings.
			'triggers'               => $triggers,
			'cookies'                => $cookies,
			'conditions'             => $conditions,
			'locations'              => $location,
			'sizes'                  => $sizes,
			'sounds'                 => $sounds,
		];
	}

	/**
	 * Sends check_in data
	 *
	 * @param array $data Telemetry data to send.
	 * @since 1.11.0
	 */
	public static function send_data( $data = [] ) {
		self::api_call( 'check_in', $data );
	}

	/**
	 * Makes HTTP request to our API endpoint
	 *
	 * @param string $action The specific endpoint in our API.
	 * @param array  $data Any data to send in the body.
	 * @return array|bool False if WP Error. Otherwise, array response from wp_remote_post.
	 * @since 1.11.0
	 */
	public static function api_call( $action = '', $data = [] ) {
		$response = wp_remote_post(
			'https://api.wppopupmaker.com/wp-json/pmapi/v2/' . $action,
			[
				'method'      => 'POST',
				'timeout'     => 20,
				'redirection' => 5,
				'httpversion' => '1.1',
				'blocking'    => false,
				'body'        => $data,
				'user-agent'  => 'POPMAKE/' . Popup_Maker::$VER . '; ' . get_site_url(),
			]
		);

		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			PUM_Utils_Logging::instance()->log( sprintf( 'Cannot send telemetry data. Error received was: %s', esc_html( $error_message ) ) );
			return false;
		}

		return $response;
	}

	/**
	 * Adds admin notice if we haven't asked before.
	 *
	 * @param array $alerts The alerts currently in the alert system.
	 * @return array Alerts for the alert system.
	 * @since 1.11.0
	 */
	public static function optin_alert( $alerts ) {
		if ( ! self::should_show_alert() ) {
			return $alerts;
		}

		$alerts[] = [
			'code'        => 'pum_telemetry_notice',
			'type'        => 'info',
			'message'     => esc_html__( "We are constantly improving Popup Maker but that's difficult to do if we don't know how it's being used. Please allow data sharing so that we can receive a little information on how it is used. You can change this setting at any time on our Settings page. No user data is sent to our servers. No sensitive data is tracked.", 'popup-maker' ),
			'priority'    => 10,
			'dismissible' => true,
			'global'      => false,
			'actions'     => [
				[
					'primary' => true,
					'type'    => 'action',
					'action'  => 'pum_optin_check_allow',
					'text'    => __( 'Allow', 'popup-maker' ),
				],
				[
					'primary' => false,
					'type'    => 'action',
					'action'  => 'dismiss',
					'text'    => __( 'Do not allow', 'popup-maker' ),
				],
				[
					'primary' => false,
					'type'    => 'link',
					'action'  => '',
					'href'    => 'https://docs.wppopupmaker.com/article/528-the-data-the-popup-maker-plugin-collects',
					'text'    => __( 'Learn more', 'popup-maker' ),
				],
			],
		];
		return $alerts;
	}

	/**
	 * Checks if any options have been clicked from admin notices.
	 *
	 * @param string $code The code for the alert.
	 * @param string $action Action taken on the alert.
	 *
	 * @since 1.11.0
	 */
	public static function optin_alert_check( $code, $action ) {
		if ( 'pum_telemetry_notice' === $code ) {
			if ( 'pum_optin_check_allow' === $action ) {
				pum_update_option( 'telemetry', true );
			}
		}
	}

	/**
	 * Whether or not we should show optin alert
	 *
	 * @since 1.11.0
	 * @return bool True if alert should be shown
	 */
	public static function should_show_alert() {
		return false === self::has_opted_in() && current_user_can( 'manage_options' ) && strtotime( self::get_installed_on() . ' +15 minutes' ) < time();
	}

	/**
	 * Determines if it is time to send telemetry data.
	 *
	 * @return bool True if it is time.
	 * @since 1.11.0
	 */
	public static function is_time_to_send() {

		// Only send if admin has opted in.
		if ( ! self::has_opted_in() ) {
			return false;
		}

		// Send a maximum of once per week.
		if ( get_transient( 'pum_tracking_last_send' ) ) {
			return false;
		}
		return true;
	}

	/**
	 * Wrapper to check if site has opted into telemetry
	 *
	 * @return bool True if has opted into telemetry
	 * @since 1.11.0
	 */
	public static function has_opted_in() {
		return false !== pum_get_option( 'telemetry', false );
	}

	/**
	 * Get the datetime string for when PM was installed.
	 *
	 * @return string
	 * @since 1.13.0
	 */
	public static function get_installed_on() {
		$installed_on = get_option( 'pum_installed_on', false );
		if ( ! $installed_on ) {
			$installed_on = current_time( 'mysql' );
		}
		return $installed_on;
	}

	/**
	 * Determines if the site is in a local environment
	 *
	 * @return bool True for local
	 * @since 1.11.0
	 */
	public static function is_localhost() {
		$url = network_site_url( '/' );
		return stristr( $url, 'dev' ) !== false || stristr( $url, 'localhost' ) !== false || stristr( $url, ':8888' ) !== false;

	}

	/**
	 * Generates a new UUID for this site.
	 *
	 * @return string
	 * @since 1.11.0
	 */
	public static function add_uuid() {
		$uuid = wp_generate_uuid4();
		update_option( 'pum_site_uuid', $uuid );
		return $uuid;
	}

	/**
	 * Retrieves the site UUID
	 *
	 * @return string
	 * @since 1.11.0
	 */
	public static function get_uuid() {
		$uuid = get_option( 'pum_site_uuid', false );
		if ( false === $uuid || ! wp_is_uuid( $uuid ) ) {
			$uuid = self::add_uuid();
		}
		return $uuid;
	}
}
