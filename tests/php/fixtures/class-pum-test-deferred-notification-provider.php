<?php
/**
 * Test provider used to verify hooks added during an active filter still run.
 *
 * @package Popup_Maker
 */

class PUM_Test_Deferred_Notification_Provider implements \PopupMaker\Services\Notifications\Provider {

	/**
	 * @return void
	 */
	public function init() {
		add_filter( 'pum_alert_list', [ $this, 'add_alert' ], PHP_INT_MIN + 1 );
	}

	/**
	 * @param array $alerts Registered alerts.
	 * @return array
	 */
	public function add_alert( $alerts ) {
		$alerts[] = [ 'code' => 'deferred_test_provider' ];

		return $alerts;
	}
}
