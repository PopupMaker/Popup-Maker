<?php
/**
 * Tests for the Notifications Manager boot lifecycle.
 *
 * The Manager owns the decision of *how* notifications come up — immediately
 * in wp-admin, lazily on the frontend — so these tests exercise that decision
 * directly rather than through Core's wiring.
 *
 * @package Popup_Maker
 */

require_once dirname( __DIR__ ) . '/fixtures/class-pum-test-deferred-notification-provider.php';

/**
 * Verify boot behavior, idempotency, and the addon registration window.
 */
class Notification_Manager_Lifecycle_Test extends WP_UnitTestCase {

	/**
	 * Manager under test.
	 *
	 * @var \PopupMaker\Services\Notifications\Manager
	 */
	protected $manager;

	/**
	 * Provider registered through the public filter.
	 *
	 * @var PUM_Test_Deferred_Notification_Provider
	 */
	protected $provider;

	/**
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		$this->manager  = new \PopupMaker\Services\Notifications\Manager( \PopupMaker\plugin() );
		$this->provider = new PUM_Test_Deferred_Notification_Provider();
	}

	/**
	 * @return void
	 */
	public function tear_down() {
		if ( isset( $GLOBALS['current_screen'] ) ) {
			unset( $GLOBALS['current_screen'] );
		}

		parent::tear_down();
	}

	/**
	 * Restrict the deferred boot list to hooks that have not fired yet.
	 *
	 * The WordPress test environment fires several of the default triggers
	 * before a test body runs — `popup_maker/update_version` on the fresh
	 * install, and `deleted_post` during fixture cleanup — which correctly
	 * makes the manager boot immediately. Tests that need to observe the
	 * dormant frontend state therefore pin the trigger list to hooks that are
	 * genuinely unfired in this request.
	 *
	 * @param string[] $hooks Hooks to use as the complete trigger list.
	 * @return void
	 */
	protected function use_unfired_boot_hooks( array $hooks ) {
		foreach ( $hooks as $hook ) {
			$this->assertFalse(
				did_action( $hook ) > 0 || did_filter( $hook ) > 0,
				sprintf( 'Guard: %s must not have fired yet for this test to be meaningful.', $hook )
			);
		}

		add_filter(
			'popup_maker/notifications/deferred_boot_hooks',
			static function () use ( $hooks ) {
				return $hooks;
			}
		);
	}

	/**
	 * Register the test provider via the public addon filter.
	 *
	 * @return void
	 */
	protected function register_test_provider() {
		$provider = $this->provider;

		add_filter(
			'popup_maker/notification_providers',
			static function ( $providers ) use ( $provider ) {
				$providers[] = $provider;
				return $providers;
			}
		);
	}

	/**
	 * Read the Manager's private boot guard.
	 *
	 * @return bool
	 */
	protected function is_booted() {
		$booted = new ReflectionProperty( $this->manager, 'booted' );

		if ( PHP_VERSION_ID < 80100 ) {
			// Required before PHP 8.1, deprecated no-op on PHP 8.5+.
			$booted->setAccessible( true );
		}

		return (bool) $booted->getValue( $this->manager );
	}

	/**
	 * wp-admin has something about to render alerts, so init() should resolve
	 * providers straight away rather than arming deferred listeners.
	 *
	 * @return void
	 */
	public function test_admin_request_boots_providers_immediately() {
		set_current_screen( 'dashboard' );
		$this->assertTrue( is_admin(), 'Guard: this test requires an admin request.' );

		$this->register_test_provider();

		$this->manager->init();

		$this->assertTrue( $this->is_booted(), 'init() should boot immediately in wp-admin.' );
		$this->assertContains( $this->provider, $this->manager->get_providers() );
		$this->assertFalse(
			has_filter( 'pum_alert_list', [ $this->manager, 'boot_on_demand' ] ),
			'Admin boot should not leave deferred listeners registered.'
		);
	}

	/**
	 * A frontend request should stay dormant until something actually asks for
	 * notifications — init() arms the listeners without constructing providers.
	 *
	 * @return void
	 */
	public function test_frontend_request_defers_boot_until_a_trigger_fires() {
		$this->assertFalse( is_admin(), 'Guard: this test requires a frontend request.' );

		$this->use_unfired_boot_hooks( [ 'pum_alert_list' ] );
		$this->register_test_provider();

		$this->manager->init();

		$this->assertFalse( $this->is_booted(), 'Frontend init() should not resolve providers yet.' );
		$this->assertSame(
			PHP_INT_MIN,
			has_filter( 'pum_alert_list', [ $this->manager, 'boot_on_demand' ] ),
			'Frontend init() should arm the deferred boot listener.'
		);

		// The trigger fires — now providers come up, and the alert lands in
		// the same filter iteration that woke the manager.
		$alerts = apply_filters( 'pum_alert_list', [] );

		$this->assertTrue( $this->is_booted() );
		$this->assertContains( 'deferred_test_provider', wp_list_pluck( $alerts, 'code' ) );
	}

