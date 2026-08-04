<?php
/**
 * Tests for the review request lifecycle.
 *
 * @package Popup_Maker
 */

use PopupMaker\RestAPI\Notifications;

/**
 * Verify review request tracking, surfacing, and dismissal behavior.
 */
class PUM_Modules_Reviews_Test extends WP_UnitTestCase {

	/**
	 * Administrator user ID.
	 *
	 * @var int
	 */
	private static $admin_id;

	/**
	 * Create shared fixtures.
	 *
	 * @param WP_UnitTest_Factory $factory Factory instance.
	 */
	public static function wpSetUpBeforeClass( $factory ) {
		self::$admin_id = $factory->user->create( [ 'role' => 'administrator' ] );
	}

	/**
	 * Prepare an eligible review request for each test.
	 */
	public function setUp(): void {
		parent::setUp();

		PUM_Modules_Reviews::reset_runtime_cache();
		wp_set_current_user( self::$admin_id );
		delete_user_meta( self::$admin_id, '_pum_reviews_already_did' );
		delete_user_meta( self::$admin_id, '_pum_reviews_dismissed_triggers' );
		delete_user_meta( self::$admin_id, '_pum_reviews_last_dismissed' );
		delete_user_meta( self::$admin_id, '_pum_reviews_last_action' );
		delete_user_meta( self::$admin_id, '_pum_reviews_last_review_click' );
		delete_user_meta( self::$admin_id, '_pum_reviews_attempt_count' );
		delete_user_meta( self::$admin_id, '_pum_reviews_last_presented' );
		delete_user_meta( self::$admin_id, '_pum_reviews_action_counts' );
		delete_user_meta( self::$admin_id, '_pum_reviews_impression_count' );
		delete_user_meta( self::$admin_id, '_pum_reviews_last_impression' );
		delete_user_meta( self::$admin_id, '_pum_reviews_legacy_dismissal_reset' );
		delete_user_meta( self::$admin_id, '_pum_reviews_generic_dismissal_migrated' );
		delete_user_meta( self::$admin_id, '_pum_dismissed_alerts' );
		update_option( 'pum_reviews_installed_on', '2020-01-01 00:00:00' );
		update_option( 'pum_total_open_count', 100 );
		pum_delete_option( 'telemetry' );
	}

	/**
	 * Remove telemetry state after each test.
	 */
	public function tearDown(): void {
		pum_delete_option( 'telemetry' );
		parent::tearDown();
	}

	/**
	 * Review interaction metrics follow the core telemetry opt-in.
	 */
	public function test_review_tracking_requires_telemetry_opt_in() {
		$this->assertFalse( PUM_Modules_Reviews::should_track_review_actions() );

		pum_update_option( 'telemetry', true );

		$this->assertTrue( PUM_Modules_Reviews::should_track_review_actions() );
	}

	/**
	 * Remote tracking variables are omitted until telemetry is enabled.
	 */
	public function test_review_alert_only_exposes_remote_tracking_when_opted_in() {
		$alerts = PUM_Modules_Reviews::review_alert( [] );
		ob_start();
		PUM_Modules_Reviews::print_review_request_vars();
		$script = ob_get_clean();

		$this->assertCount( 1, $alerts );
		$this->assertStringNotContainsString( PUM_Modules_Reviews::$api_url, $script );
		$this->assertStringNotContainsString( wp_hash( home_url() . '-' . self::$admin_id ), $script );
		$this->assertStringNotContainsString( '<script', $alerts[0]['html'] );

		pum_update_option( 'telemetry', true );
		PUM_Modules_Reviews::reset_runtime_cache();
		ob_start();
		PUM_Modules_Reviews::print_review_request_vars();
		$script = ob_get_clean();

		$this->assertStringContainsString( PUM_Modules_Reviews::$api_url, $script );
		$this->assertStringContainsString( 'window.pum_review_uuid', $script );
	}

	/**
	 * The review provider opts into inline visibility and snoozes corner closes.
	 */
	public function test_review_alert_declares_shared_surface_and_close_action() {
		$alerts = PUM_Modules_Reviews::review_alert( [] );
		$alert  = $alerts[0];

		$this->assertTrue( $alert['display_inline'] );
		$this->assertTrue( PUM_Utils_Alerts::is_panel_eligible( $alert ) );
		$this->assertTrue( PUM_Utils_Alerts::is_inline_eligible( $alert ) );
		$this->assertSame( 'maybe_later', $alert['dismiss_action'] );
	}

