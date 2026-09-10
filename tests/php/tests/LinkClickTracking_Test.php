<?php
/**
 * Link Click Tracking Service tests.
 *
 * @package Popup_Maker
 */

use PopupMaker\Services\LinkClickTracking;

/**
 * Test methods for LinkClickTracking service.
 */
class LinkClickTracking_Test extends WP_UnitTestCase {

	/**
	 * Service instance.
	 *
	 * @var LinkClickTracking
	 */
	private $service;

	/**
	 * Popup ID for test fixtures.
	 *
	 * @var int
	 */
	private $popup_id;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->popup_id = wp_insert_post(
			[
				'post_type'   => 'popup',
				'post_status' => 'publish',
				'post_title'  => 'Link Tracking Test Popup',
			]
		);

		// Create a mock container that the Service base class needs.
		$container     = new stdClass();
		$this->service = $this->getMockBuilder( LinkClickTracking::class )
			->setConstructorArgs( [ $container ] )
			->onlyMethods( [] )
			->getMock();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		$this->service->reset_site_count();
		$this->service->reset_popup_count( $this->popup_id );
		parent::tearDown();
	}

	/**
	 * Test that init registers expected hooks.
	 */
	public function test_init_registers_hooks() {
		$this->service->init();

		$this->assertIsInt(
			has_action( 'pum_analytics_conversion', [ $this->service, 'track_link_click' ] ),
			'Should register pum_analytics_conversion hook.'
		);
	}

	/**
	 * Test track_link_click with valid link_click event.
	 */
	public function test_track_link_click_valid_event() {
		$args = [
			'eventData' => [
				'type'     => 'link_click',
				'url'      => 'https://example.com',
				'linkType' => 'external',
			],
		];

		$this->service->track_link_click( $this->popup_id, $args );

		$this->assertEquals( 1, $this->service->get_site_count(), 'Site count should be 1 after one link click.' );
		$this->assertEquals( 1, $this->service->get_popup_count( $this->popup_id ), 'Popup count should be 1.' );
	}

	/**
	 * A cached missing option is invalidated when the counter is first created.
	 */
	public function test_track_link_click_after_missing_site_count_was_cached() {
		$this->service->reset_site_count();
		$this->assertSame( 0, $this->service->get_site_count() );

		$this->service->track_link_click(
			$this->popup_id,
			[
				'eventData' => [
					'type' => 'link_click',
				],
			]
		);

		$this->assertSame( 1, $this->service->get_site_count() );
	}

	/**
	 * A failed site-counter upsert is reported and suppresses the success action.
	 */
	public function test_site_counter_database_failure_is_reported_without_success_action() {
		global $wpdb;

		$args = [
			'eventData' => [
				'type' => 'link_click',
				'url'  => 'https://example.com',
			],
		];

		add_post_meta( $this->popup_id, LinkClickTracking::POPUP_META_KEY, 0, true );

		$query_filter             = static function ( $query ) {
			if ( false !== strpos( $query, 'INSERT INTO' ) && false !== strpos( $query, LinkClickTracking::SITE_COUNT_KEY ) ) {
				return 'SELECT * FROM pum_missing_analytics_counter_table';
			}

			return $query;
		};
		$failure                  = null;
		$error_action             = static function ( $error, $popup_id, $context ) use ( &$failure ) {
			$failure = compact( 'error', 'popup_id', 'context' );
		};
		$success_fired            = false;
		$success_action           = static function () use ( &$success_fired ) {
			$success_fired = true;
		};
		$previous_suppress_errors = $wpdb->suppress_errors( true );

		add_filter( 'query', $query_filter );
		add_action( 'popup_maker/analytics_counter_failed', $error_action, 10, 3 );
		add_action( 'popup_maker/link_click_tracked', $success_action );

		try {
			$this->service->track_link_click( $this->popup_id, $args );
		} finally {
			remove_filter( 'query', $query_filter );
			remove_action( 'popup_maker/analytics_counter_failed', $error_action );
			remove_action( 'popup_maker/link_click_tracked', $success_action );
			$wpdb->suppress_errors( $previous_suppress_errors );
		}

		$this->assertIsArray( $failure );
		$this->assertWPError( $failure['error'] );
		$this->assertSame( 'pum_analytics_counter_database_error', $failure['error']->get_error_code() );
		$this->assertSame( LinkClickTracking::SITE_COUNT_KEY, $failure['error']->get_error_data()['counter_key'] );
		$this->assertSame( $this->popup_id, $failure['popup_id'] );
		$this->assertSame( $args['eventData'], $failure['context'] );
		$this->assertFalse( $success_fired );
		$this->assertSame( 1, $this->service->get_popup_count( $this->popup_id ) );
		$this->assertSame( 0, $this->service->get_site_count() );
	}

	/**
	 * Test track_link_click increments multiple times.
	 */
	public function test_track_link_click_multiple_increments() {
		$args = [
			'eventData' => [
				'type' => 'link_click',
				'url'  => 'https://example.com',
			],
		];

		$this->service->track_link_click( $this->popup_id, $args );
		$this->service->track_link_click( $this->popup_id, $args );

		$this->assertEquals( 2, $this->service->get_site_count(), 'Site count should be 2 after two clicks.' );
	}

	/**
	 * Test steady-state counter increments avoid existence queries.
	 */
	public function test_existing_counters_use_four_queries() {
		global $wpdb;

		add_option( LinkClickTracking::SITE_COUNT_KEY, 0, '', false );
		delete_post_meta( $this->popup_id, LinkClickTracking::POPUP_META_KEY );
		add_post_meta( $this->popup_id, LinkClickTracking::POPUP_META_KEY, 0, true );
		wp_cache_delete( LinkClickTracking::SITE_COUNT_KEY, 'options' );
		wp_cache_delete( $this->popup_id, 'post_meta' );

		$site_method  = new ReflectionMethod( $this->service, 'increment_site_count' );
		$popup_method = new ReflectionMethod( $this->service, 'increment_popup_count' );
		if ( PHP_VERSION_ID < 80100 ) {
			$site_method->setAccessible( true );
			$popup_method->setAccessible( true );
		}
		$query_count = $wpdb->num_queries;

		$this->assertSame( 1, $site_method->invoke( $this->service ) );
		$this->assertSame( 1, $popup_method->invoke( $this->service, $this->popup_id ) );
		$this->assertSame( 4, $wpdb->num_queries - $query_count );
	}

	/**
	 * Test track_link_click skips form_submission events.
	 */
	public function test_track_link_click_skips_form_submission() {
		$args = [
			'eventData' => [
				'type'         => 'form_submission',
				'formProvider' => 'cf7',
			],
		];

		$this->service->track_link_click( $this->popup_id, $args );

		$this->assertEquals( 0, $this->service->get_site_count(), 'Should not track form_submission events.' );
	}

	/**
	 * Test track_link_click skips empty event data.
	 */
	public function test_track_link_click_skips_empty_event_data() {
		$this->service->track_link_click( $this->popup_id, [ 'eventData' => [] ] );

		$this->assertEquals( 0, $this->service->get_site_count(), 'Should not track with empty eventData.' );
	}

	/**
	 * Test track_link_click skips missing eventData key.
	 */
	public function test_track_link_click_skips_missing_event_data() {
		$this->service->track_link_click( $this->popup_id, [] );

		$this->assertEquals( 0, $this->service->get_site_count(), 'Should not track with missing eventData.' );
	}

	/**
	 * Test track_link_click skips non-array args.
	 */
	public function test_track_link_click_skips_non_array_args() {
		$this->service->track_link_click( $this->popup_id, 'invalid' );

		$this->assertEquals( 0, $this->service->get_site_count(), 'Should not track with non-array args.' );
	}

	/**
	 * Test track_link_click skips zero popup ID.
	 */
	public function test_track_link_click_skips_zero_popup_id() {
		$args = [
			'eventData' => [
				'type' => 'link_click',
			],
		];

		$this->service->track_link_click( 0, $args );

		$this->assertEquals( 0, $this->service->get_site_count(), 'Should not track with zero popup ID.' );
	}

	/**
	 * Test track_link_click skips non-existent popup.
	 */
	public function test_track_link_click_skips_nonexistent_popup() {
		$args = [
			'eventData' => [
				'type' => 'link_click',
			],
		];

		$this->service->track_link_click( 999999, $args );

		$this->assertEquals( 0, $this->service->get_site_count(), 'Should not track for non-existent popup.' );
	}

	/**
	 * Test track_link_click fires the tracked action.
	 */
	public function test_track_link_click_fires_action() {
		$fired_popup_id   = null;
		$fired_event_data = null;

		add_action(
			'popup_maker/link_click_tracked',
			function ( $popup_id, $event_data ) use ( &$fired_popup_id, &$fired_event_data ) {
				$fired_popup_id   = $popup_id;
				$fired_event_data = $event_data;
			},
			10,
			2
		);

		$args = [
			'eventData' => [
				'type' => 'link_click',
				'url'  => 'https://example.com/page',
			],
		];

		$this->service->track_link_click( $this->popup_id, $args );

		$this->assertEquals( $this->popup_id, $fired_popup_id, 'Action should fire with correct popup ID.' );
		$this->assertEquals( 'link_click', $fired_event_data['type'], 'Event data type should be link_click.' );
	}

	/**
	 * Test reset methods clear counts properly.
	 */
	public function test_reset_counts() {
		$args = [
			'eventData' => [
				'type' => 'link_click',
			],
		];

		$this->service->track_link_click( $this->popup_id, $args );

		$this->service->reset_site_count();
		$this->assertEquals( 0, $this->service->get_site_count(), 'Site count should be 0 after reset.' );

		// Re-track to test popup reset.
		$this->service->track_link_click( $this->popup_id, $args );
		$this->service->reset_popup_count( $this->popup_id );
		$this->assertEquals( 0, $this->service->get_popup_count( $this->popup_id ), 'Popup count should be 0 after reset.' );
	}

	/**
	 * Test get_popup_count returns 0 for popup with no clicks.
	 */
	public function test_get_popup_count_returns_zero_for_new_popup() {
		$new_popup_id = wp_insert_post(
			[
				'post_type'   => 'popup',
				'post_status' => 'publish',
				'post_title'  => 'No Clicks Popup',
			]
		);

		$this->assertEquals( 0, $this->service->get_popup_count( $new_popup_id ), 'New popup should have 0 click count.' );
	}
}
