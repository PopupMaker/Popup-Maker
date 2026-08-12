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

		$this->assertFalse( is_admin() );

		add_filter(
			'popup_maker/notification_providers',
			static function ( $providers ) use ( $provider ) {
				$providers[] = $provider;
				return $providers;
			}
		);
		$manager->register_lazy_boot();

		$alerts = apply_filters( 'pum_alert_list', [] );
		$codes  = wp_list_pluck( $alerts, 'code' );

		$this->assertContains( 'deferred_test_provider', $codes );
		$this->assertContains( $provider, $manager->get_providers() );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 *
	 * @return void
	 */
	public function test_custom_deferred_boot_hook_boots_the_manager_on_frontend() {
		$manager     = new \PopupMaker\Services\Notifications\Manager( \PopupMaker\plugin() );
		$provider    = new PUM_Test_Deferred_Notification_Provider();
		$custom_hook = 'pum_test_custom_deferred_notification_boot';

		$this->assertFalse( is_admin() );

		add_filter(
			'popup_maker/notification_providers',
			static function ( $providers ) use ( $provider ) {
				$providers[] = $provider;
				return $providers;
			}
		);

		add_filter(
			'popup_maker/notifications/deferred_boot_hooks',
			static function ( $hooks ) use ( $custom_hook ) {
				$hooks[] = $custom_hook;
				return $hooks;
			}
		);

		$manager->register_lazy_boot();

		$this->assertSame( 'original-value', apply_filters( $custom_hook, 'original-value' ) );
		$this->assertSame( PHP_INT_MIN + 1, has_filter( 'pum_alert_list', [ $provider, 'add_alert' ] ) );
		$this->assertContains( $provider, $manager->get_providers() );
	}
}
