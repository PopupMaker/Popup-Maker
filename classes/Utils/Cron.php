<?php
/**
 * Cron Utility
 *
 * @package   PUM
 * @copyright Copyright (c) 2023, Code Atlantic LLC
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PUM_Utils_Cron
 *
 * @since 1.8.0
 */
class PUM_Utils_Cron {

	/**
	 * PUM_Utils_Cron constructor.
	 */
	public function __construct() {
		add_filter( 'cron_schedules', [ $this, 'add_schedules' ] );
		add_action( 'wp', [ $this, 'schedule_events' ] );
	}

	/**
	 * Registers new cron schedules
	 *
	 * @param array $schedules
	 *
	 * @return array
	 */
	public function add_schedules( $schedules = [] ) {
		// Adds once weekly to the existing schedules.
		$schedules['weekly'] = [
			'interval' => 604800,
			'display'  => __( 'Once Weekly', 'popup-maker' ),
		];

		return $schedules;
	}

	/**
	 * Schedules our events
	 */
	public function schedule_events() {
		$this->weekly_events();
		$this->daily_events();
	}

	/**
	 * Schedule weekly events
	 */
	private function weekly_events() {
		if ( ! wp_next_scheduled( 'pum_weekly_scheduled_events' ) ) {
			wp_schedule_event( current_time( 'timestamp' ), 'weekly', 'pum_weekly_scheduled_events' );
		}
	}

	/**
	 * Schedule daily events
	 */
	private function daily_events() {
		if ( ! wp_next_scheduled( 'pum_daily_scheduled_events' ) ) {
			wp_schedule_event( current_time( 'timestamp' ), 'daily', 'pum_daily_scheduled_events' );
		}
	}

}