	/**
	 * The panel maps its corner X to a provider's declared close action.
	 */
	public function test_panel_resolves_provider_close_action() {
		$controller = new class() extends Notifications {
			/**
			 * Expose dismissal resolution for testing.
			 *
			 * @param array<string,mixed> $alert  Alert definition.
			 * @param string              $action Requested action.
			 * @return string
			 */
			public function resolve_dismiss_action_for_test( array $alert, $action ) {
				return $this->resolve_dismiss_action( $alert, $action );
			}

			/**
			 * Expose action validation for testing.
			 *
			 * @param array<string,mixed> $alert  Alert definition.
			 * @param string              $action Requested action.
			 * @return string|false
			 */
			public function resolve_action_expires_for_test( array $alert, $action ) {
				return $this->resolve_action_expires( $alert, $action );
			}
		};

		$this->assertSame(
			'maybe_later',
			$controller->resolve_dismiss_action_for_test( [ 'dismiss_action' => 'maybe_later' ], '' )
		);
		$this->assertSame( '', $controller->resolve_dismiss_action_for_test( [], '' ) );
		$this->assertSame( '', $controller->resolve_action_expires_for_test( [], 'am_now_core' ) );
		$this->assertSame( '', $controller->resolve_action_expires_for_test( [ 'allowed_actions' => [ 'am_now_pro' ] ], 'am_now_pro' ) );
		$this->assertFalse( $controller->resolve_action_expires_for_test( [ 'allowed_actions' => [ [] ] ], 'am_now_pro' ) );
	}

	/**
	 * Clicking the review link records intent without claiming completion.
	 */
	public function test_review_link_records_intent_without_permanent_dismissal() {
		$recorded = PUM_Modules_Reviews::record_action( 'am_now_core', 'open_count', '100_opens', 20 );
		$action   = get_user_meta( self::$admin_id, '_pum_reviews_last_action', true );
		$counts   = get_user_meta( self::$admin_id, '_pum_reviews_action_counts', true );

		$this->assertTrue( $recorded );
		$this->assertSame( 'am_now_core', $action['reason'] );
		$this->assertSame( 1, $counts['am_now_core'] );
		$this->assertNotEmpty( get_user_meta( self::$admin_id, '_pum_reviews_last_review_click', true ) );
		$this->assertFalse( PUM_Modules_Reviews::already_did() );
	}

	/**
	 * Alert discovery does not count an attempt before a surface is shown.
	 */
	public function test_attempt_metrics_count_distinct_presentations_once() {
		$this->assertCount( 1, PUM_Modules_Reviews::review_alert( [] ) );
		$this->assertSame( 0, PUM_Modules_Reviews::attempt_count() );
		$this->assertTrue( PUM_Modules_Reviews::needs_impression() );

		$this->assertTrue( PUM_Modules_Reviews::record_action( 'shown_core' ) );

		$last = get_user_meta( self::$admin_id, '_pum_reviews_last_presented', true );
		$this->assertSame( 1, PUM_Modules_Reviews::attempt_count() );
		$this->assertSame( 1, $last['attempt'] );
		$this->assertNotEmpty( $last['trigger_code'] );
		$this->assertFalse( PUM_Modules_Reviews::needs_impression() );
		$this->assertSame( 1, (int) get_user_meta( self::$admin_id, '_pum_reviews_impression_count', true ) );
		$this->assertTrue(
			PUM_Modules_Reviews::record_action(
				'shown_core',
				$last['trigger_group'],
				$last['trigger_code'],
				$last['trigger_priority']
			)
		);
		$this->assertSame( 1, (int) get_user_meta( self::$admin_id, '_pum_reviews_impression_count', true ) );
		$this->assertEmpty( get_user_meta( self::$admin_id, '_pum_reviews_last_dismissed', true ) );
	}

	/**
	 * Filtered destinations declare their product-specific action reason.
	 */
	public function test_review_alert_declares_filtered_destination_reasons() {
		$add_destination = static function ( $destinations ) {
			$destinations['pro'] = [
				'label'   => 'Review Popup Maker Pro',
				'url'     => 'https://example.com/review',
				'reason'  => 'am_now_pro',
				'primary' => true,
			];

			return $destinations;
		};
		add_filter( 'pum_reviews_destinations', $add_destination );

		$alert = PUM_Modules_Reviews::review_alert( [] )[0];

		remove_filter( 'pum_reviews_destinations', $add_destination );

		$this->assertContains( 'am_now_pro', $alert['allowed_actions'] );
		$this->assertStringContainsString( 'data-reason="am_now_pro"', $alert['html'] );
	}

	/**
	 * Invalid filtered context values fall back without unsafe casts.
	 */
	public function test_review_product_context_rejects_non_scalar_values() {
		$invalid_context = static function ( $context ) {
			$context['product'] = [];
			$context['name']    = new stdClass();

			return $context;
		};
		add_filter( 'pum_reviews_product_context', $invalid_context );

		$context = PUM_Modules_Reviews::get_product_context();

		remove_filter( 'pum_reviews_product_context', $invalid_context );

		$this->assertSame( 'core', $context['product'] );
		$this->assertSame( 'Popup Maker', $context['name'] );
	}

	/**
	 * Trigger caches can be reset when test inputs change in one process.
	 */
	public function test_review_trigger_runtime_cache_can_be_reset() {
		$this->assertSame( '100_opens', PUM_Modules_Reviews::get_trigger_code() );

		update_option( 'pum_total_open_count', 500 );
		PUM_Modules_Reviews::reset_runtime_cache();

		$this->assertSame( '500_opens', PUM_Modules_Reviews::get_trigger_code() );
	}

