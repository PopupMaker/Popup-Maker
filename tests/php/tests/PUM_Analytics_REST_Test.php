<?php
/**
 * Analytics REST endpoint tests.
 *
 * @package Popup_Maker
 */

/**
 * Test analytics requests through WordPress REST validation and dispatch.
 */
class PUM_Analytics_REST_Test extends WP_UnitTestCase {

	/**
	 * Popup ID for test events.
	 *
	 * @var int
	 */
	private $popup_id;

	/**
	 * Original remote address.
	 *
	 * @var string|null
	 */
	private $remote_addr;

	/**
	 * Set up the REST server and popup fixture.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->remote_addr      = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : null;
		$_SERVER['REMOTE_ADDR'] = '192.0.2.10';
		$this->popup_id         = self::factory()->post->create(
			[
				'post_type'   => 'popup',
				'post_status' => 'publish',
				'post_title'  => 'Analytics REST Test Popup',
			]
		);

		$this->reset_rest_server();
	}

	/**
	 * Reset globals changed by the test.
	 */
	public function tearDown(): void {
		global $wp_rest_server;

		$wp_rest_server = null;

		if ( null === $this->remote_addr ) {
			unset( $_SERVER['REMOTE_ADDR'] );
		} else {
			$_SERVER['REMOTE_ADDR'] = $this->remote_addr;
		}

		PUM_Utils_Options::delete( 'bypass_adblockers' );
		PUM_Utils_Options::delete( 'adblock_bypass_url_method' );
		PUM_Utils_Options::delete( 'adblock_bypass_custom_filename' );

		parent::tearDown();
	}

	/**
	 * A flat event passes route validation and tracks once.
	 */
	public function test_flat_single_event_returns_204_and_tracks_once() {
		$event_args     = [];
		$specific_hooks = 0;
		$generic_hook   = function ( $args ) use ( &$event_args ) {
			$event_args[] = $args;
		};
		$open_hook      = function () use ( &$specific_hooks ) {
			++$specific_hooks;
		};

		add_action( 'pum_analytics_event', $generic_hook );
		add_action( 'pum_analytics_open', $open_hook, 10, 2 );

		$response = $this->dispatch_form_request(
			[
				'event'     => 'open',
				'pid'       => $this->popup_id,
				'eventData' => '{"source":"flat"}',
			]
		);

		remove_action( 'pum_analytics_event', $generic_hook );
		remove_action( 'pum_analytics_open', $open_hook, 10 );

		$this->assert_no_content( $response );
		$this->assert_event_counts( 1, 0 );
		$this->assertSame( 1, $specific_hooks );
		$this->assertCount( 1, $event_args );
		$this->assertSame( [ 'source' => 'flat' ], $event_args[0]['eventData'] );
	}

	/**
	 * A FormData-compatible JSON string batch tracks every event once.
	 */
	public function test_json_string_batch_returns_204_and_tracks_each_event_once() {
		$tracked_events = [];
		$generic_hook   = function ( $args ) use ( &$tracked_events ) {
			$tracked_events[] = $args;
		};

		add_action( 'pum_analytics_event', $generic_hook );

		$response = $this->dispatch_form_request(
			[
				'events' => wp_json_encode(
					[
						[
							'event' => 'open',
							'pid'   => $this->popup_id,
						],
						[
							'event'     => 'conversion',
							'pid'       => $this->popup_id,
							'eventData' => '{"type":"form_submission"}',
						],
					]
				),
			]
		);

		remove_action( 'pum_analytics_event', $generic_hook );

		$this->assert_no_content( $response );
		$this->assert_event_counts( 1, 1 );
		$this->assertSame( [ 'open', 'conversion' ], wp_list_pluck( $tracked_events, 'event' ) );
		$this->assertSame( [ 'type' => 'form_submission' ], $tracked_events[1]['eventData'] );
	}

