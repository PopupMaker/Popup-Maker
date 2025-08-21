<?php
/**
 * Cron Utility
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
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
	 *
	 * Initializes cron utility and registers WordPress hooks.
	 *
	 * @return void
	 */
	public function __construct() {
		add_filter( 'cron_schedules', [ $this, 'add_schedules' ] );
		add_action( 'wp', [ $this, 'schedule_events' ] );
	}

	/**
	 * Registers new cron schedules for WordPress.
	 *
	 * Adds custom schedule intervals to WordPress cron system.
	 *
	 * @param array<string, array{interval: int, display: string}> $schedules WordPress cron schedules array
	 * @return array<string, array{interval: int, display: string}> Modified schedules array with additional schedules
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
	 * Schedules all recurring cron events.
	 *
	 * Initializes both weekly and daily scheduled events for the plugin.
	 *
	 * @return void
	 */
	public function schedule_events() {
		$this->weekly_events();
		$this->daily_events();
	}

	/**
	 * Schedule weekly recurring events.
	 *
	 * Sets up the weekly cron event if it hasn't been scheduled yet.
	 *
	 * @return void
	 */
	private function weekly_events() {
		if ( ! wp_next_scheduled( 'pum_weekly_scheduled_events' ) ) {
			wp_schedule_event( time(), 'weekly', 'pum_weekly_scheduled_events' );
		}
	}

	/**
	 * Schedule daily recurring events.
	 *
	 * Sets up the daily cron event if it hasn't been scheduled yet.
	 *
	 * @return void
	 */
	private function daily_events() {
		if ( ! wp_next_scheduled( 'pum_daily_scheduled_events' ) ) {
			wp_schedule_event( time(), 'daily', 'pum_daily_scheduled_events' );
		}
	}
}
