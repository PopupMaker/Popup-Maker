<?php
/**
 * Form Conversion Tracking Service tests.
 *
 * @package Popup_Maker
 */

use PopupMaker\Services\FormConversionTracking;

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
}