	/**
	 * A JSON REST client can send an already-decoded events array.
	 */
	public function test_decoded_array_batch_returns_204_and_tracks_each_event_once() {
		$response = $this->dispatch_json_request(
			[
				'events' => [
					[
						'event' => 'open',
						'pid'   => $this->popup_id,
					],
					[
						'event'     => 'conversion',
						'pid'       => $this->popup_id,
						'eventData' => [ 'source' => 'json' ],
					],
				],
			]
		);

		$this->assert_no_content( $response );
		$this->assert_event_counts( 1, 1 );
	}

	/**
	 * Malformed JSON is rejected without tracking.
	 */
	public function test_malformed_json_returns_400_without_tracking() {
		$response = $this->dispatch_form_request( [ 'events' => '[{"event":"open"' ] );

		$this->assert_error_response( $response, 'invalid_events' );
		$this->assert_event_counts( 0, 0 );
	}

	/**
	 * Empty and non-list batches are rejected without tracking.
	 *
	 * @dataProvider invalid_batch_payload_provider
	 *
	 * @param mixed $events Events value.
	 */
	public function test_invalid_batch_payload_returns_400_without_tracking( $events ) {
		$response = $this->dispatch_form_request( [ 'events' => $events ] );

		$this->assert_error_response( $response, 'invalid_events' );
		$this->assert_event_counts( 0, 0 );
	}

	/**
	 * Supply empty and non-list batch encodings.
	 *
	 * @return array<string,array<int,mixed>>
	 */
	public function invalid_batch_payload_provider() {
		return [
			'empty JSON array'          => [ '[]' ],
			'empty array'               => [ [] ],
			'empty string'              => [ '' ],
			'JSON object'               => [ '{"event":"open","pid":1}' ],
			'numeric-keyed JSON object' => [ '{"0":{"event":"open","pid":1}}' ],
			'JSON scalar'               => [ '42' ],
			'decoded associative array' => [
				[
					'event' => 'open',
					'pid'   => 1,
				],
			],
		];
	}

	/**
	 * Missing flat parameters are rejected by endpoint validation.
	 *
	 * @dataProvider missing_params_provider
	 *
	 * @param array $params Request parameters.
	 */
	public function test_missing_event_or_pid_returns_400_without_tracking( $params ) {
		$response = $this->dispatch_form_request( $params );

		$this->assert_error_response( $response, 'missing_params' );
		$this->assert_event_counts( 0, 0 );
	}

	/**
	 * Supply incomplete flat payloads.
	 *
	 * @return array<string,array<int,array<string,mixed>>>
	 */
	public function missing_params_provider() {
		return [
			'empty request' => [ [] ],
			'missing event' => [ [ 'pid' => 1 ] ],
			'missing pid'   => [ [ 'event' => 'open' ] ],
		];
	}

	/**
	 * Invalid flat event and popup values are rejected without tracking.
	 */
	public function test_invalid_flat_event_and_popup_return_400_without_tracking() {
		$invalid_event = $this->dispatch_form_request(
			[
				'event' => 'unknown',
				'pid'   => $this->popup_id,
			]
		);
		$invalid_popup = $this->dispatch_form_request(
			[
				'event' => 'open',
				'pid'   => 999999,
			]
		);

		$this->assert_error_response( $invalid_event, 'invalid_params' );
		$this->assert_error_response( $invalid_popup, 'invalid_params' );
		$this->assert_event_counts( 0, 0 );
	}

	/**
	 * Flat popup IDs are validated before the route sanitizes them.
	 */
	public function test_flat_negative_popup_id_fails_route_validation_without_tracking() {
		$response = $this->dispatch_form_request(
			[
				'event' => 'open',
				'pid'   => -$this->popup_id,
			]
		);

		$this->assert_error_response( $response, 'rest_invalid_param' );
		$this->assert_event_counts( 0, 0 );
	}

