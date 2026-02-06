<?php
/**
 * Class PUM_Utils_ArrayTest
 *
 * @package Popup_Maker
 */


/**
 * Test methods within our PUM_Utils_Array class.
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
	 * Tests filter_null with all null values.
	 */
	public function test_filter_null_all_null() {
		$returned = PUM_Utils_Array::filter_null( [ null, null ] );
		$this->assertCount( 0, $returned );
	}

	/**
	 * Tests filter_null with no null values.
	 */
	public function test_filter_null_no_nulls() {
		$returned = PUM_Utils_Array::filter_null( [ 'a', 0, false, '' ] );
		$this->assertCount( 4, $returned );
	}

	/**
	 * Tests to make sure data returned from `remove_keys_starting_with` is valid.
	 */
	public function test_remove_keys_starting_with() {
		$test = [
			'test'  => 1,
			'first' => 'abc',
		];

		$returned = PUM_Utils_Array::remove_keys_starting_with( $test );
		$this->assertCount( 2, $returned );

		$returned = PUM_Utils_Array::remove_keys_starting_with( $test, 'sec' );
		$this->assertCount( 2, $returned );

		$returned = PUM_Utils_Array::remove_keys_starting_with( $test, 'tes' );
		$this->assertCount( 1, $returned );

		$returned = PUM_Utils_Array::remove_keys_starting_with( $test, [ 'tes' ] );
		$this->assertCount( 1, $returned );
	}

	/**
	 * Tests to make sure data returned from `remove_keys` is valid.
	 */
	public function test_remove_keys() {
		$test = [
			'test'  => 1,
			'first' => 'abc',
		];

		$returned = PUM_Utils_Array::remove_keys( $test );
		$this->assertCount( 2, $returned );

		$returned = PUM_Utils_Array::remove_keys( $test, 'sec' );
		$this->assertCount( 2, $returned );

		$returned = PUM_Utils_Array::remove_keys( $test, 'test' );
		$this->assertCount( 1, $returned );

		$returned = PUM_Utils_Array::remove_keys( $test, [ 'test' ] );
		$this->assertCount( 1, $returned );
	}

	// ─── remove_keys_ending_with ────────────────────────────────────────

	/**
	 * Tests removing keys that end with a given suffix.
	 */
	public function test_remove_keys_ending_with() {
		$test = [
			'first_name' => 'John',
			'last_name'  => 'Doe',
			'email'      => 'john@example.com',
		];

		// Matching suffix removes correct keys.
		$returned = PUM_Utils_Array::remove_keys_ending_with( $test, '_name' );
		$this->assertCount( 1, $returned );
		$this->assertArrayHasKey( 'email', $returned );

		// Non-matching suffix removes nothing.
		$returned = PUM_Utils_Array::remove_keys_ending_with( $test, '_id' );
		$this->assertCount( 3, $returned );

		// Empty strings param returns all keys.
		$returned = PUM_Utils_Array::remove_keys_ending_with( $test );
		$this->assertCount( 3, $returned );
	}

	// ─── remove_keys_containing ─────────────────────────────────────────

	/**
	 * Tests removing keys that contain a given substring.
	 */
	public function test_remove_keys_containing() {
		$test = [
			'popup_title'   => 'Hello',
			'popup_content' => 'World',
			'theme_color'   => 'blue',
		];

		$returned = PUM_Utils_Array::remove_keys_containing( $test, 'popup' );
		$this->assertCount( 1, $returned );
		$this->assertArrayHasKey( 'theme_color', $returned );
	}

	// ─── pluck_keys_starting_with ───────────────────────────────────────

	/**
	 * Tests plucking (keeping) only keys that start with a prefix.
	 */
	public function test_pluck_keys_starting_with() {
		$test = [
			'popup_title'   => 'Hello',
			'popup_content' => 'World',
			'theme_color'   => 'blue',
		];

		$returned = PUM_Utils_Array::pluck_keys_starting_with( $test, 'popup' );
		$this->assertCount( 2, $returned );
		$this->assertArrayHasKey( 'popup_title', $returned );
		$this->assertArrayHasKey( 'popup_content', $returned );
	}

	// ─── allowed_keys ───────────────────────────────────────────────────

	/**
	 * Tests extracting only allowed keys.
	 */
	public function test_allowed_keys() {
		$test = [
			'name'     => 'John',
			'email'    => 'john@example.com',
			'password' => 'secret',
		];

		$returned = PUM_Utils_Array::allowed_keys( $test, [ 'name', 'email' ] );
		$this->assertCount( 2, $returned );
		$this->assertArrayNotHasKey( 'password', $returned );
	}

	// ─── parse_allowed_args ─────────────────────────────────────────────

	/**
	 * Tests parsing args with defaults and allowed keys filtering.
	 */
	public function test_parse_allowed_args() {
		$input    = [
			'color' => 'red',
			'extra' => 'should_be_removed',
		];
		$defaults = [
			'color' => 'blue',
			'size'  => 'medium',
		];

		$returned = PUM_Utils_Array::parse_allowed_args( $input, $defaults );

		$this->assertEquals( 'red', $returned['color'] );
		$this->assertEquals( 'medium', $returned['size'] );
		$this->assertArrayNotHasKey( 'extra', $returned );
	}

	// ─── fix_json_boolean_values ────────────────────────────────────────

	/**
	 * Tests that string 'true' and 'false' become real booleans.
	 */
	public function test_fix_json_boolean_values() {
		$input = [
			'enabled'  => 'true',
			'disabled' => 'false',
			'name'     => 'keep_me',
			'nested'   => [
				'flag' => 'true',
			],
		];

		$returned = PUM_Utils_Array::fix_json_boolean_values( $input );

		$this->assertTrue( $returned['enabled'] );
		$this->assertFalse( $returned['disabled'] );
		$this->assertEquals( 'keep_me', $returned['name'] );
		$this->assertTrue( $returned['nested']['flag'] );
	}

	// ─── from_object ────────────────────────────────────────────────────

	/**
	 * Tests recursive object to array conversion.
	 */
	public function test_from_object() {
		$obj        = new stdClass();
		$obj->name  = 'test';
		$obj->child = new stdClass();
		$obj->child->value = 42;

		$returned = PUM_Utils_Array::from_object( $obj );

		$this->assertIsArray( $returned );
		$this->assertEquals( 'test', $returned['name'] );
		$this->assertIsArray( $returned['child'] );
		$this->assertEquals( 42, $returned['child']['value'] );
	}

	// ─── remap_keys ────────────────────────────────────────────────────

	/**
	 * Tests remapping array keys to new names.
	 */
	public function test_remap_keys() {
		$input = [
			'old_name'  => 'John',
			'old_email' => 'john@example.com',
		];

		$returned = PUM_Utils_Array::remap_keys( $input, [
			'old_name'  => 'name',
			'old_email' => 'email',
		] );

		$this->assertArrayHasKey( 'name', $returned );
		$this->assertArrayHasKey( 'email', $returned );
		$this->assertArrayNotHasKey( 'old_name', $returned );
		$this->assertEquals( 'John', $returned['name'] );
	}

	// ─── replace_key ───────────────────────────────────────────────────

	/**
	 * Tests replacing a key name while preserving order.
	 */
	public function test_replace_key() {
		$input = [
			'a' => 1,
			'b' => 2,
			'c' => 3,
		];

		$returned = PUM_Utils_Array::replace_key( $input, 'b', 'beta' );

		$this->assertIsArray( $returned );
		$this->assertArrayHasKey( 'beta', $returned );
		$this->assertArrayNotHasKey( 'b', $returned );
		$this->assertEquals( 2, $returned['beta'] );

		// Verify key order is preserved.
		$keys = array_keys( $returned );
		$this->assertEquals( [ 'a', 'beta', 'c' ], $keys );
	}

	// ─── sort ───────────────────────────────────────────────────────────

	/**
	 * Tests sorting by key.
	 */
	public function test_sort_by_key() {
		$input = [
			'c' => 3,
			'a' => 1,
			'b' => 2,
		];

		$returned = PUM_Utils_Array::sort( $input, 'key' );
		$keys     = array_keys( $returned );
		$this->assertEquals( [ 'a', 'b', 'c' ], $keys );
	}

	/**
	 * Tests reverse sort by key.
	 */
	public function test_sort_by_key_reverse() {
		$input = [
			'a' => 1,
			'c' => 3,
			'b' => 2,
		];

		$returned = PUM_Utils_Array::sort( $input, 'key', true );
		$keys     = array_keys( $returned );
		$this->assertEquals( [ 'c', 'b', 'a' ], $keys );
	}

	/**
	 * Tests sort by priority.
	 */
	public function test_sort_by_priority() {
		$input = [
			'high'   => [ 'priority' => 1 ],
			'low'    => [ 'priority' => 10 ],
			'medium' => [ 'priority' => 5 ],
		];

		$returned = PUM_Utils_Array::sort( $input, 'priority' );
		$keys     = array_keys( $returned );
		$this->assertEquals( 'high', $keys[0] );
		$this->assertEquals( 'low', $keys[2] );
	}

	// ─── move_item ──────────────────────────────────────────────────────

	/**
	 * Tests moving an item to the top.
	 */
	public function test_move_item_to_top() {
		$arr = [
			'a' => 1,
			'b' => 2,
			'c' => 3,
		];

		$result = PUM_Utils_Array::move_item( $arr, 'c', 'top' );
		$this->assertTrue( $result );

		$keys = array_keys( $arr );
		$this->assertEquals( 'c', $keys[0] );
	}

	/**
	 * Tests moving an item to the bottom.
	 */
	public function test_move_item_to_bottom() {
		$arr = [
			'a' => 1,
			'b' => 2,
			'c' => 3,
		];

		$result = PUM_Utils_Array::move_item( $arr, 'a', 'bottom' );
		$this->assertTrue( $result );

		$keys = array_keys( $arr );
		$this->assertEquals( 'a', end( $keys ) );
	}

	/**
	 * Tests swapping two items.
	 */
	public function test_move_item_swap() {
		$arr = [
			'a' => 1,
			'b' => 2,
			'c' => 3,
		];

		$result = PUM_Utils_Array::move_item( $arr, 'a', 'swap', 'c' );
		$this->assertTrue( $result );

		$keys = array_keys( $arr );
		$this->assertEquals( 'c', $keys[0] );
		$this->assertEquals( 'a', $keys[2] );
	}

	/**
	 * Tests move with non-existent key returns false.
	 */
	public function test_move_item_invalid_key() {
		$arr    = [ 'a' => 1 ];
		$result = PUM_Utils_Array::move_item( $arr, 'z', 'top' );
		$this->assertFalse( $result );
	}

	// ─── sanitize ──────────────────────────────────────────────────────

	/**
	 * Tests sanitize with a string input.
	 */
	public function test_sanitize_string() {
		$returned = PUM_Utils_Array::sanitize( '<script>alert("xss")</script>' );
		$this->assertIsString( $returned );
		$this->assertStringNotContainsString( '<script>', $returned );
	}

	/**
	 * Tests sanitize with nested array input.
	 */
	public function test_sanitize_array() {
		$input    = [
			'safe'   => 'hello',
			'unsafe' => '<b>bold</b>',
		];
		$returned = PUM_Utils_Array::sanitize( $input );
		$this->assertIsArray( $returned );
		$this->assertStringNotContainsString( '<b>', $returned['unsafe'] );
	}
}
