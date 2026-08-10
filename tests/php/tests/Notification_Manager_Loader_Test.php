<?php
/**
 * Tests for on-demand notification provider loading.
 *
 * @package Popup_Maker
 */

require_once dirname( __DIR__ ) . '/fixtures/class-pum-test-deferred-notification-provider.php';

/**
 * Verify frontend requests load notification providers when needed.
 */
class Notification_Manager_Loader_Test extends WP_UnitTestCase {

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 *
	 * @return void
	 */
	public function test_alert_filter_boots_deferred_providers_in_current_iteration() {
		$manager  = new \PopupMaker\Services\Notifications\Manager( \PopupMaker\plugin() );
		$provider = new PUM_Test_Deferred_Notification_Provider();
		$loader   = static function ( $alerts ) use ( $manager ) {
			$manager->init();
			return $alerts;
		};

		$this->assertFalse( is_admin() );
		add_filter( 'pum_alert_list', $loader, PHP_INT_MIN );

		add_filter(
			'popup_maker/notification_providers',
			static function ( $providers ) use ( $provider ) {
				$providers[] = $provider;
				return $providers;
			}
		);

		$alerts = apply_filters( 'pum_alert_list', [] );
		$codes  = wp_list_pluck( $alerts, 'code' );

		$this->assertContains( 'deferred_test_provider', $codes );
		$this->assertContains( $provider, $manager->get_providers() );
	}
}