	/**
	 * Core has no request cap while retaining a filter for product policy.
	 */
	public function test_attempts_are_unlimited_by_default() {
		$this->assertSame( 0, PUM_Modules_Reviews::max_attempts() );

		$set_cap = static function () {
			return 3;
		};
		add_filter( 'pum_reviews_max_attempts', $set_cap );

		$this->assertSame( 3, PUM_Modules_Reviews::max_attempts() );

		remove_filter( 'pum_reviews_max_attempts', $set_cap );
	}

	/**
	 * View milestones continue beyond the historical five-million entry.
	 */
	public function test_open_count_milestones_have_no_terminal_threshold() {
		$thresholds = PUM_Modules_Reviews::open_count_thresholds( 50000000 );

		$this->assertContains( 5000000, $thresholds );
		$this->assertContains( 10000000, $thresholds );
		$this->assertSame( 50000000, end( $thresholds ) );
	}

	/**
	 * Core supplies only its WordPress.org review destination.
	 */
	public function test_core_owns_only_its_wordpress_org_destination() {
		$context      = PUM_Modules_Reviews::get_product_context();
		$destinations = PUM_Modules_Reviews::get_review_destinations();

		$this->assertSame( 'core', $context['product'] );
		$this->assertSame( [ 'core' ], array_keys( $destinations ) );
		$this->assertSame( 'Leave a 5-star review', $destinations['core']['label'] );
		$this->assertSame( 'am_now_core', $destinations['core']['reason'] );
	}

	/**
	 * Review messaging connects five-star requests to meaningful outcomes.
	 */
	public function test_review_messaging_is_outcome_oriented() {
		$alerts        = PUM_Modules_Reviews::review_alert( [] );
		$alert         = $alerts[0];
		$time_trigger  = PUM_Modules_Reviews::triggers( 'time_installed', 'one_week' );
		$usage_trigger = PUM_Modules_Reviews::triggers( 'open_count', '100_opens' );

		$this->assertStringContainsString( 'Is Popup Maker helping you grow?', $alert['title'] );
		$this->assertStringContainsString( 'meaningful conversions', $time_trigger['message'] );
		$this->assertStringContainsString( 'meaningful results', $usage_trigger['message'] );
		$this->assertStringContainsString( 'Leave a 5-star review', $alert['html'] );
	}

	/**
	 * Explicit confirmation remains a permanent review-request dismissal.
	 */
	public function test_already_reviewed_confirmation_remains_permanent() {
		$recorded = PUM_Modules_Reviews::record_action( 'already_did', 'open_count', '100_opens', 20 );

		$this->assertTrue( $recorded );
		$this->assertTrue( PUM_Modules_Reviews::already_did() );
	}

	/**
	 * Only stale ambiguous legacy completion records are reset.
	 */
	public function test_stale_legacy_permanent_dismissal_is_reset() {
		update_user_meta( self::$admin_id, '_pum_reviews_already_did', true );
		update_user_meta( self::$admin_id, '_pum_reviews_last_dismissed', '2020-01-01 00:00:00' );

		$this->assertTrue( PUM_Modules_Reviews::reset_stale_legacy_dismissal() );
		$this->assertFalse( PUM_Modules_Reviews::already_did() );
		$this->assertNotEmpty( get_user_meta( self::$admin_id, '_pum_reviews_legacy_dismissal_reset', true ) );
	}

	/**
	 * New explicit completion reasons remain permanent.
	 */
	public function test_structured_permanent_dismissal_is_not_reset() {
		update_user_meta( self::$admin_id, '_pum_reviews_already_did', true );
		update_user_meta( self::$admin_id, '_pum_reviews_last_dismissed', '2020-01-01 00:00:00' );
		update_user_meta( self::$admin_id, '_pum_reviews_last_action', [ 'reason' => 'already_did' ] );

		$this->assertFalse( PUM_Modules_Reviews::reset_stale_legacy_dismissal() );
		$this->assertTrue( PUM_Modules_Reviews::already_did() );
	}

	/**
	 * Recover the review request without disturbing other dismissed alerts.
	 */
	public function test_clears_only_buggy_generic_review_dismissal() {
		update_user_meta(
			self::$admin_id,
			'_pum_dismissed_alerts',
			[
				'review_request' => true,
				'other_alert'    => true,
			]
		);

		PUM_Modules_Reviews::clear_generic_panel_dismissal();

		$dismissed = get_user_meta( self::$admin_id, '_pum_dismissed_alerts', true );
		$this->assertArrayNotHasKey( 'review_request', $dismissed );
		$this->assertTrue( $dismissed['other_alert'] );

		update_user_meta( self::$admin_id, '_pum_dismissed_alerts', [ 'review_request' => true ] );
		PUM_Modules_Reviews::clear_generic_panel_dismissal();

		$this->assertArrayHasKey( 'review_request', get_user_meta( self::$admin_id, '_pum_dismissed_alerts', true ) );
	}
}
