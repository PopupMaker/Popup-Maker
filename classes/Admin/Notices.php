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
 * Handles local compatibility notices through the admin alerts system.
 *
 * @since 1.17.0
 */
class PUM_Admin_Notices {

	/**
	 * Initialize the admin notices.
	 */
	public static function init() {
		if ( is_admin() && current_user_can( 'manage_options' ) ) {
			add_filter( 'pum_alert_list', [ __CLASS__, 'upcoming_min_req_changes' ], 10 );
		}
	}

	/**
	 * Wrap notice with admin only awareness message.
	 *
	 * @param string $notice Notice template.
	 * @param string $current_version Current version.
	 * @param string $future_version Future required version.
	 *
	 * @return string
	 *
	 * @since 1.20.0
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

		// Finally append the notice with a small note to the admin that nobody else will see this notice.
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
				'code'     => sprintf( 'php_%s_%s', $future_php_version, $plugin_version ),
				'type'     => 'error',
				'category' => 'warning',
				'html'     => self::wrap_notice(
					__( "%1\$sPopup Maker will soon require PHP Version %2\$s.%3\$s \n\nYou're using Version %4\$s. Please ask your host to upgrade your server's PHP.", 'popup-maker' ),
					phpversion(),
					$future_php_version
				),
				'global'   => true,
			];
		}

		if ( $future_wp_version && version_compare( $future_wp_version, $wp_version, '>' ) ) {
			$alerts[] = [
				// Show the notice on every update. Yes, annoying, but not as annoying as a plugin breaking.
				'code'     => sprintf( 'wp_%s_%s', $future_wp_version, $plugin_version ),
				'type'     => 'error',
				'category' => 'warning',
				'html'     => self::wrap_notice(
					__( "%1\$sPopup Maker will soon require WordPress Version %2\$s.%3\$s \n\nYou're using Version %4\$s. Please ask your host to upgrade your server's WordPress.", 'popup-maker' ),
					$wp_version,
					$future_wp_version
				),
				'global'   => true,
			];
		}

		return $alerts;
	}
}
