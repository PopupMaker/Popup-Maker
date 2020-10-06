<?php
/**
 * Class TestOnboarding
 *
 * @package Popup_Maker
 */


/**
 * Test methods within our PUM_Admin_Onboarding class
 */
class PUM_Utils_ArrayTest extends WP_UnitTestCase {

	/**
	 * Tests to make sure data returned from `all_popups_main_tour` is valid.
	 */
	public function test_filter_null() {
		$test = [
			null,
			'a',
			1,
			false,
		];

		$returned = PUM_Utils_Array::filter_null( $test );

		$this->assertIsArray( $returned );

		$this->assertCount( 3, $returned );
	}
}