	/**
	 * One invalid batch entry rejects the whole batch before hooks or counters.
	 *
	 * @dataProvider invalid_batch_event_provider
	 *
	 * @param string $invalid_part Invalid event part.
	 */
	public function test_invalid_batch_does_not_partially_track( $invalid_part ) {
		$hook_count    = 0;
		$hook          = function () use ( &$hook_count ) {
			++$hook_count;
		};
		$invalid_event = [
			'event' => 'conversion',
			'pid'   => $this->popup_id,
		];

		if ( 'event' === $invalid_part ) {
			$invalid_event['event'] = 'unknown';
		} else {
			$invalid_event['pid'] = 999999;
		}

		add_action( 'pum_analytics_event', $hook );

		$response = $this->dispatch_json_request(
			[
				'events' => [
					[
						'event' => 'open',
						'pid'   => $this->popup_id,
					],
					$invalid_event,
				],
			]
		);

		remove_action( 'pum_analytics_event', $hook );

		$this->assert_error_response( $response, 'invalid_params' );
		$this->assert_event_counts( 0, 0 );
		$this->assertSame( 0, $hook_count );
	}

	/**
	 * Supply invalid entries after a valid event.
	 *
	 * @return array<string,array<int,string>>
	 */
	public function invalid_batch_event_provider() {
		return [
			'invalid event' => [ 'event' ],
			'invalid popup' => [ 'popup' ],
		];
	}

	/**
	 * Batch popup IDs must be positive integers before normalization.
	 */
	public function test_batch_rejects_popup_ids_that_would_normalize_to_valid_popup() {
		$hook_count = 0;
		$hook       = function () use ( &$hook_count ) {
			++$hook_count;
		};

		add_action( 'pum_analytics_event', $hook );

		foreach ( [ -$this->popup_id, $this->popup_id + 0.5 ] as $invalid_pid ) {
			$response = $this->dispatch_json_request(
				[
					'events' => [
						[
							'event' => 'open',
							'pid'   => $invalid_pid,
						],
					],
				]
			);

			$this->assert_error_response( $response, 'invalid_params' );
		}

		remove_action( 'pum_analytics_event', $hook );

		$this->assert_event_counts( 0, 0 );
		$this->assertSame( 0, $hook_count );
	}

	/**
	 * Oversized batches are rejected atomically before tracking.
	 */
	public function test_batch_limit_rejects_complete_payload_without_tracking() {
		$hook_count   = 0;
		$hook         = function () use ( &$hook_count ) {
			++$hook_count;
		};
		$limit_filter = function () {
			return 1;
		};

		add_action( 'pum_analytics_event', $hook );
		add_filter( 'pum_analytics_rest_batch_limit', $limit_filter );

		$response = $this->dispatch_json_request(
			[
				'events' => [
					[
						'event' => 'open',
						'pid'   => $this->popup_id,
					],
					[
						'event' => 'conversion',
						'pid'   => $this->popup_id,
					],
				],
			]
		);

		remove_filter( 'pum_analytics_rest_batch_limit', $limit_filter );
		remove_action( 'pum_analytics_event', $hook );

		$this->assert_error_response( $response, 'invalid_events' );
		$this->assert_event_counts( 0, 0 );
		$this->assertSame( 0, $hook_count );
	}

	/**
	 * Custom events continue to use the public event filter and hooks.
	 */
	public function test_batch_preserves_custom_event_filter_and_hooks() {
		$hook_count   = 0;
		$event_filter = function ( $events ) {
			$events[] = 'dismiss';
			return $events;
		};
		$event_hook   = function () use ( &$hook_count ) {
			++$hook_count;
		};

		add_filter( 'pum_analytics_valid_events', $event_filter );
		add_action( 'pum_analytics_dismiss', $event_hook, 10, 2 );

		$response = $this->dispatch_form_request(
			[
				'events' => wp_json_encode(
					[
						[
							'event' => 'dismiss',
							'pid'   => $this->popup_id,
						],
					]
				),
			]
		);

		remove_filter( 'pum_analytics_valid_events', $event_filter );
		remove_action( 'pum_analytics_dismiss', $event_hook, 10 );

		$this->assert_no_content( $response );
		$this->assertSame( 1, (int) get_post_meta( $this->popup_id, 'popup_dismiss_count', true ) );
		$this->assertSame( 1, $hook_count );
	}

