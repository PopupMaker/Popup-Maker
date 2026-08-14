<?php
/**
 * Form Conversion Tracking Service tests.
 *
 * @package Popup_Maker
 */

use PopupMaker\Services\FormConversionTracking;
use PopupMaker\Utils\AnalyticsCounter;

/**
 * Test methods for FormConversionTracking service.
 */
class FormConversionTracking_Test extends WP_UnitTestCase {

	/**
	 * Service instance.
	 *
	 * @var FormConversionTracking
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
				'post_title'  => 'Form Tracking Test Popup',
			]
		);

		// Create a mock container that the Service base class needs.
		$container     = new stdClass();
		$this->service = $this->getMockBuilder( FormConversionTracking::class )
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
			has_action( 'pum_integrated_form_submission', [ $this->service, 'track_form_conversion' ] ),
			'Should register pum_integrated_form_submission hook.'
		);

		$this->assertIsInt(
			has_action( 'pum_analytics_conversion', [ $this->service, 'track_ajax_conversion' ] ),
			'Should register pum_analytics_conversion hook.'
		);
	}

	/**
	 * Test track_form_conversion increments counts.
	 */
	public function test_track_form_conversion_increments_counts() {
		$args = [
			'popup_id'      => $this->popup_id,
			'form_provider' => 'gravity-forms',
			'form_id'       => '5',
		];

		$this->service->track_form_conversion( $args );

		$this->assertEquals( 1, $this->service->get_site_count(), 'Site count should be 1 after one conversion.' );
		$this->assertEquals( 1, $this->service->get_popup_count( $this->popup_id ), 'Popup count should be 1 after one conversion.' );
	}

	/**
	 * First writes invalidate the aggregate negative-option cache.
	 */
	public function test_missing_site_count_invalidates_aggregate_negative_cache() {
		$this->service->reset_site_count();
		$this->assertSame( 0, $this->service->get_site_count() );
		$this->assertIsArray( wp_cache_get( 'notoptions', 'options' ) );

		$this->service->track_form_conversion(
			[
				'popup_id' => $this->popup_id,
			]
		);

		$notoptions = wp_cache_get( 'notoptions', 'options' );

		$this->assertIsArray( $notoptions );
		$this->assertArrayNotHasKey( FormConversionTracking::SITE_COUNT_KEY, $notoptions );
		$this->assertSame( 1, $this->service->get_site_count() );
	}

	/**
	 * Steady writes preserve negative cache entries for unrelated options.
	 */
	public function test_existing_site_count_preserves_unrelated_negative_cache() {
		$missing_key = 'pum_test_missing_option';

		add_option( FormConversionTracking::SITE_COUNT_KEY, 0, '', false );
		$this->assertFalse( get_option( $missing_key ) );

		$notoptions = wp_cache_get( 'notoptions', 'options' );
		$this->assertIsArray( $notoptions );
		$this->assertArrayHasKey( $missing_key, $notoptions );

		$site_method = new ReflectionMethod( $this->service, 'increment_site_count' );
		if ( PHP_VERSION_ID < 80100 ) {
			$site_method->setAccessible( true );
		}

		$this->assertSame( 1, $site_method->invoke( $this->service ) );

		$notoptions = wp_cache_get( 'notoptions', 'options' );
		$this->assertIsArray( $notoptions );
		$this->assertArrayHasKey( $missing_key, $notoptions );
	}

	/**
	 * Existing autoloaded counters are updated without returning stale cache data.
	 */
	public function test_existing_autoloaded_site_count_is_updated_and_made_non_autoloaded() {
		add_option( FormConversionTracking::SITE_COUNT_KEY, 3, '', true );

		$alloptions = wp_load_alloptions();
		$this->assertArrayHasKey( FormConversionTracking::SITE_COUNT_KEY, $alloptions );
		$this->assertSame( '3', (string) $alloptions[ FormConversionTracking::SITE_COUNT_KEY ] );

		$site_method = new ReflectionMethod( $this->service, 'increment_site_count' );
		if ( PHP_VERSION_ID < 80100 ) {
			$site_method->setAccessible( true );
		}

		$this->assertSame( 4, $site_method->invoke( $this->service ) );
		$this->assertSame( 4, $this->service->get_site_count() );
		$this->assertArrayNotHasKey( FormConversionTracking::SITE_COUNT_KEY, wp_load_alloptions( true ) );
	}

