<?php
/**
 * Upgrade Routine 3
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
 * Class PUM_Admin_Upgrade_Routine_3
 */
final class PUM_Admin_Upgrade_Routine_3 extends PUM_Admin_Upgrade_Routine {

	/**
	 * Returns a description.
	 *
	 * @return mixed|void
	 */
	public static function description() {
		return __( 'Upgrade popup triggers &amp; cookies.', 'popup-maker' );
	}

	/**
	 * Upgrade popup triggers & cookies.
	 *
	 * - Convert Auto Open
	 * - Convert Click Open
	 */
	public static function run() {
		if ( ! current_user_can( PUM_Admin_Upgrades::instance()->required_cap ) ) {
			wp_die( __( 'You do not have permission to do upgrades', 'popup-maker' ), __( 'Error', 'popup-maker' ), [ 'response' => 403 ] );
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

		$popups = pum_get_popups(
			[
				'number' => $upgrades->get_arg( 'number' ),
				'page'   => $upgrades->get_arg( 'step' ),
				'status' => [ 'any', 'trash', 'auto-draft' ],
				'order'  => 'ASC',
			]
		);

		if ( $popups ) {

			foreach ( $popups as $popup ) {
				$_cookies  = $cookies = [];
				$_triggers = $triggers = [];

				// Convert Click Open Triggers.
				$click_open  = popmake_get_popup_meta_group( 'click_open', $popup->ID );
				$_triggers[] = [
					'type'     => 'click_open',
					'settings' => [
						'extra_selectors' => ! empty( $click_open['extra_selectors'] ) ? $click_open['extra_selectors'] : '',
						'cookie'          => [
							'name' => null,
						],
					],
				];

				// If auto open enabled create a new trigger.
				$auto_open = popmake_get_popup_meta_group( 'auto_open', $popup->ID );
				if ( isset( $auto_open['enabled'] ) && $auto_open['enabled'] ) {

					// Set the new cookie name.
					$cookie_name = 'popmake-auto-open-' . $popup->ID;

					// Append the cookie key if set.
					if ( ! empty( $auto_open['cookie_key'] ) ) {
						$cookie_name .= '-' . $auto_open['cookie_key'];
					}

					// Store cookie_trigger for reuse.
					$cookie_trigger = $auto_open['cookie_trigger'];

					// Create empty trigger cookie in case of disabled trigger.
					$trigger_cookie = null;

					// If cookie trigger not disabled create a new cookie and add it to the auto open trigger.
					if ( 'disabled' !== $cookie_trigger ) {

						// Add the new cookie to the auto open trigger.
						$trigger_cookie = [ $cookie_name ];

						// Set the event based on the original option.
						switch ( $cookie_trigger ) {
							case 'close':
								$event = 'on_popup_close';
								break;
							case 'open':
								$event = 'on_popup_close';
								break;
							default:
								$event = $cookie_trigger;
								break;
						}

						// Add the new cookie to the cookies array.
						$_cookies[] = [
							'event'    => $event,
							'settings' => [
								'name'    => $cookie_name,
								'key'     => '',
								'time'    => $auto_open['cookie_time'],
								'path'    => isset( $auto_open['cookie_path'] ) ? 1 : 0,
								'session' => isset( $auto_open['session_cookie'] ) ? 1 : 0,
							],
						];
					}

					// Add the new auto open trigger to the triggers array.
					$_triggers[] = [
						'type'     => 'auto_open',
						'settings' => [
							'delay'  => ! empty( $auto_open['delay'] ) ? absint( $auto_open['delay'] ) : 500,
							'cookie' => [
								'name' => $trigger_cookie,
							],
						],
					];
				}

				foreach ( $_cookies as $cookie ) {
					$cookie['settings'] = PUM_Cookies::instance()->validate_cookie( $cookie['event'], $cookie['settings'] );
					$cookies[]          = $cookie;
				}

				foreach ( $_triggers as $trigger ) {
					$trigger['settings'] = PUM_Triggers::instance()->validate_trigger( $trigger['type'], $trigger['settings'] );
					$triggers[]          = $trigger;
				}

				update_post_meta( $popup->ID, 'popup_triggers', $triggers );

				update_post_meta( $popup->ID, 'popup_cookies', $cookies );

				$completed ++;

			}

			if ( $completed < $total ) {
				$upgrades->set_arg( 'completed', $completed );
				self::next_step();
			}
		}

		self::done();

	}

	/**
	 * Returns the auto open meta of a popup.
	 *
	 * @since 1.1.0
	 * @deprecated 1.4
	 *
	 * @param int $popup_id ID number of the popup to retrieve a auto open meta for
	 *
	 * @return mixed array|string of the popup auto open meta
	 */
	public static function get_auto_open( $popup_id = null, $key = null, $default = null ) {
		return popmake_get_popup_meta( 'auto_open', $popup_id, $key, $default );
	}
}