	/**
	 * An ordinary frontend request that never touches a notification hook must
	 * not pay for provider construction.
	 *
	 * @return void
	 */
	public function test_unrelated_frontend_request_never_boots() {
		$this->assertFalse( is_admin(), 'Guard: this test requires a frontend request.' );

		$this->use_unfired_boot_hooks( [ 'pum_alert_list' ] );
		$this->register_test_provider();

		$this->manager->init();

		// Traffic that has nothing to do with notifications.
		do_action( 'wp_enqueue_scripts' );
		do_action( 'wp_head' );
		apply_filters( 'the_content', 'irrelevant' );

		$this->assertFalse( $this->is_booted(), 'Unrelated frontend traffic must not boot notifications.' );
		$this->assertSame( [], $this->readProviders(), 'No providers should have been resolved.' );
	}

	/**
	 * Read the resolved provider list without forcing a boot.
	 *
	 * @return array<int,mixed>
	 */
	protected function readProviders() {
		$providers = new ReflectionProperty( $this->manager, 'providers' );

		if ( PHP_VERSION_ID < 80100 ) {
			// Required before PHP 8.1, deprecated no-op on PHP 8.5+.
			$providers->setAccessible( true );
		}

		return (array) $providers->getValue( $this->manager );
	}

	/**
	 * An upgrade dispatches popup_maker/update_version during plugins_loaded,
	 * before init@5 runs. The manager must notice and boot immediately.
	 *
	 * @return void
	 */
	public function test_already_fired_trigger_boots_immediately() {
		$this->assertFalse( is_admin(), 'Guard: this test requires a frontend request.' );

		$this->register_test_provider();

		do_action( 'popup_maker/update_version', '1.0.0', '0.9.0' );

		$this->manager->init();

		$this->assertTrue( $this->is_booted(), 'An already-fired trigger should boot the manager.' );
		$this->assertContains( $this->provider, $this->manager->get_providers() );
	}

	/**
	 * When a *later* hook in the list already fired, the earlier hooks must not
	 * be left wired to an already-booted manager.
	 *
	 * @return void
	 */
	public function test_already_fired_late_trigger_leaves_no_stale_listeners() {
		$this->assertFalse( is_admin(), 'Guard: this test requires a frontend request.' );

		$this->register_test_provider();

		// `deactivated_plugin` sits near the end of the default hook list,
		// well after `pum_alert_list`.
		do_action( 'deactivated_plugin', 'some-plugin/some-plugin.php', false );

		$this->manager->init();

		$this->assertTrue( $this->is_booted() );
		$this->assertFalse(
			has_filter( 'pum_alert_list', [ $this->manager, 'boot_on_demand' ] ),
			'A late already-fired trigger must not leave earlier hooks registered.'
		);
	}

	/**
	 * Once booted through a trigger, the deferred listeners should be released
	 * rather than firing a no-op boot on every later hook.
	 *
	 * @return void
	 */
	public function test_deferred_listeners_are_released_after_boot() {
		$this->assertFalse( is_admin(), 'Guard: this test requires a frontend request.' );

		$this->use_unfired_boot_hooks( [ 'pum_alert_list', 'pum_alert_dismissed', 'deactivated_plugin' ] );
		$this->register_test_provider();
		$this->manager->init();

		$this->assertNotFalse( has_filter( 'pum_alert_list', [ $this->manager, 'boot_on_demand' ] ) );

		apply_filters( 'pum_alert_list', [] );

		$this->assertTrue( $this->is_booted() );

		foreach ( [ 'pum_alert_list', 'pum_alert_dismissed', 'deactivated_plugin' ] as $hook ) {
			$this->assertFalse(
				has_filter( $hook, [ $this->manager, 'boot_on_demand' ] ),
				sprintf( 'Deferred listener on %s should be released after boot.', $hook )
			);
		}
	}

