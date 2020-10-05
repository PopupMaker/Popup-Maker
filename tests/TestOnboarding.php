<?php
/**
 * Class TestOnboarding
 *
 * @package Popup_Maker
 */

use PHPUnit\Framework\TestCase;

/**
 * Test methods within our PUM_Admin_Onboarding class
 */
class TestOnboarding extends WP_UnitTestCase {

	/**
	 * Tests to make sure data returned from `all_popups_main_tour` is valid.
	 */
	public function test_all_popups_pointers() {
		$pointers = PUM_Admin_Onboarding::all_popups_main_tour( array() );
		$this->assertTrue( is_array( $pointers ) );
	}
}
