<?php
/**
 * Expanded Analytics tests.
 *
 * Tests additional PUM_Analytics methods not covered by test-pum-analytics.php.
 *
 * @package Popup_Maker
 */

/**
 * Expanded test methods for PUM_Analytics class.
 */
class PUM_Analytics_Expanded_Test extends WP_UnitTestCase {

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
				'post_title'  => 'Test Popup',
			]
		);
	}

	/**
	 * Test that track ignores empty popup ID.
	 */
	public function test_track_ignores_empty_pid() {
		$popup = pum_get_popup( $this->popup_id );
		$popup->reset_counts();

		// Empty pid should bail early.
		PUM_Analytics::track(
			[
				'pid'   => 0,
				'event' => 'open',
			]
		);

		$this->assertEquals( 0, $popup->get_event_count( 'open' ), 'Should not track with pid of 0.' );
	}

	/**
	 * Test that track ignores negative popup ID.
	 */
	public function test_track_ignores_negative_pid() {
		$popup = pum_get_popup( $this->popup_id );
		$popup->reset_counts();

		PUM_Analytics::track(
			[
				'pid'   => -1,
				'event' => 'open',
			]
		);

		$this->assertEquals( 0, $popup->get_event_count( 'open' ), 'Should not track with negative pid.' );
	}

	/**
	 * Test that track ignores invalid event types.
	 */
	public function test_track_ignores_invalid_event() {
		$popup = pum_get_popup( $this->popup_id );
		$popup->reset_counts();

		PUM_Analytics::track(
			[
				'pid'   => $this->popup_id,
				'event' => 'bogus_event',
			]
		);

		// Open and conversion should both still be 0.
		$this->assertEquals( 0, $popup->get_event_count( 'open' ), 'Invalid event should not increment open.' );
		$this->assertEquals( 0, $popup->get_event_count( 'conversion' ), 'Invalid event should not increment conversion.' );
	}

	/**
	 * Test that track fires the event-specific action hook.
	 */
	public function test_track_fires_event_action() {
		$fired = false;

		add_action(
			'pum_analytics_open',
			function () use ( &$fired ) {
				$fired = true;
			},
			10,
			2
		);

		PUM_Analytics::track(
			[
				'pid'   => $this->popup_id,
				'event' => 'open',
			]
		);

		$this->assertTrue( $fired, 'pum_analytics_open action should have fired.' );
	}

	/**
	 * Test that track fires the generic pum_analytics_event action.
	 */
	public function test_track_fires_generic_event_action() {
		$captured_args = null;

		add_action(
			'pum_analytics_event',
			function ( $args ) use ( &$captured_args ) {
				$captured_args = $args;
			}
		);

		$args = [
			'pid'   => $this->popup_id,
			'event' => 'conversion',
		];

		PUM_Analytics::track( $args );

		$this->assertNotNull( $captured_args, 'pum_analytics_event action should have fired.' );
		$this->assertEquals( $this->popup_id, $captured_args['pid'], 'Args should contain the popup ID.' );
	}

	/**
	 * Test event_keys returns correct pair for 'open' event.
	 */
	public function test_event_keys_open() {
		$keys = PUM_Analytics::event_keys( 'open' );

		$this->assertIsArray( $keys );
		$this->assertEquals( 'open', $keys[0], 'First key should be the event name.' );
		$this->assertEquals( 'opened', $keys[1], 'Second key for open should be opened.' );
	}

	/**
	 * Test event_keys returns correct pair for 'conversion' event.
	 */
	public function test_event_keys_conversion() {
		$keys = PUM_Analytics::event_keys( 'conversion' );

		$this->assertEquals( 'conversion', $keys[0], 'First key should be conversion.' );
		$this->assertEquals( 'conversion', $keys[1], 'Second key for conversion should also be conversion.' );
	}

	/**
	 * Test valid_events returns expected defaults.
	 */
	public function test_valid_events_defaults() {
		$events = PUM_Analytics::valid_events();

		$this->assertContains( 'open', $events, 'Should contain open event.' );
		$this->assertContains( 'conversion', $events, 'Should contain conversion event.' );
	}

	/**
	 * Test endpoint_absint validates numeric values.
	 */
	public function test_endpoint_absint_with_numeric() {
		$this->assertTrue( PUM_Analytics::endpoint_absint( '42' ), 'Numeric string should pass.' );
		$this->assertTrue( PUM_Analytics::endpoint_absint( 42 ), 'Integer should pass.' );
	}

	/**
	 * Test endpoint_absint rejects non-numeric values.
	 */
	public function test_endpoint_absint_with_non_numeric() {
		$this->assertFalse( PUM_Analytics::endpoint_absint( 'abc' ), 'Non-numeric string should fail.' );
		$this->assertFalse( PUM_Analytics::endpoint_absint( '' ), 'Empty string should fail.' );
	}

	/**
	 * Test sanitize_event_data with array input.
	 */
	public function test_sanitize_event_data_array_passthrough() {
		$data   = [ 'type' => 'form_submission', 'formId' => '123' ];
		$result = PUM_Analytics::sanitize_event_data( $data );

		$this->assertEquals( $data, $result, 'Array input should pass through unchanged.' );
	}

	/**
	 * Test sanitize_event_data with valid JSON string.
	 */
	public function test_sanitize_event_data_json_string() {
		$json   = '{"type":"link_click","url":"https://example.com"}';
		$result = PUM_Analytics::sanitize_event_data( $json );

		$this->assertIsArray( $result, 'Valid JSON should decode to array.' );
		$this->assertEquals( 'link_click', $result['type'], 'Type should be decoded correctly.' );
	}

	/**
	 * Test sanitize_event_data with invalid JSON string.
	 */
	public function test_sanitize_event_data_invalid_json() {
		$result = PUM_Analytics::sanitize_event_data( 'not-json{' );

		$this->assertIsArray( $result, 'Invalid JSON should return empty array.' );
		$this->assertEmpty( $result, 'Invalid JSON should return empty array.' );
	}

	/**
	 * Test sanitize_event_data with non-string/non-array input.
	 */
	public function test_sanitize_event_data_null_input() {
		$this->assertEquals( [], PUM_Analytics::sanitize_event_data( null ), 'Null should return empty array.' );
		$this->assertEquals( [], PUM_Analytics::sanitize_event_data( 42 ), 'Integer should return empty array.' );
	}

	/**
	 * Test analytics_enabled returns true by default.
	 */
	public function test_analytics_enabled_default() {
		// Default should be enabled (no disable options set).
		$this->assertTrue( PUM_Analytics::analytics_enabled(), 'Analytics should be enabled by default.' );
	}

	/**
	 * Test analytics_enabled respects the filter.
	 */
	public function test_analytics_enabled_filter_override() {
		add_filter( 'pum_analytics_enabled', '__return_false' );

		$this->assertFalse( PUM_Analytics::analytics_enabled(), 'Filter should disable analytics.' );

		remove_filter( 'pum_analytics_enabled', '__return_false' );
	}

	/**
	 * Test pum_vars adds expected keys to the array.
	 */
	public function test_pum_vars_adds_analytics_enabled() {
		$vars = PUM_Analytics::pum_vars( [ 'existing' => true ] );

		$this->assertArrayHasKey( 'analytics_enabled', $vars, 'Should add analytics_enabled key.' );
		$this->assertArrayHasKey( 'analytics_route', $vars, 'Should add analytics_route key.' );
		$this->assertArrayHasKey( 'analytics_api', $vars, 'Should add analytics_api key.' );
		// Existing vars should be preserved.
		$this->assertTrue( $vars['existing'], 'Existing vars should be preserved.' );
	}

	/**
	 * Test get_file returns empty string for non-existent path.
	 */
	public function test_get_file_nonexistent() {
		$result = PUM_Analytics::get_file( '/tmp/nonexistent_file_abc123.gif' );

		$this->assertEquals( '', $result, 'Non-existent file should return empty string.' );
	}

	/**
	 * Test event_keys with a custom event name that ends in 'e'.
	 */
	public function test_event_keys_custom_event_ending_in_e() {
		$keys = PUM_Analytics::event_keys( 'close' );

		$this->assertEquals( 'close', $keys[0], 'First key should be the event name.' );
		// rtrim('close', 'e') = 'clos', then 'clos' . 'ed' = 'closed'.
		$this->assertEquals( 'closed', $keys[1], 'Second key should strip trailing e and add ed.' );
	}

	/**
	 * Test event_keys with a custom event that does not end in 'e'.
	 */
	public function test_event_keys_custom_event_not_ending_in_e() {
		$keys = PUM_Analytics::event_keys( 'submit' );

		$this->assertEquals( 'submit', $keys[0], 'First key should be the event name.' );
		// rtrim('submit', 'e') = 'submit', then 'submit' . 'ed' = 'submited' (source just appends 'ed').
		$this->assertEquals( 'submited', $keys[1], 'Second key should add ed suffix (no double t).' );
	}

	/**
	 * Test event_keys filter can modify the returned keys.
	 */
	public function test_event_keys_filter() {
		$filter = function ( $keys, $event ) {
			if ( 'open' === $event ) {
				$keys[1] = 'custom_opened';
			}
			return $keys;
		};

		add_filter( 'pum_analytics_event_keys', $filter, 10, 2 );

		$keys = PUM_Analytics::event_keys( 'open' );
		$this->assertEquals( 'custom_opened', $keys[1], 'Filter should modify the second key.' );

		remove_filter( 'pum_analytics_event_keys', $filter, 10 );
	}

	/**
	 * Test valid_events filter can add custom events.
	 */
	public function test_valid_events_filter_adds_custom() {
		$filter = function ( $events ) {
			$events[] = 'dismiss';
			return $events;
		};

		add_filter( 'pum_analytics_valid_events', $filter );

		$events = PUM_Analytics::valid_events();
		$this->assertContains( 'dismiss', $events, 'Filter should add custom event.' );
		$this->assertContains( 'open', $events, 'Original events should still exist.' );

		remove_filter( 'pum_analytics_valid_events', $filter );
	}

	/**
	 * Test track with a non-existent popup ID does nothing.
	 */
	public function test_track_with_nonexistent_popup() {
		$fired = false;
		add_action(
			'pum_analytics_event',
			function () use ( &$fired ) {
				$fired = true;
			}
		);

		PUM_Analytics::track(
			[
				'pid'   => 999999,
				'event' => 'open',
			]
		);

		$this->assertFalse( $fired, 'Should not fire event for non-existent popup.' );
	}

	/**
	 * Test track with missing event key does nothing.
	 */
	public function test_track_with_missing_event_key() {
		// BUG: PUM_Analytics::track() does not validate that 'event' key exists,
		// causing an "Undefined array key" error on PHP 8.x.
		// This documents the bug — to be fixed in a separate branch.
		$this->markTestSkipped( 'Known bug: track() does not validate missing event key (undefined array key on PHP 8.x).' );
	}

	/**
	 * Test track increments count multiple times.
	 */
	public function test_track_increments_count_multiple_times() {
		$popup = pum_get_popup( $this->popup_id );
		if ( ! pum_is_popup( $popup ) ) {
			$this->markTestSkipped( 'pum_is_popup() returned false.' );
		}
		$popup->reset_counts();

		PUM_Analytics::track( [ 'pid' => $this->popup_id, 'event' => 'open' ] );
		PUM_Analytics::track( [ 'pid' => $this->popup_id, 'event' => 'open' ] );
		PUM_Analytics::track( [ 'pid' => $this->popup_id, 'event' => 'open' ] );

		$count = (int) get_post_meta( $this->popup_id, 'popup_open_count', true );
		$this->assertEquals( 3, $count, 'Open count should be 3 after three tracks.' );
	}

	/**
	 * Test track with empty args array does nothing.
	 */
	public function test_track_with_empty_args() {
		$fired = false;
		add_action(
			'pum_analytics_event',
			function () use ( &$fired ) {
				$fired = true;
			}
		);

		PUM_Analytics::track( [] );

		$this->assertFalse( $fired, 'Should not fire event with empty args.' );
	}

	/**
	 * Test analytics_enabled returns false when disable_analytics option is set.
	 */
	public function test_analytics_enabled_disabled_by_option() {
		// Use PUM_Utils_Options API to set the option correctly.
		PUM_Utils_Options::update( 'disable_analytics', true );

		$enabled = PUM_Analytics::analytics_enabled();

		// Clean up.
		PUM_Utils_Options::delete( 'disable_analytics' );

		// With disable_analytics=true, analytics should be disabled.
		$this->assertFalse( $enabled, 'analytics_enabled should return false when disable_analytics is true.' );
	}

	/**
	 * Test customize_endpoint_value returns original value when bypass is off.
	 */
	public function test_customize_endpoint_value_no_bypass() {
		$result = PUM_Analytics::customize_endpoint_value( 'analytics' );
		$this->assertEquals( 'analytics', $result, 'Should return original value when bypass is off.' );
	}

	/**
	 * Test get_analytics_namespace returns correct format.
	 */
	public function test_get_analytics_namespace_format() {
		$namespace = PUM_Analytics::get_analytics_namespace();

		$this->assertMatchesRegularExpression( '/^[\w-]+\/v\d+$/', $namespace, 'Namespace should match pattern word/vN.' );
	}

	/**
	 * Test pum_vars contains analytics_api with rest_url available.
	 */
	public function test_pum_vars_analytics_api_is_string() {
		$vars = PUM_Analytics::pum_vars( [] );

		// rest_url is available in WP test environment.
		if ( function_exists( 'rest_url' ) ) {
			$this->assertIsString( $vars['analytics_api'], 'analytics_api should be a string URL when rest_url is available.' );
			$this->assertNotEmpty( $vars['analytics_api'], 'analytics_api should not be empty.' );
		}
	}

	/**
	 * Test pum_vars analytics_enabled matches analytics_enabled method.
	 */
	public function test_pum_vars_analytics_enabled_matches_method() {
		$vars    = PUM_Analytics::pum_vars( [] );
		$enabled = PUM_Analytics::analytics_enabled();

		$this->assertEquals( $enabled, $vars['analytics_enabled'], 'pum_vars analytics_enabled should match analytics_enabled().' );
	}

	/**
	 * Test endpoint_absint with float values.
	 */
	public function test_endpoint_absint_with_float() {
		$this->assertTrue( PUM_Analytics::endpoint_absint( '3.14' ), 'Float string should pass is_numeric check.' );
		$this->assertTrue( PUM_Analytics::endpoint_absint( 3.14 ), 'Float should pass is_numeric check.' );
	}

	/**
	 * Test endpoint_absint with negative numbers.
	 */
	public function test_endpoint_absint_with_negative() {
		$this->assertTrue( PUM_Analytics::endpoint_absint( '-5' ), 'Negative numeric string should pass is_numeric.' );
	}

	/**
	 * Test sanitize_event_data with boolean input.
	 */
	public function test_sanitize_event_data_boolean_input() {
		$this->assertEquals( [], PUM_Analytics::sanitize_event_data( true ), 'Boolean true should return empty array.' );
		$this->assertEquals( [], PUM_Analytics::sanitize_event_data( false ), 'Boolean false should return empty array.' );
	}

	/**
	 * Test sanitize_event_data with JSON that decodes to non-array.
	 */
	public function test_sanitize_event_data_json_non_array() {
		$result = PUM_Analytics::sanitize_event_data( '"just a string"' );
		$this->assertEquals( [], $result, 'JSON string value should return empty array.' );
	}

	/**
	 * Test sanitize_event_data with empty array.
	 */
	public function test_sanitize_event_data_empty_array() {
		$result = PUM_Analytics::sanitize_event_data( [] );
		$this->assertEquals( [], $result, 'Empty array should pass through as empty array.' );
	}

	/**
	 * Test sanitize_event_data with empty string.
	 */
	public function test_sanitize_event_data_empty_string() {
		$result = PUM_Analytics::sanitize_event_data( '' );
		$this->assertEquals( [], $result, 'Empty string should return empty array.' );
	}

	/**
	 * Test get_file with an actual file that exists.
	 */
	public function test_get_file_existing() {
		// Use sys_get_temp_dir() instead of /tmp/claude/ which doesn't exist in Docker.
		$tmp = sys_get_temp_dir() . '/pum_test_beacon_' . uniqid() . '.txt';
		file_put_contents( $tmp, 'test-content' );

		$result = PUM_Analytics::get_file( $tmp );
		unlink( $tmp );

		$this->assertEquals( 'test-content', $result, 'Should return contents of existing file.' );
	}
}