	/**
	 * Named-lock queries stay on the writer connection and within this database.
	 */
	public function test_post_meta_lock_queries_use_writer_connection() {
		delete_post_meta( $this->popup_id, FormConversionTracking::POPUP_META_KEY );
		wp_cache_delete( $this->popup_id, 'post_meta' );

		$lock_queries = [];
		$query_filter = static function ( $query ) use ( &$lock_queries ) {
			if ( false !== strpos( $query, 'GET_LOCK' ) || false !== strpos( $query, 'RELEASE_LOCK' ) ) {
				$lock_queries[] = $query;
			}

			return $query;
		};

		add_filter( 'query', $query_filter );

		try {
			$popup_method = new ReflectionMethod( $this->service, 'increment_popup_count' );
			if ( PHP_VERSION_ID < 80100 ) {
				$popup_method->setAccessible( true );
			}

			$this->assertSame( 1, $popup_method->invoke( $this->service, $this->popup_id ) );
		} finally {
			remove_filter( 'query', $query_filter );
		}

		$this->assertNotEmpty( $lock_queries );
		$expected_lock = $this->popup_counter_lock_name( $this->popup_id );
		foreach ( $lock_queries as $lock_query ) {
			$this->assertStringContainsString( 'FOR UPDATE', $lock_query );
			$this->assertStringContainsString( $expected_lock, $lock_query );
		}
	}

	/**
	 * Named locks are released when counter initialization fails.
	 */
	public function test_post_meta_lock_is_released_after_failure() {
		global $wpdb;

		delete_post_meta( $this->popup_id, FormConversionTracking::POPUP_META_KEY );
		wp_cache_delete( $this->popup_id, 'post_meta' );

		$lock_acquired = false;
		$query_filter  = static function ( $query ) use ( &$lock_acquired ) {
			if ( false !== strpos( $query, 'GET_LOCK' ) ) {
				$lock_acquired = true;
			} elseif ( $lock_acquired && false !== strpos( $query, 'UPDATE ' ) ) {
				throw new RuntimeException( 'Simulated counter update failure.' );
			}

			return $query;
		};

		add_filter( 'query', $query_filter );

		try {
			$popup_method = new ReflectionMethod( $this->service, 'increment_popup_count' );
			if ( PHP_VERSION_ID < 80100 ) {
				$popup_method->setAccessible( true );
			}

			$popup_method->invoke( $this->service, $this->popup_id );
			$this->fail( 'Expected the simulated counter failure.' );
		} catch ( RuntimeException $exception ) {
			$this->assertSame( 'Simulated counter update failure.', $exception->getMessage() );
		} finally {
			remove_filter( 'query', $query_filter );
		}

		$lock_name = $this->popup_counter_lock_name( $this->popup_id );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- This assertion inspects the named lock on the current test connection.
		$is_free = $wpdb->get_var( $wpdb->prepare( 'SELECT IS_FREE_LOCK(%s)', $lock_name ) );

		$this->assertSame( '1', (string) $is_free );
	}

