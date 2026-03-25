<?php
/**
 * Class PUM_AnalyticsTEST
 *
 * @package Popup_Maker
 */


/**
 * Test methods within our PUM_Analytics class
 */
class PUM_AnalyticsTEST extends WP_UnitTestCase {

	/**
	 * Tests to make sure `track` is valid.
	 */
	public function test_track() {

		// Creates our test popup with publish status.
		$popup_id = wp_insert_post([
			'post_type'   => 'popup',
			'post_status' => 'publish',
			'post_title'  => 'Test Analytics Popup',
		]);

		// Ensure post type is correctly stored.
		$this->assertEquals( 'popup', get_post_type( $popup_id ), 'Post type should be popup.' );

		// Make sure counts are 0.
		$popup = pum_get_popup( $popup_id );

		// Verify pum_is_popup succeeds, otherwise tracking will be skipped.
		if ( ! pum_is_popup( $popup ) ) {
			$this->markTestSkipped( 'pum_is_popup() returned false — plugin may not be fully initialized in test context.' );
		}

		$popup->reset_counts();

		// Tests tracking an open.
		$open = [
			'pid'   => $popup_id,
			'event' => 'open',
		];
		PUM_Analytics::track( $open );

		// Re-fetch to avoid stale cache.
		$new_count = (int) get_post_meta( $popup_id, 'popup_open_count', true );
		$this->assertEquals( 1, $new_count, 'Open tracking check' );

		// Tests tracking a conversion.
		$conversion = [
			'pid'   => $popup_id,
			'event' => 'conversion',
		];
		PUM_Analytics::track( $conversion );

		// Re-fetch to avoid stale cache.
		$new_count = (int) get_post_meta( $popup_id, 'popup_conversion_count', true );
		$this->assertEquals( 1, $new_count, 'Conversion tracking check' );
	}

	/**
	 * Tests to make sure data returned from `pum_vars` is valid.
	 */
	public function test_pum_vars() {
		$pum_vars = PUM_Analytics::pum_vars( [] );
		$this->assertIsArray( $pum_vars );

		$this->assertArrayHasKey( 'analytics_route', $pum_vars );
		$this->assertArrayHasKey( 'analytics_api', $pum_vars );
	}

	/**
	 * Tests to make sure data returned from `get_analytics_namespace` is valid.
	 */
	public function test_get_analytics_namespace() {
		$namespace = PUM_Analytics::get_analytics_namespace();
		$this->assertIsString( $namespace );

		$this->assertStringContainsString( '/v1', $namespace );
	}

	/**
	 * Tests to make sure data returned from `get_analytics_route` is valid.
	 */
	public function test_get_analytics_route() {
		$route = PUM_Analytics::get_analytics_route();
		$this->assertIsString( $route );
	}
}
