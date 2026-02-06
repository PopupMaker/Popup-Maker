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
			function ( $popup_id, $args ) use ( &$fired ) {
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
}