	/**
	 * A real contending connection can outlive the old two-attempt window.
	 */
	public function test_post_meta_increment_waits_for_real_contending_connection() {
		global $wpdb;

		delete_post_meta( $this->popup_id, FormConversionTracking::POPUP_META_KEY );
		wp_cache_delete( $this->popup_id, 'post_meta' );

		if ( ! defined( 'MYSQLI_ASYNC' ) || ! function_exists( 'mysqli_reap_async_query' ) ) {
			$this->markTestSkipped( 'The mysqli asynchronous-query API is unavailable.' );
		}

		$lock_name   = $this->popup_counter_lock_name( $this->popup_id );
		$lock_holder = new wpdb( DB_USER, DB_PASSWORD, DB_NAME, DB_HOST );
		$acquired    = $lock_holder->get_var( $lock_holder->prepare( 'SELECT GET_LOCK(%s, 0)', $lock_name ) );

		if ( '1' !== (string) $acquired ) {
			$this->markTestSkipped( 'A second database connection could not acquire a named lock.' );
		}

		$dbh_property = new ReflectionProperty( $lock_holder, 'dbh' );
		if ( PHP_VERSION_ID < 80100 ) {
			$dbh_property->setAccessible( true );
		}
		$dbh           = $dbh_property->getValue( $lock_holder );
		$release_query = $lock_holder->prepare( 'SELECT SLEEP(2.2), RELEASE_LOCK(%s)', $lock_name );
		// phpcs:ignore WordPress.DB.RestrictedFunctions.mysql_mysqli_query -- Async execution lets the second test connection release its held lock while the primary connection waits.
		$async_query = mysqli_query( $dbh, $release_query, MYSQLI_ASYNC );

		if ( false === $async_query ) {
			$lock_holder->get_var( $lock_holder->prepare( 'SELECT RELEASE_LOCK(%s)', $lock_name ) );
			$this->markTestSkipped( 'The second database connection could not schedule an asynchronous lock release.' );
		}

		try {
			$result = AnalyticsCounter::increment_post_meta( $this->popup_id, FormConversionTracking::POPUP_META_KEY );
		} finally {
			// phpcs:ignore WordPress.DB.RestrictedFunctions.mysql_mysqli_reap_async_query -- Reap the isolated second connection's test-only asynchronous query.
			mysqli_reap_async_query( $dbh );
		}

		$this->assertSame( 1, $result );
		$this->assertSame( 1, $this->service->get_popup_count( $this->popup_id ) );
		$this->assertSame(
			'1',
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Verify the isolated concurrency fixture created exactly one row.
			(string) $wpdb->get_var(
				$wpdb->prepare(
					'SELECT COUNT(*) FROM %i WHERE post_id = %d AND meta_key = %s',
					$wpdb->postmeta,
					$this->popup_id,
					FormConversionTracking::POPUP_META_KEY
				)
			)
		);
	}

	/**
	 * Exhausted contention is surfaced and does not partially increment totals.
	 */
	public function test_post_meta_lock_timeout_is_reported_to_tracking_consumers() {
		delete_post_meta( $this->popup_id, FormConversionTracking::POPUP_META_KEY );
		wp_cache_delete( $this->popup_id, 'post_meta' );

		$lock_attempts  = 0;
		$query_filter   = static function ( $query ) use ( &$lock_attempts ) {
			if ( false !== strpos( $query, 'GET_LOCK' ) ) {
				++$lock_attempts;
				return 'SELECT 0';
			}

			return $query;
		};
		$reported_error = null;
		$error_action   = static function ( $error ) use ( &$reported_error ) {
			$reported_error = $error;
		};

		add_filter( 'query', $query_filter );
		add_action( 'popup_maker/analytics_counter_failed', $error_action );

		try {
			$this->service->track_form_conversion( [ 'popup_id' => $this->popup_id ] );
		} finally {
			remove_filter( 'query', $query_filter );
			remove_action( 'popup_maker/analytics_counter_failed', $error_action );
		}

		$this->assertWPError( $reported_error );
		$this->assertSame( 'pum_analytics_counter_lock_timeout', $reported_error->get_error_code() );
		$this->assertSame( 3, $lock_attempts );
		$this->assertSame( 0, $this->service->get_popup_count( $this->popup_id ) );
		$this->assertSame( 0, $this->service->get_site_count() );
	}

