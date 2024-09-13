<?php
/**
 * Class for Admin Notices
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles showing remote notices through the admin alerts system.
 *
 * @since 1.17.0
 */
class PUM_Admin_Notices {

	/**
	 * Enqueues and sets up pointers across our admin pages.
	 */
	public static function init() {
		if ( is_admin() && current_user_can( 'manage_options' ) ) {
			add_filter( 'pum_alert_list', [ __CLASS__, 'tips_alert' ] );
			add_action( 'pum_alert_dismissed', [ __CLASS__, 'alert_handler' ], 10, 2 );
			add_filter( 'pum_alert_list', [ __CLASS__, 'upcoming_min_req_changes' ], 10 );
		}
	}

	/**
	 * Adds a 'tip' alert occasionally inside PM's admin area
	 *
	 * @since 1.17.0
	 *
	 * @param array $alerts The alerts currently in the alert system.
	 * @return array Alerts for the alert system.
	 */
	public static function tips_alert( $alerts ) {
		if ( ! self::should_show_notice() ) {
			return $alerts;
		}

		$notices = self::get_notices();

		if ( empty( $notices ) ) {
			return $alerts;
		}

		// Foreach notice, add it to the alerts array.
		foreach ( $notices as $notice ) {
			$alert = [
				'code'    => 'pum_notice_' . $notice['id'],
				'type'    => 'success',
				'html'    => $notice['content'],
				'actions' => [],
				'global'  => $notice['is_global'] ? true : false,
			];

			if ( ! empty( $notice['link'] ) ) {
				$alert['actions'][] = [
					'primary' => true,
					'type'    => 'link',
					'action'  => '',
					'href'    => $notice['link'],
					'text'    => __( 'Learn more', 'popup-maker' ),
				];
			}

			$alert['actions'][] = [
				'primary' => false,
				'type'    => 'action',
				'action'  => 'dismiss',
				'text'    => __( 'Dismiss', 'popup-maker' ),
			];

			$alert['actions'][] = [
				'primary' => false,
				'type'    => 'action',
				'action'  => 'disable_notices',
				'text'    => __( 'Turn off these occasional notices', 'popup-maker' ),
			];

			$alerts[] = $alert;
		}

		return $alerts;
	}

	/**
	 * Get notices to show.
	 *
	 * @return array
	 */
	public static function get_notices() {
		$notices = get_transient( 'pum_plugin_notices' );

		if ( ! $notices ) {
			$notices = self::fetch_notices();
		}

		$dismissed_notices = get_option( 'pum_dismissed_notices', [] );

		// Remove dismissed notices.
		if ( $dismissed_notices ) {
			foreach ( $notices as $key => $notice ) {
				if ( in_array( $notice->id, $dismissed_notices, true ) ) {
					unset( $notices[ $key ] );
				}
			}
		}

		// Removed filtered notices.
		foreach ( $notices as $key => $notice ) {
			if ( ! empty( $notice['filters'] ) ) {
				foreach ( $notice['filters'] as $filter ) {
					$filter = explode( ':', $filter );
					$type   = trim( $filter[0] );
					$extra  = trim( ! empty( $filter[1] ) ? $filter[1] : '' );

					if ( ! empty( $type ) ) {
						switch ( $type ) {
							case 'plugin':
								if ( $extra && ! is_plugin_active( "$extra/$extra.php" ) ) {
									unset( $notices[ $key ] );
								}
								break;

							case 'theme':
								if ( ! wp_get_theme( $extra )->exists() ) {
									unset( $notices[ $key ] );
								}
								break;

							case 'environment':
								if ( $extra && ! self::check_environment( $extra ) ) {
									unset( $notices[ $key ] );
								}
								break;
						}
					}
				}
			}
		}

		return $notices;
	}


	/**
	 * Fetch list of notices from the Popup Maker server.
	 *
	 * @since 1.17.0
	 *
	 * @return array
	 */
	public static function fetch_notices() {
		$notices = wp_remote_get( 'https://wppopupmaker.com/wp-json/wp/v2/plugin-notices' );

		if ( is_wp_error( $notices ) ) {
			return [];
		}

		$notices = json_decode( wp_remote_retrieve_body( $notices ), true );

		if ( ! is_array( $notices ) ) {
			return [];
		}

		foreach ( $notices as $i => $notice ) {
			$notices[ $i ] = [
				'id'        => $notice['id'],
				'content'   => $notice['content']['rendered'],
				'excerpt'   => $notice['excerpt']['rendered'],
				'link'      => $notice['acf']['learn_more_url'],
				'is_global' => $notice['acf']['is_global'],
				'filters'   => $notice['acf']['use_filters'] ? $notice['acf']['filters'] : [],
			];
		}

		set_transient( 'pum_plugin_notices', $notices, 12 * HOUR_IN_SECONDS );

		return $notices;
	}


