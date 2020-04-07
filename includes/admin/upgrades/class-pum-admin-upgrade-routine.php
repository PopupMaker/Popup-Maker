<?php
/**
 * Upgrade Routine Class
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

/**
 * Class PUM_Admin_Upgrade_Routine
 */
class PUM_Admin_Upgrade_Routine {

	/**
	 * Describe the upgrade routine.
	 *
	 * @return string
	 */
	public static function description() {
		return '';
	}

	/**
	 * Run the upgrade routine.
	 *
	 * @return void
	 */
	public static function run() {
	}

	/**
	 * Properly redirects or returns redirect url if DOING_AJAX.
	 *
	 * @param string $redirect
	 */
	public static function redirect( $redirect = '' ) {
		wp_redirect( $redirect );
		exit;
	}

	/**
	 * Generate the next step ajax response or redirect.
	 */
	public static function next_step() {

		$upgrades = PUM_Admin_Upgrades::instance();

		$upgrades->step_up();

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			echo wp_json_encode( array(
				'status' => sprintf( __( 'Step %d of approximately %d running', 'popup-maker' ), $upgrades->get_arg( 'step' ), $upgrades->get_arg( 'steps' ) ),
				'next'   => $upgrades->get_args(),
			) );
			exit;
		} else {
			$redirect = add_query_arg( $upgrades->get_args(), admin_url() );
			PUM_Admin_Upgrade_Routine::redirect( $redirect );
		}

	}

	public static function done() {

		$upgrades = PUM_Admin_Upgrades::instance();

		delete_option( 'pum_doing_upgrade' );

		$upgrades->set_upgrade_complete( $upgrades->current_routine() );

		$upgrades->set_pum_db_ver( $upgrades->get_arg( 'pum-upgrade' ) );

		$next_routine = $upgrades->next_routine();

		if ( $upgrades->has_upgrades() && $next_routine && $upgrades->get_upgrade( $next_routine ) ) {
			if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {

				$upgrades->set_arg( 'step', 1 );
				$upgrades->set_arg( 'completed', 0 );
				$upgrades->set_arg( 'pum-upgrade', $next_routine );

				echo wp_json_encode( array(
					'status' => sprintf( '<strong>%s</strong>', $upgrades->get_upgrade( $next_routine ) ),
					'next'   => $upgrades->get_args(),
				) );
				exit;
			}
		}

	}
}