	/**
	 * A failed site-counter upsert is reported and suppresses the success action.
	 */
	public function test_site_counter_database_failure_is_reported_without_success_action() {
		global $wpdb;

		$args = [
			'popup_id'      => $this->popup_id,
			'form_provider' => 'wpforms',
		];

		add_post_meta( $this->popup_id, FormConversionTracking::POPUP_META_KEY, 0, true );

		$query_filter             = static function ( $query ) {
			if ( false !== strpos( $query, 'INSERT INTO' ) && false !== strpos( $query, FormConversionTracking::SITE_COUNT_KEY ) ) {
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
		add_action( 'popup_maker/form_conversion_tracked', $success_action );

		try {
			$this->service->track_form_conversion( $args );
		} finally {
			remove_filter( 'query', $query_filter );
			remove_action( 'popup_maker/analytics_counter_failed', $error_action );
			remove_action( 'popup_maker/form_conversion_tracked', $success_action );
			$wpdb->suppress_errors( $previous_suppress_errors );
		}

		$this->assertIsArray( $failure );
		$this->assertWPError( $failure['error'] );
		$this->assertSame( 'pum_analytics_counter_database_error', $failure['error']->get_error_code() );
		$error_data = $failure['error']->get_error_data();
		$this->assertSame( FormConversionTracking::SITE_COUNT_KEY, $error_data['counter_key'] );
		$this->assertFalse( $error_data['retryable'] );
		$this->assertArrayNotHasKey( 'post_id', $error_data );
		$this->assertSame( $this->popup_id, $failure['popup_id'] );
		$this->assertSame( $args, $failure['context'] );
		$this->assertFalse( $success_fired );
		$this->assertSame( 1, $this->service->get_popup_count( $this->popup_id ) );
		$this->assertSame( 0, $this->service->get_site_count() );
	}

	/**
	 * Test track_form_conversion increments multiple times.
	 */
	public function test_track_form_conversion_increments_multiple() {
		$args = [
			'popup_id'      => $this->popup_id,
			'form_provider' => 'cf7',
		];

		$this->service->track_form_conversion( $args );
		$this->service->track_form_conversion( $args );
		$this->service->track_form_conversion( $args );

		$this->assertEquals( 3, $this->service->get_site_count(), 'Site count should be 3 after three conversions.' );
		$this->assertEquals( 3, $this->service->get_popup_count( $this->popup_id ), 'Popup count should be 3.' );
	}

	/**
	 * Test steady-state counter increments avoid existence queries.
	 */
	public function test_existing_counters_use_four_queries() {
		global $wpdb;

		add_option( FormConversionTracking::SITE_COUNT_KEY, 0, '', false );
		delete_post_meta( $this->popup_id, FormConversionTracking::POPUP_META_KEY );
		add_post_meta( $this->popup_id, FormConversionTracking::POPUP_META_KEY, 0, true );
		wp_cache_delete( FormConversionTracking::SITE_COUNT_KEY, 'options' );
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
	 * Test track_form_conversion skips when already tracked.
	 */
	public function test_track_form_conversion_skips_already_tracked() {
		$args = [
			'popup_id' => $this->popup_id,
			'tracked'  => true,
		];

		$this->service->track_form_conversion( $args );

		$this->assertEquals( 0, $this->service->get_site_count(), 'Should not track when already tracked.' );
	}

	/**
	 * Test track_form_conversion skips with non-array input.
	 */
	public function test_track_form_conversion_skips_non_array() {
		$this->service->track_form_conversion( 'not-an-array' );

		$this->assertEquals( 0, $this->service->get_site_count(), 'Should not track with non-array input.' );
	}

	/**
	 * Test track_form_conversion skips with missing popup_id.
	 */
	public function test_track_form_conversion_skips_missing_popup_id() {
		$this->service->track_form_conversion( [ 'form_provider' => 'cf7' ] );

		$this->assertEquals( 0, $this->service->get_site_count(), 'Should not track without popup_id.' );
	}

	/**
	 * Test track_form_conversion skips with invalid popup ID.
	 */
	public function test_track_form_conversion_skips_nonexistent_popup() {
		$args = [
			'popup_id' => 999999,
		];

		$this->service->track_form_conversion( $args );

		$this->assertEquals( 0, $this->service->get_site_count(), 'Should not track for non-existent popup.' );
	}

	/**
	 * Test track_form_conversion fires the tracked action.
	 */
	public function test_track_form_conversion_fires_action() {
		$fired_popup_id = null;

		add_action(
			'popup_maker/form_conversion_tracked',
			function ( $popup_id ) use ( &$fired_popup_id ) {
				$fired_popup_id = $popup_id;
			}
		);

		$this->service->track_form_conversion(
			[
				'popup_id'      => $this->popup_id,
				'form_provider' => 'wpforms',
			]
		);

		$this->assertEquals( $this->popup_id, $fired_popup_id, 'Action should fire with correct popup ID.' );
	}

	/**
	 * Test track_ajax_conversion with valid form submission event data.
	 */
	public function test_track_ajax_conversion_valid_form_submission() {
		$args = [
			'eventData' => [
				'type'         => 'form_submission',
				'formProvider' => 'ninja-forms',
			],
		];

		$this->service->track_ajax_conversion( $this->popup_id, $args );

		$this->assertEquals( 1, $this->service->get_site_count(), 'Should track valid AJAX form submission.' );
		$this->assertEquals( 1, $this->service->get_popup_count( $this->popup_id ), 'Popup count should be 1.' );
	}

	/**
	 * Test track_ajax_conversion skips non-form-submission events.
	 */
	public function test_track_ajax_conversion_skips_link_click_type() {
		$args = [
			'eventData' => [
				'type' => 'link_click',
				'url'  => 'https://example.com',
			],
		];

		$this->service->track_ajax_conversion( $this->popup_id, $args );

		$this->assertEquals( 0, $this->service->get_site_count(), 'Should not track link_click events.' );
	}

	/**
	 * Test track_ajax_conversion skips empty event data.
	 */
	public function test_track_ajax_conversion_skips_empty_event_data() {
		$this->service->track_ajax_conversion( $this->popup_id, [ 'eventData' => [] ] );

		$this->assertEquals( 0, $this->service->get_site_count(), 'Should not track with empty eventData.' );
	}

	/**
	 * Test track_ajax_conversion skips non-array args.
	 */
	public function test_track_ajax_conversion_skips_non_array_args() {
		$this->service->track_ajax_conversion( $this->popup_id, 'string-arg' );

		$this->assertEquals( 0, $this->service->get_site_count(), 'Should not track with non-array args.' );
	}

	/**
	 * Test track_ajax_conversion skips invalid popup ID.
	 */
	public function test_track_ajax_conversion_skips_zero_popup_id() {
		$args = [
			'eventData' => [
				'type' => 'form_submission',
			],
		];

		$this->service->track_ajax_conversion( 0, $args );

		$this->assertEquals( 0, $this->service->get_site_count(), 'Should not track with zero popup ID.' );
	}

	/**
	 * Test reset_site_count clears the count.
	 */
	public function test_reset_site_count() {
		$this->service->track_form_conversion(
			[
				'popup_id' => $this->popup_id,
			]
		);

		$this->assertGreaterThan( 0, $this->service->get_site_count(), 'Precondition: count should be > 0.' );

		$this->service->reset_site_count();

		$this->assertEquals( 0, $this->service->get_site_count(), 'Site count should be 0 after reset.' );
	}

	/**
	 * Test reset_popup_count clears the popup count.
	 */
	public function test_reset_popup_count() {
		$this->service->track_form_conversion(
			[
				'popup_id' => $this->popup_id,
			]
		);

		$this->assertGreaterThan( 0, $this->service->get_popup_count( $this->popup_id ), 'Precondition: popup count should be > 0.' );

		$this->service->reset_popup_count( $this->popup_id );

		$this->assertEquals( 0, $this->service->get_popup_count( $this->popup_id ), 'Popup count should be 0 after reset.' );
	}

	/**
	 * Build the expected named lock for a popup counter.
	 *
	 * @param int $popup_id Popup ID.
	 * @return string
	 */
	private function popup_counter_lock_name( $popup_id ) {
		global $wpdb;

		return 'pum_counter_' . md5( DB_NAME . ':' . $wpdb->postmeta . ':' . FormConversionTracking::POPUP_META_KEY . ':' . $popup_id );
	}
}