	/**
	 * Addons load on plugins_loaded 12+, after Core at 11 — a provider
	 * registered any time before the manager boots must still be picked up.
	 *
	 * @return void
	 */
	public function test_provider_registered_late_is_still_booted() {
		$this->assertFalse( is_admin(), 'Guard: this test requires a frontend request.' );

		$this->use_unfired_boot_hooks( [ 'pum_alert_list' ] );

		// Manager arms its listeners before the addon registers anything.
		$this->manager->init();

		$this->register_test_provider();

		$alerts = apply_filters( 'pum_alert_list', [] );

		$this->assertContains( $this->provider, $this->manager->get_providers() );
		$this->assertContains( 'deferred_test_provider', wp_list_pluck( $alerts, 'code' ) );
	}

	/**
	 * Addons can contribute their own deferred boot hooks, and those must be
	 * honored as wake triggers.
	 *
	 * @return void
	 */
	public function test_addon_provided_deferred_hook_triggers_boot() {
		$this->assertFalse( is_admin(), 'Guard: this test requires a frontend request.' );

		$custom_hook = 'pum_test_addon_deferred_boot_hook';

		$this->register_test_provider();

		// Replace the defaults so the addon's hook is the only live trigger —
		// several defaults have already fired in the test environment.
		$this->use_unfired_boot_hooks( [ $custom_hook ] );

		$this->manager->init();

		$this->assertFalse( $this->is_booted(), 'Guard: should still be dormant before the custom hook fires.' );

		// The addon's own hook wakes the manager, and passes its value through.
		$this->assertSame( 'original-value', apply_filters( $custom_hook, 'original-value' ) );

		$this->assertTrue( $this->is_booted(), 'An addon-provided hook should trigger boot.' );
		$this->assertContains( $this->provider, $this->manager->get_providers() );
	}

	/**
	 * get_providers() is an explicit demand for live providers, so it must
	 * force a boot even on a frontend request that is still dormant.
	 *
	 * @return void
	 */
	public function test_get_providers_forces_boot_on_frontend() {
		$this->assertFalse( is_admin(), 'Guard: this test requires a frontend request.' );

		$this->use_unfired_boot_hooks( [ 'pum_alert_list' ] );
		$this->register_test_provider();

		$this->manager->init();
		$this->assertFalse( $this->is_booted(), 'Guard: should be dormant before get_providers().' );

		$providers = $this->manager->get_providers();

		$this->assertTrue( $this->is_booted(), 'get_providers() should force a boot.' );
		$this->assertContains( $this->provider, $providers );
	}

	/**
	 * get_providers() must boot even when init() was never called at all.
	 *
	 * @return void
	 */
	public function test_get_providers_boots_without_prior_init() {
		$this->register_test_provider();

		$this->assertContains( $this->provider, $this->manager->get_providers() );
		$this->assertTrue( $this->is_booted() );
	}

	/**
	 * Repeated init()/boot/get_providers() calls must not re-resolve providers
	 * or re-run their hook registration.
	 *
	 * @return void
	 */
	public function test_repeated_lifecycle_calls_resolve_providers_once() {
		$resolve_count = 0;

		add_filter(
			'popup_maker/notification_providers',
			function ( $providers ) use ( &$resolve_count ) {
				++$resolve_count;
				$providers[] = $this->provider;
				return $providers;
			}
		);

		$this->manager->init();
		$this->manager->init();
		$this->manager->get_providers();
		$this->manager->get_providers();
		$this->manager->boot_on_demand();
		$this->manager->init();

		$this->assertSame( 1, $resolve_count, 'Providers should resolve exactly once per request.' );
		$this->assertCount(
			1,
			array_keys( $this->manager->get_providers(), $this->provider, true ),
			'The provider should appear exactly once.'
		);
	}

	/**
	 * boot_on_demand() is hooked onto arbitrary filters, so it must return the
	 * filtered value untouched.
	 *
	 * @return void
	 */
	public function test_boot_on_demand_passes_the_filtered_value_through() {
		$this->assertSame( 'untouched', $this->manager->boot_on_demand( 'untouched' ) );
		$this->assertTrue( $this->is_booted() );
	}

	/**
	 * The pre-existing public entry point must keep working for any addon
	 * still calling it.
	 *
	 * @return void
	 */
	public function test_register_lazy_boot_still_delegates_to_init() {
		$this->assertFalse( is_admin(), 'Guard: this test requires a frontend request.' );

		$this->use_unfired_boot_hooks( [ 'pum_alert_list' ] );
		$this->register_test_provider();

		$this->manager->register_lazy_boot();

		$this->assertFalse( $this->is_booted(), 'register_lazy_boot() should behave like init().' );
		$this->assertSame(
			PHP_INT_MIN,
			has_filter( 'pum_alert_list', [ $this->manager, 'boot_on_demand' ] )
		);
	}
}