	/**
	 * Every batch entry still passes through the existing rate limiter.
	 */
	public function test_batch_preserves_per_event_rate_limiting() {
		$limits_filter = function () {
			return [
				'window' => MINUTE_IN_SECONDS,
				'max'    => 1,
			];
		};

		add_filter( 'popup_maker/analytics/rate_limit', $limits_filter );

		$response = $this->dispatch_json_request(
			[
				'events' => [
					[
						'event' => 'open',
						'pid'   => $this->popup_id,
					],
					[
						'event' => 'open',
						'pid'   => $this->popup_id,
					],
				],
			]
		);

		remove_filter( 'popup_maker/analytics/rate_limit', $limits_filter );

		$this->assert_no_content( $response );
		$this->assert_event_counts( 1, 0 );
	}

	/**
	 * Filtered route arguments and ad-block-bypass route names remain supported.
	 */
	public function test_filtered_custom_route_dispatches_batch() {
		$route_filter = function ( $route_args ) {
			$route_args['args']['source'] = [
				'required' => false,
				'type'     => 'string',
			];

			return $route_args;
		};

		PUM_Utils_Options::update( 'bypass_adblockers', true );
		PUM_Utils_Options::update( 'adblock_bypass_url_method', 'custom' );
		PUM_Utils_Options::update( 'adblock_bypass_custom_filename', 'custom-metrics' );
		add_filter( 'pum_analytics_rest_route_args', $route_filter );
		$this->reset_rest_server();
		remove_filter( 'pum_analytics_rest_route_args', $route_filter );

		$routes = rest_get_server()->get_routes();
		$route  = $this->route();

		$this->assertArrayHasKey( $route, $routes );
		$this->assertArrayHasKey( 'source', $routes[ $route ][0]['args'] );

		$response = $this->dispatch_form_request(
			[
				'events' => wp_json_encode(
					[
						[
							'event' => 'open',
							'pid'   => $this->popup_id,
						],
					]
				),
				'source' => 'beacon',
			]
		);

		$this->assert_no_content( $response );
		$this->assert_event_counts( 1, 0 );
	}

	/**
	 * Reset and initialize the WordPress REST server.
	 */
	private function reset_rest_server() {
		global $wp_rest_server;

		$wp_rest_server = new WP_REST_Server();
		do_action( 'rest_api_init' );
	}

	/**
	 * Dispatch URL-encoded form parameters through the REST server.
	 *
	 * @param array $params Request parameters.
	 * @return WP_REST_Response
	 */
	private function dispatch_form_request( $params ) {
		$request = new WP_REST_Request( 'POST', $this->route() );
		$request->set_body_params( $params );

		return rest_do_request( $request );
	}

	/**
	 * Dispatch a JSON body through the REST server.
	 *
	 * @param array $params Request parameters.
	 * @return WP_REST_Response
	 */
	private function dispatch_json_request( $params ) {
		$request = new WP_REST_Request( 'POST', $this->route() );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body( wp_json_encode( $params ) );

		return rest_do_request( $request );
	}

	/**
	 * Get the current analytics route path.
	 *
	 * @return string
	 */
	private function route() {
		return '/' . PUM_Analytics::get_analytics_namespace() . '/' . PUM_Analytics::get_analytics_route();
	}

	/**
	 * Assert a successful no-content REST response.
	 *
	 * @param WP_REST_Response $response REST response.
	 */
	private function assert_no_content( $response ) {
		$this->assertSame( 204, $response->get_status() );
		$this->assertNull( $response->get_data() );
	}

	/**
	 * Assert an error REST response.
	 *
	 * @param WP_REST_Response $response REST response.
	 * @param string           $code     Expected error code.
	 */
	private function assert_error_response( $response, $code ) {
		$data = $response->get_data();

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( $code, $data['code'] );
	}

	/**
	 * Assert the popup's core analytics counters.
	 *
	 * @param int $opens       Expected open count.
	 * @param int $conversions Expected conversion count.
	 */
	private function assert_event_counts( $opens, $conversions ) {
		$this->assertSame( $opens, (int) get_post_meta( $this->popup_id, 'popup_open_count', true ) );
		$this->assertSame( $conversions, (int) get_post_meta( $this->popup_id, 'popup_conversion_count', true ) );
	}
}
