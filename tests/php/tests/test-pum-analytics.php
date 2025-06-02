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

		// Creates our test popup.
		$popup_id = wp_insert_post(array(
			'post_type' => 'popup'
		));

		// Make sure counts are 0.
		$popup = pum_get_popup( $popup_id );
		$popup->reset_counts();

		// Tests tracking an open.
		$open = array(
			'pid' => $popup_id,
			'event' => 'open'
		);
		PUM_Analytics::track( $open );
		$new_count = $popup->get_event_count( 'open' );
		$this->assertEquals( 1, $new_count, 'Open tracking check' );

		// Tests tracking a conversion.
		$conversion = array(
			'pid' => $popup_id,
			'event' => 'conversion'
		);
		PUM_Analytics::track( $conversion );
		$new_count = $popup->get_event_count( 'conversion' );
		$this->assertEquals( 1, $new_count, 'Conversion tracking check' );
	}

	/**
	 * Tests to make sure data returned from `pum_vars` is valid.
	 */
	public function test_pum_vars() {
		$pum_vars = PUM_Analytics::pum_vars( array() );
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
