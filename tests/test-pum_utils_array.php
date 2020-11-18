<?php
/**
 * Class PUM_Utils_ArrayTest
 *
 * @package Popup_Maker
 */


/**
 * Test methods within our PUM_Utils_Array class
 */
class PUM_Utils_ArrayTest extends WP_UnitTestCase {

	/**
	 * Tests to make sure data returned from `filter_null` is valid.
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

	/**
	 * Tests to make sure data returned from `remove_keys_starting_with` is valid.
	 */
	public function test_remove_keys_starting_with() {
		$test = [
			'test'  => 1,
			'first' => 'abc'
		];

		$returned = PUM_Utils_Array::remove_keys_starting_with( $test );
		$this->assertCount( 2, $returned );

		$returned = PUM_Utils_Array::remove_keys_starting_with( $test, 'sec' );
		$this->assertCount( 2, $returned );

		$returned = PUM_Utils_Array::remove_keys_starting_with( $test, 'tes' );
		$this->assertCount( 1, $returned );

		$returned = PUM_Utils_Array::remove_keys_starting_with( $test, ['tes'] );
		$this->assertCount( 1, $returned );
	}

	/**
	 * Tests to make sure data returned from `remove_keys` is valid.
	 */
	public function test_remove_keys() {
		$test = [
			'test'  => 1,
			'first' => 'abc'
		];

		$returned = PUM_Utils_Array::remove_keys( $test );
		$this->assertCount( 2, $returned );

		$returned = PUM_Utils_Array::remove_keys( $test, 'sec' );
		$this->assertCount( 2, $returned );

		$returned = PUM_Utils_Array::remove_keys( $test, 'test' );
		$this->assertCount( 1, $returned );

		$returned = PUM_Utils_Array::remove_keys( $test, ['test'] );
		$this->assertCount( 1, $returned );
	}
}