	/**
	 * Checks if any options have been clicked from admin notices.
	 *
	 * @since 1.17.0
	 *
	 * @param string $code The code for the alert.
	 * @param string $action Action taken on the alert.
	 */
	public static function alert_handler( $code, $action ) {
		if ( strpos( $code, 'pum_notice_' ) === 0 ) {
			if ( 'disable_notices' === $action ) {
				pum_update_option( 'disable_notices', true );
			}
		}
	}

	/**
	 * Whether or not we should show notice alert
	 *
	 * @since 1.17.0
	 *
	 * @return bool True if the alert should be shown
	 */
	public static function should_show_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		if ( self::has_turned_off_notices() ) {
			return false;
		}

		if ( strtotime( self::get_installed_on() . ' +3 days' ) < time() ) {
			// return false;
		}

		return true;
	}

	/**
	 * Checks to see if site has turned off PM notices
	 *
	 * @since 1.17.0
	 *
	 * @return bool True if site has disabled notices
	 */
	public static function has_turned_off_notices() {
		return true === pum_get_option( 'disable_notices', false ) || 1 === intval( pum_get_option( 'disable_notices', false ) );
	}

	/**
	 * Get the datetime string for when PM was installed.
	 *
	 * @since 1.17.0
	 *
	 * @return string
	 */
	public static function get_installed_on() {
		$installed_on = get_option( 'pum_installed_on', false );
		if ( ! $installed_on ) {
			$installed_on = current_time( 'mysql' );
		}
		return $installed_on;
	}

	/**
	 * Checks if the current environment is a development environment.
	 *
	 * @since 1.17.0
	 *
	 * @param string $type The type of environment to check for.
	 *
	 * @return bool
	 */
	public static function check_environment( $type ) {
		$env = function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production';

		switch ( $type ) {
			case 'local':
				return 'local' === $env;
			case 'staging':
				return 'staging' === $env;
			case 'development':
				return 'development' === $env;
			case 'production':
				return 'production' === $env;
		}

		return false;
	}

	/**
	 * Wrap notice with admin only awareness message.
	 *
	 * @param string $notice
	 *
	 * @return string
	 *
	 * since 1.20.0
	 */
	public static function wrap_notice( $notice, $current_version, $future_version ) {
		// Messy, but it works for now, clean up in the future when this class is refactored.
		$notice = sprintf(
			$notice,
			'<h3 style="padding: 0; margin-left: 0; margin-top: 0; margin-right: 0; border: none; margin-bottom: inherit;">',
			$future_version,
			"</h3>\n\n",
			'<span style="font-family: Consolas, Courier, monospace; text-decoration: underline; font-weight: bold;">' . $current_version . '</span>'
		);

		// FInally append the notice with a small note to the admin that nobody else will see this notice.
		return '<div style="padding-top: 1em; padding-bottom: 1em;">' . wpautop( $notice ) . '<small><em>' . esc_html__( '** You are seeing this notice because you are an administrator. Other users of the site will see nothing.', 'popup-maker' ) . '</small></em></div>';
	}

	/**
	 * Adds a notice about upcoming minimum PHP & WP version changes.
	 *
	 * @param array $alerts The alerts currently in the alert system.
	 *
	 * @return array Alerts for the alert system.
	 *
	 * @since 1.20.0
	 */
	public static function upcoming_min_req_changes( $alerts ) {
		global $wp_version;

		if ( ! current_user_can( 'manage_options' ) ) {
			return $alerts;
		}

		$plugin_version     = \PopupMaker\config( 'version' );
		$future_php_version = \PopupMaker\config( 'future_php_req' );
		$future_wp_version  = \PopupMaker\config( 'future_wp_req' );

		if ( $future_php_version && version_compare( $future_php_version, phpversion(), '>' ) ) {
			$alerts[] = [
				// Show the notice on every update. Yes, annoying, but not as annoying as a plugin breaking.
				'code'   => sprintf( 'php_%s_%s', $future_php_version, $plugin_version ),
				'type'   => 'error',
				'html'   => self::wrap_notice(
					__( "%1\$sPopup Maker will soon require PHP Version %2\$s.%3\$s \n\nYou're using Version %4\$s. Please ask your host to upgrade your server's PHP.", 'popup-maker' ),
					phpversion(),
					$future_php_version
				),
				'global' => true,
			];
		}

		if ( $future_wp_version && version_compare( $future_wp_version, $wp_version, '>' ) ) {
			$alerts[] = [
				// Show the notice on every update. Yes, annoying, but not as annoying as a plugin breaking.
				'code'   => sprintf( 'wp_%s_%s', $future_wp_version, $plugin_version ),
				'type'   => 'error',
				'html'   => self::wrap_notice(
					__( "%1\$sPopup Maker will soon require WordPress Version %2\$s.%3\$s \n\nYou're using Version %4\$s. Please ask your host to upgrade your server's WordPress.", 'popup-maker' ),
					$wp_version,
					$future_wp_version
				),
				'global' => true,
			];
		}

		return $alerts;
	}
}
