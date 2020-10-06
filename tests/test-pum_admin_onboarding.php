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
}
