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

	/**
	 * An upgrade fires popup_maker/update_version during plugins_loaded,
	 * before init@5 registers lazy boot — the manager must boot immediately
	 * when a deferred hook already fired earlier in the request.
	 *
	 * @return void
	 */
	public function test_lazy_boot_inits_immediately_when_deferred_hook_already_fired() {
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

		// Simulate the upgrade action firing before register_lazy_boot runs.
		do_action( 'popup_maker/update_version', '1.0.0', '0.9.0' );

		$manager->register_lazy_boot();

		$this->assertContains( $provider, $manager->get_providers() );
	}

	/**
	 * Core must wire the Manager's lazy boot on init — verify through the
	 * real booted plugin rather than a locally constructed manager.
	 *
	 * @return void
	 */
	public function test_core_registers_manager_lazy_boot_on_init() {
		$manager = \PopupMaker\plugin( 'notifications' );

		// Core's wiring results in one of two valid states: deferred boot
		// filters registered, or an immediate boot because a deferred hook
		// (e.g. popup_maker/update_version on a fresh install) already fired.
		$deferred = false !== has_filter( 'pum_alert_list', [ $manager, 'boot_on_demand' ] );

		$booted_prop = new ReflectionProperty( $manager, 'booted' );

		if ( PHP_VERSION_ID < 80100 ) {
			// Required before PHP 8.1, deprecated no-op on PHP 8.5+.
			$booted_prop->setAccessible( true );
		}

		$this->assertTrue(
			$deferred || $booted_prop->getValue( $manager ),
			'Core should register the real manager for deferred boot on frontend requests.'
		);
	}
}
