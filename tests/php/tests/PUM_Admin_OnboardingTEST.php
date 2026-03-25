<?php
/**
 * Class PUM_Admin_OnboardingTEST
 *
 * @package Popup_Maker
 */


/**
 * Test methods within our PUM_Admin_Onboarding class
 */
class PUM_Admin_OnboardingTEST extends WP_UnitTestCase {

	/**
	 * Tests to make sure data returned from `all_popups_main_tour` is valid.
	 */
	public function test_all_popups_pointers() {
		$pointers = PUM_Admin_Onboarding::all_popups_main_tour( array() );
		$this->assertIsArray( $pointers );
	}

	/**
	 * Tests to make sure data returned from `tips_alert` is valid.
	 */
	public function test_tips_alert() {
		$alerts = PUM_Admin_Onboarding::tips_alert( array() );
		$this->assertIsArray( $alerts );
	}

	/**
	 * Tests to make sure data returned from `get_random_tip` is valid.
	 */
	public function test_get_random_tip() {
		$tip = PUM_Admin_Onboarding::get_random_tip();
		$this->assertIsArray( $tip );

		$this->assertCount( 2, $tip );

		$this->assertArrayHasKey( 'msg', $tip );
		$this->assertArrayHasKey( 'link', $tip );
	}

	/**
	 * Tests to make sure data returned from `should_show_tip` is valid.
	 */
	public function test_should_show_tip() {
		$result = PUM_Admin_Onboarding::should_show_tip();
		$this->assertIsBool( $result );
	}

	/**
	 * Tests to make sure data returned from `has_turned_off_tips` is valid.
	 */
	public function test_has_turned_off_tips() {
		$result = PUM_Admin_Onboarding::has_turned_off_tips();
		$this->assertIsBool( $result );
	}
}
