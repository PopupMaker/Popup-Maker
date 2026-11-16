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
	 * Restore the notification preference after each test.
	 */
	public function tearDown(): void {
		pum_delete_option( 'disable_notifications' );

		parent::tearDown();
	}

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

	/**
	 * Disabled assistive notifications are omitted from the REST panel.
	 */
	public function test_disabled_assistive_notifications_return_an_empty_panel() {
		global $menu;

		$original_menu = $menu;

		pum_update_option( 'disable_notifications', true );

		$filter = static function ( $alerts ) {
			$alerts[] = [
				'code'           => 'test_assistive_notification',
				'message'        => 'Test notification.',
				'type'           => 'info',
				'display_inline' => true,
			];

			return $alerts;
		};

		add_filter( 'pum_alert_list', $filter );

		try {
			$controller = new \PopupMaker\RestAPI\Notifications();
			$response   = $controller->get_items( new WP_REST_Request( 'GET' ) );

			$this->assertSame( [], $response->get_data() );
			$this->assertSame( '0', $response->get_headers()['X-PM-Notifications-Count'] );

			$menu = [
				[ 'Popups', 'edit_posts', 'edit.php?post_type=popup' ],
			];

			PUM_Utils_Alerts::append_alert_count();

			$this->assertSame( 'Popups', $menu[0][0] );
		} finally {
			remove_filter( 'pum_alert_list', $filter );
			$menu = $original_menu;
		}
	}

	/**
	 * Disabled assistive notifications stay off the inline surface while
	 * blocking and global notices remain eligible.
	 */
	public function test_disabled_assistive_notifications_preserve_blocking_inline_alerts() {
		pum_update_option( 'disable_notifications', true );

		$this->assertFalse(
			PUM_Utils_Alerts::is_inline_eligible(
				[
					'type'           => 'success',
					'display_inline' => true,
				]
			)
		);
		$this->assertTrue( PUM_Utils_Alerts::is_inline_eligible( [ 'type' => 'warning' ] ) );
		$this->assertTrue( PUM_Utils_Alerts::is_inline_eligible( [ 'type' => 'error' ] ) );
		$this->assertTrue(
			PUM_Utils_Alerts::is_inline_eligible(
				[
					'type'   => 'info',
					'global' => true,
				]
			)
		);
	}

	/**
	 * Disabled assistive notifications do not render dashboard indicators.
	 */
	public function test_disabled_assistive_notifications_hide_dashboard_indicators() {
		global $menu;

		pum_update_option( 'disable_notifications', true );

		$controller = new \PopupMaker\Controllers\Admin\ToolbarNotifications( \PopupMaker\plugin() );
		$menu       = [
			[ 'Popups', 'edit_posts', 'edit.php?post_type=popup' ],
		];

		$controller->inject_sidebar_marker();

		ob_start();
		$controller->print_styles();
		$controller->print_marker_bootstrap();
		$output = ob_get_clean();

		$this->assertSame( 'Popups', $menu[0][0] );
		$this->assertSame( '', $output );
	}
}
