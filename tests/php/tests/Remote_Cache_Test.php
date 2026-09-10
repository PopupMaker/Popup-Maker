<?php
/**
 * Remote response cache tests.
 *
 * @package Popup_Maker
 */

/**
 * Verify translation-status requests recognize empty and failed cache entries.
 */
class PUM_Remote_Cache_Test extends WP_UnitTestCase {

	/**
	 * Initialize remote response caches.
	 */
	public function setUp(): void {
		parent::setUp();

		delete_transient( 'pum_alerts_translation_status' );
	}

	/**
	 * Clear remote response caches.
	 */
	public function tearDown(): void {
		delete_transient( 'pum_alerts_translation_status' );

		parent::tearDown();
	}

	/**
	 * A cached empty response remains a cache hit.
	 */
	public function test_cached_empty_responses_skip_remote_requests() {
		$requests = 0;
		$mock     = static function () use ( &$requests ) {
			++$requests;
			return new WP_Error( 'unexpected_request' );
		};

		set_transient( 'pum_alerts_translation_status', [], HOUR_IN_SECONDS );
		add_filter( 'pre_http_request', $mock );

		$this->assertSame( [], PUM_Utils_I10n::translation_status() );

		remove_filter( 'pre_http_request', $mock );

		$this->assertSame( 0, $requests );
	}

	/**
	 * Upstream failures are retried after a short cooldown, not every call.
	 */
	public function test_remote_failures_are_negative_cached() {
		$requests = 0;
		$mock     = static function () use ( &$requests ) {
			++$requests;
			return new WP_Error( 'fixture_failure' );
		};

		add_filter( 'pre_http_request', $mock );

		PUM_Utils_I10n::translation_status();
		PUM_Utils_I10n::translation_status();

		remove_filter( 'pre_http_request', $mock );

		$this->assertSame( 1, $requests );
		$this->assertSame( [], get_transient( 'pum_alerts_translation_status' ) );
	}

	/**
	 * Admin notices retain the local minimum-version warning only.
	 */
	public function test_admin_notices_do_not_register_remote_community_notices() {
		global $wp_version;

		$administrator = self::factory()->user->create( [ 'role' => 'administrator' ] );
		$original_wp   = $wp_version;

		wp_set_current_user( $administrator );
		set_current_screen( 'dashboard' );
		remove_all_filters( 'pum_alert_list' );
		remove_all_actions( 'pum_alert_dismissed' );
		PUM_Admin_Notices::init();

		$this->assertSame( 10, has_filter( 'pum_alert_list', [ PUM_Admin_Notices::class, 'upcoming_min_req_changes' ] ) );
		$this->assertFalse( method_exists( PUM_Admin_Notices::class, 'fetch_notices' ) );
		$this->assertFalse( has_action( 'pum_alert_dismissed', [ PUM_Admin_Notices::class, 'alert_handler' ] ) );

		$wp_version = '1.0';

		try {
			$alerts = PUM_Admin_Notices::upcoming_min_req_changes( [] );
		} finally {
			$wp_version = $original_wp;
		}

		$this->assertNotEmpty( $alerts );
		$this->assertStringStartsWith( 'wp_', $alerts[0]['code'] );
	}
}
