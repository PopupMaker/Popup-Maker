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
		$fired_popup_id  = null;
		$fired_event_data = null;

		add_action(
			'popup_maker/link_click_tracked',
			function ( $popup_id, $event_data ) use ( &$fired_popup_id, &$fired_event_data ) {
				$fired_popup_id  = $popup_id;
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
