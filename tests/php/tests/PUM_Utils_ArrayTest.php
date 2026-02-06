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

	/**
	 * Tests sanitize with deeply nested array.
	 */
	public function test_sanitize_deeply_nested() {
		$input    = [
			'level1' => [
				'level2' => '<img onerror=alert(1)>',
			],
		];
		$returned = PUM_Utils_Array::sanitize( $input );
		$this->assertIsArray( $returned['level1'] );
		$this->assertStringNotContainsString( 'onerror', $returned['level1']['level2'] );
	}

	// ─── filter_null edge cases ────────────────────────────────────────

	/**
	 * Tests filter_null with empty array.
	 */
	public function test_filter_null_empty_array() {
		$returned = PUM_Utils_Array::filter_null( [] );
		$this->assertIsArray( $returned );
		$this->assertCount( 0, $returned );
	}

	/**
	 * Tests filter_null_callback directly returns true for non-null.
	 */
	public function test_filter_null_callback_non_null() {
		$this->assertTrue( PUM_Utils_Array::filter_null_callback( 'hello' ) );
		$this->assertTrue( PUM_Utils_Array::filter_null_callback( 0 ) );
		$this->assertTrue( PUM_Utils_Array::filter_null_callback( false ) );
		$this->assertTrue( PUM_Utils_Array::filter_null_callback( '' ) );
	}

	/**
	 * Tests filter_null_callback returns false for null.
	 */
	public function test_filter_null_callback_null() {
		$this->assertFalse( PUM_Utils_Array::filter_null_callback( null ) );
	}

	/**
	 * Tests filter_null_callback default parameter is null.
	 */
	public function test_filter_null_callback_default() {
		$this->assertFalse( PUM_Utils_Array::filter_null_callback() );
	}

	// ─── remove_keys_starting_with edge cases ──────────────────────────

	/**
	 * Tests remove_keys_starting_with with false (falsy) as strings param.
	 */
	public function test_remove_keys_starting_with_false() {
		$test     = [ 'a' => 1, 'b' => 2 ];
		$returned = PUM_Utils_Array::remove_keys_starting_with( $test, false );
		$this->assertCount( 2, $returned );
	}

	/**
	 * Tests remove_keys_starting_with with multiple string matches.
	 */
	public function test_remove_keys_starting_with_multiple_strings() {
		$test = [
			'popup_title'  => 'Hello',
			'theme_color'  => 'blue',
			'config_debug' => true,
		];

		$returned = PUM_Utils_Array::remove_keys_starting_with( $test, [ 'popup', 'config' ] );
		$this->assertCount( 1, $returned );
		$this->assertArrayHasKey( 'theme_color', $returned );
	}

	// ─── remove_keys_ending_with edge cases ────────────────────────────

	/**
	 * Tests remove_keys_ending_with with false as strings param.
	 */
	public function test_remove_keys_ending_with_false() {
		$test     = [ 'a_id' => 1 ];
		$returned = PUM_Utils_Array::remove_keys_ending_with( $test, false );
		$this->assertCount( 1, $returned );
	}

	/**
	 * Tests remove_keys_ending_with with multiple suffixes.
	 */
	public function test_remove_keys_ending_with_multiple_suffixes() {
		$test = [
			'first_name' => 'John',
			'user_id'    => 1,
			'email'      => 'test@test.com',
		];

		$returned = PUM_Utils_Array::remove_keys_ending_with( $test, [ '_name', '_id' ] );
		$this->assertCount( 1, $returned );
		$this->assertArrayHasKey( 'email', $returned );
	}

	// ─── remove_keys_containing edge cases ─────────────────────────────

	/**
	 * Tests remove_keys_containing with object input.
	 */
	public function test_remove_keys_containing_object_input() {
		$obj       = new stdClass();
		$obj->popup_title = 'test';
		$obj->theme_color = 'red';

		$returned = PUM_Utils_Array::remove_keys_containing( $obj, 'popup' );
		$this->assertIsArray( $returned );
		$this->assertCount( 1, $returned );
		$this->assertArrayHasKey( 'theme_color', $returned );
	}

	/**
	 * Tests remove_keys_containing with non-array, non-object input.
	 */
	public function test_remove_keys_containing_non_array_input() {
		$returned = PUM_Utils_Array::remove_keys_containing( 'not_an_array', 'test' );
		$this->assertSame( 'not_an_array', $returned );
	}

	/**
	 * Tests remove_keys_containing with false strings param.
	 */
	public function test_remove_keys_containing_false_strings() {
		$test     = [ 'key' => 'val' ];
		$returned = PUM_Utils_Array::remove_keys_containing( $test, false );
		$this->assertCount( 1, $returned );
	}

	/**
	 * Tests remove_keys_containing with single string (not array).
	 */
	public function test_remove_keys_containing_single_string() {
		$test = [
			'popup_title' => 'Hi',
			'other'       => 'val',
		];

		$returned = PUM_Utils_Array::remove_keys_containing( $test, 'popup' );
		$this->assertCount( 1, $returned );
	}

	// ─── pluck edge cases ──────────────────────────────────────────────

	/**
	 * Tests pluck with object input converts to array.
	 */
	public function test_pluck_object_input() {
		$obj       = new stdClass();
		$obj->name = 'John';
		$obj->age  = 30;

		$returned = PUM_Utils_Array::pluck( $obj, [ 'name' ] );
		$this->assertIsArray( $returned );
		$this->assertArrayHasKey( 'name', $returned );
		$this->assertArrayNotHasKey( 'age', $returned );
	}

	/**
	 * Tests pluck with non-array input returns empty array.
	 */
	public function test_pluck_non_array_returns_empty() {
		$returned = PUM_Utils_Array::pluck( 'string_input', [ 'key' ] );
		$this->assertIsArray( $returned );
		$this->assertEmpty( $returned );
	}

	/**
	 * Tests pluck with integer input returns empty array.
	 */
	public function test_pluck_integer_returns_empty() {
		$returned = PUM_Utils_Array::pluck( 42, [ 'key' ] );
		$this->assertIsArray( $returned );
		$this->assertEmpty( $returned );
	}

	// ─── pluck_keys_containing edge cases ──────────────────────────────

	/**
	 * Tests pluck_keys_containing with object input.
	 */
	public function test_pluck_keys_containing_object() {
		$obj              = new stdClass();
		$obj->popup_title = 'Hello';
		$obj->theme_color = 'blue';

		$returned = PUM_Utils_Array::pluck_keys_containing( $obj, [ 'popup' ] );
		$this->assertIsArray( $returned );
		$this->assertArrayHasKey( 'popup_title', $returned );
	}

	/**
	 * Tests pluck_keys_containing with non-array input.
	 */
	public function test_pluck_keys_containing_non_array() {
		$returned = PUM_Utils_Array::pluck_keys_containing( 'nope', [ 'key' ] );
		$this->assertIsArray( $returned );
		$this->assertEmpty( $returned );
	}

	// ─── pluck_keys_ending_with ────────────────────────────────────────

	/**
	 * Tests pluck_keys_ending_with keeps matching keys.
	 */
	public function test_pluck_keys_ending_with() {
		$test = [
			'first_name' => 'John',
			'last_name'  => 'Doe',
			'email'      => 'test@test.com',
		];

		$returned = PUM_Utils_Array::pluck_keys_ending_with( $test, '_name' );
		$this->assertCount( 2, $returned );
		$this->assertArrayHasKey( 'first_name', $returned );
		$this->assertArrayHasKey( 'last_name', $returned );
		$this->assertArrayNotHasKey( 'email', $returned );
	}

	// ─── sort edge cases ───────────────────────────────────────────────

	/**
	 * Tests sort with non-array input returns input unchanged.
	 */
	public function test_sort_non_array() {
		$returned = PUM_Utils_Array::sort( 'not_an_array' );
		$this->assertSame( 'not_an_array', $returned );
	}

	/**
	 * Tests natural sort.
	 */
	public function test_sort_natural() {
		$input = [
			'img12' => 'a',
			'img2'  => 'b',
			'img1'  => 'c',
		];

		$returned = PUM_Utils_Array::sort( $input, 'natural' );
		$values   = array_values( $returned );
		$this->assertSame( 'a', $values[0] );
		$this->assertSame( 'b', $values[1] );
		$this->assertSame( 'c', $values[2] );
	}

	/**
	 * Tests reverse sort by priority.
	 */
	public function test_sort_by_priority_reverse() {
		$input = [
			'high'   => [ 'priority' => 1 ],
			'low'    => [ 'priority' => 10 ],
			'medium' => [ 'priority' => 5 ],
		];

		$returned = PUM_Utils_Array::sort( $input, 'priority', true );
		$keys     = array_keys( $returned );
		$this->assertSame( 'low', $keys[0] );
		$this->assertSame( 'high', $keys[2] );
	}

	/**
	 * Tests sort by priority using 'pri' key shorthand.
	 */
	public function test_sort_by_priority_pri_key() {
		$input = [
			'a' => [ 'pri' => 3 ],
			'b' => [ 'pri' => 1 ],
			'c' => [ 'pri' => 2 ],
		];

		$returned = PUM_Utils_Array::sort( $input, 'priority' );
		$keys     = array_keys( $returned );
		$this->assertSame( 'b', $keys[0] );
		$this->assertSame( 'a', $keys[2] );
	}

	/**
	 * Tests sort_by_priority returns 0 when both lack priority.
	 */
	public function test_sort_by_priority_no_priority_keys() {
		$result = PUM_Utils_Array::sort_by_priority( [ 'name' => 'a' ], [ 'name' => 'b' ] );
		$this->assertSame( 0, $result );
	}

	/**
	 * Tests sort_by_priority returns 0 when priorities are equal.
	 */
	public function test_sort_by_priority_equal() {
		$result = PUM_Utils_Array::sort_by_priority( [ 'priority' => 5 ], [ 'priority' => 5 ] );
		$this->assertSame( 0, $result );
	}

	/**
	 * Tests rsort_by_priority returns 0 when both lack priority.
	 */
	public function test_rsort_by_priority_no_priority() {
		$result = PUM_Utils_Array::rsort_by_priority( [ 'x' => 1 ], [ 'y' => 2 ] );
		$this->assertSame( 0, $result );
	}

	/**
	 * Tests rsort_by_priority returns 0 when priorities are equal.
	 */
	public function test_rsort_by_priority_equal() {
		$result = PUM_Utils_Array::rsort_by_priority( [ 'priority' => 3 ], [ 'priority' => 3 ] );
		$this->assertSame( 0, $result );
	}

	// ─── move_item edge cases ──────────────────────────────────────────

	/**
	 * Tests move_item up by one position (numeric -1).
	 */
	public function test_move_item_up_numeric() {
		$arr = [
			'a' => 1,
			'b' => 2,
			'c' => 3,
		];

		$result = PUM_Utils_Array::move_item( $arr, 'c', -1 );
		$this->assertTrue( $result );

		$keys = array_keys( $arr );
		// 'c' should move before 'b'.
		$this->assertSame( 'a', $keys[0] );
		$this->assertSame( 'c', $keys[1] );
	}

	/**
	 * Tests move_item down by one position (numeric 1).
	 */
	public function test_move_item_down_numeric() {
		$arr = [
			'a' => 1,
			'b' => 2,
			'c' => 3,
		];

		$result = PUM_Utils_Array::move_item( $arr, 'a', 1 );
		$this->assertTrue( $result );

		$keys = array_keys( $arr );
		$this->assertSame( 'b', $keys[0] );
		$this->assertSame( 'a', $keys[1] );
	}

	/**
	 * Tests move_item with 'up' keyword.
	 */
	public function test_move_item_up_keyword() {
		$arr = [
			'a' => 1,
			'b' => 2,
			'c' => 3,
		];

		$result = PUM_Utils_Array::move_item( $arr, 'b', 'up' );
		$this->assertTrue( $result );

		$keys = array_keys( $arr );
		$this->assertSame( 'b', $keys[0] );
	}

	/**
	 * Tests move_item with 'down' keyword.
	 */
	public function test_move_item_down_keyword() {
		$arr = [
			'a' => 1,
			'b' => 2,
			'c' => 3,
		];

		$result = PUM_Utils_Array::move_item( $arr, 'b', 'down' );
		$this->assertTrue( $result );

		$keys = array_keys( $arr );
		$this->assertSame( 'b', $keys[2] );
	}

	/**
	 * Tests move_item before a specific key.
	 */
	public function test_move_item_before_key() {
		$arr = [
			'a' => 1,
			'b' => 2,
			'c' => 3,
		];

		$result = PUM_Utils_Array::move_item( $arr, 'c', 'before', 'a' );
		$this->assertTrue( $result );

		$keys = array_keys( $arr );
		$this->assertSame( 'c', $keys[0] );
	}

	/**
	 * Tests move_item after a specific key.
	 */
	public function test_move_item_after_key() {
		$arr = [
			'a' => 1,
			'b' => 2,
			'c' => 3,
		];

		$result = PUM_Utils_Array::move_item( $arr, 'a', 'after', 'c' );
		$this->assertTrue( $result );

		$keys = array_keys( $arr );
		$this->assertSame( 'a', end( $keys ) );
	}

	/**
	 * Tests move_item swap with numeric 0.
	 */
	public function test_move_item_swap_numeric() {
		$arr = [
			'a' => 1,
			'b' => 2,
		];

		$result = PUM_Utils_Array::move_item( $arr, 'a', 0, 'b' );
		$this->assertTrue( $result );

		$keys = array_keys( $arr );
		$this->assertSame( 'b', $keys[0] );
		$this->assertSame( 'a', $keys[1] );
	}

	/**
	 * Tests move_item with 0 move and same key returns true (no-op).
	 */
	public function test_move_item_zero_same_key_noop() {
		$arr    = [ 'a' => 1, 'b' => 2 ];
		$result = PUM_Utils_Array::move_item( $arr, 'a', 0 );
		$this->assertTrue( $result );
	}

	/**
	 * Tests move_item with invalid move string returns false.
	 */
	public function test_move_item_invalid_move_string() {
		$arr    = [ 'a' => 1 ];
		$result = PUM_Utils_Array::move_item( $arr, 'a', 'invalid_direction' );
		$this->assertFalse( $result );
	}

	/**
	 * Tests move_item with non-existent key2 returns false.
	 */
	public function test_move_item_invalid_key2() {
		$arr    = [ 'a' => 1, 'b' => 2 ];
		$result = PUM_Utils_Array::move_item( $arr, 'a', 'before', 'z' );
		$this->assertFalse( $result );
	}

	// ─── replace_key edge cases ────────────────────────────────────────

	/**
	 * Tests replace_key with non-existent key does not crash.
	 */
	public function test_replace_key_nonexistent() {
		$input    = [ 'a' => 1 ];
		$returned = PUM_Utils_Array::replace_key( $input, 'missing', 'new' );
		// The method does not throw, it will attempt array_combine.
		$this->assertNotFalse( $returned );
	}

	// ─── remap_keys edge cases ─────────────────────────────────────────

	/**
	 * Tests remap_keys skips empty values (does not copy them).
	 */
	public function test_remap_keys_empty_value_not_copied() {
		$input = [
			'old_key' => '',
			'keep'    => 'val',
		];

		$returned = PUM_Utils_Array::remap_keys( $input, [ 'old_key' => 'new_key' ] );
		$this->assertArrayNotHasKey( 'old_key', $returned );
		// Empty value means it won't be set on the new key.
		$this->assertArrayNotHasKey( 'new_key', $returned );
		$this->assertArrayHasKey( 'keep', $returned );
	}

	/**
	 * Tests remap_keys with empty remap array.
	 */
	public function test_remap_keys_empty_remap() {
		$input    = [ 'key' => 'val' ];
		$returned = PUM_Utils_Array::remap_keys( $input, [] );
		$this->assertSame( $input, $returned );
	}

	// ─── from_object edge cases ────────────────────────────────────────

	/**
	 * Tests from_object with scalar returns scalar.
	 */
	public function test_from_object_scalar() {
		$this->assertSame( 'hello', PUM_Utils_Array::from_object( 'hello' ) );
		$this->assertSame( 42, PUM_Utils_Array::from_object( 42 ) );
		$this->assertNull( PUM_Utils_Array::from_object( null ) );
	}

	/**
	 * Tests from_object with plain array.
	 */
	public function test_from_object_plain_array() {
		$input    = [ 'a' => 1, 'b' => 2 ];
		$returned = PUM_Utils_Array::from_object( $input );
		$this->assertSame( $input, $returned );
	}

	// ─── fix_json_boolean_values edge cases ────────────────────────────

	/**
	 * Tests fix_json_boolean_values with non-array input returns it unchanged.
	 */
	public function test_fix_json_boolean_values_non_array() {
		$this->assertSame( 'hello', PUM_Utils_Array::fix_json_boolean_values( 'hello' ) );
		$this->assertSame( 42, PUM_Utils_Array::fix_json_boolean_values( 42 ) );
	}

	/**
	 * Tests fix_json_boolean_values does not affect numeric strings.
	 */
	public function test_fix_json_boolean_values_numeric_strings() {
		$input    = [ 'count' => '5', 'flag' => 'true' ];
		$returned = PUM_Utils_Array::fix_json_boolean_values( $input );
		$this->assertSame( '5', $returned['count'] );
		$this->assertTrue( $returned['flag'] );
	}

	// ─── safe_json_decode ──────────────────────────────────────────────

	/**
	 * Tests safe_json_decode with valid JSON string.
	 */
	public function test_safe_json_decode_valid() {
		$json     = '{"key":"value","nested":{"a":1}}';
		$returned = PUM_Utils_Array::safe_json_decode( $json );
		$this->assertIsArray( $returned );
		$this->assertSame( 'value', $returned['key'] );
		$this->assertSame( 1, $returned['nested']['a'] );
	}

	/**
	 * Tests safe_json_decode with escaped JSON (stripslashes).
	 */
	public function test_safe_json_decode_escaped() {
		$json     = '{\"key\":\"value\"}';
		$returned = PUM_Utils_Array::safe_json_decode( $json );
		$this->assertIsArray( $returned );
		$this->assertSame( 'value', $returned['key'] );
	}

	/**
	 * Tests safe_json_decode with boolean string values.
	 */
	public function test_safe_json_decode_boolean_strings() {
		$json     = '{"enabled":"true","disabled":"false"}';
		$returned = PUM_Utils_Array::safe_json_decode( $json );
		$this->assertTrue( $returned['enabled'] );
		$this->assertFalse( $returned['disabled'] );
	}

	/**
	 * Tests safe_json_decode with empty string returns empty array.
	 */
	public function test_safe_json_decode_empty_string() {
		$returned = PUM_Utils_Array::safe_json_decode( '' );
		$this->assertIsArray( $returned );
		$this->assertCount( 1, $returned );
		$this->assertSame( '', $returned[0] );
	}

	/**
	 * Tests safe_json_decode with array input returns array.
	 */
	public function test_safe_json_decode_array_input() {
		$input    = [ 'already' => 'array' ];
		$returned = PUM_Utils_Array::safe_json_decode( $input );
		$this->assertIsArray( $returned );
		$this->assertSame( 'array', $returned['already'] );
	}

	// ─── safe_json_encode ──────────────────────────────────────────────

	/**
	 * Tests safe_json_encode returns valid JSON.
	 */
	public function test_safe_json_encode_basic() {
		$result = PUM_Utils_Array::safe_json_encode( [ 'key' => 'value' ] );
		$this->assertIsString( $result );
		$decoded = json_decode( $result, true );
		$this->assertSame( 'value', $decoded['key'] );
	}

	/**
	 * Tests safe_json_encode with HTML entities.
	 */
	public function test_safe_json_encode_html_entities() {
		$result  = PUM_Utils_Array::safe_json_encode( [ 'text' => '&amp; &lt;' ] );
		$decoded = json_decode( $result, true );
		$this->assertSame( '& <', $decoded['text'] );
	}

	/**
	 * Tests safe_json_encode with empty array.
	 */
	public function test_safe_json_encode_empty() {
		$result = PUM_Utils_Array::safe_json_encode( [] );
		$this->assertSame( '[]', $result );
	}

	// ─── make_safe_for_json_encode ─────────────────────────────────────

	/**
	 * Tests make_safe_for_json_encode with scalar input.
	 */
	public function test_make_safe_for_json_encode_scalar() {
		$result = PUM_Utils_Array::make_safe_for_json_encode( '&amp; test' );
		$this->assertSame( '& test', $result );
	}

	/**
	 * Tests make_safe_for_json_encode preserves booleans in arrays.
	 */
	public function test_make_safe_for_json_encode_preserves_booleans() {
		$input  = [ 'flag' => true, 'text' => '&lt;div&gt;' ];
		$result = PUM_Utils_Array::make_safe_for_json_encode( $input );
		$this->assertTrue( $result['flag'] );
		$this->assertSame( '<div>', $result['text'] );
	}

	/**
	 * Tests make_safe_for_json_encode with nested arrays.
	 */
	public function test_make_safe_for_json_encode_nested() {
		$input  = [ 'nested' => [ 'val' => '&quot;quoted&quot;' ] ];
		$result = PUM_Utils_Array::make_safe_for_json_encode( $input );
		$this->assertSame( '"quoted"', $result['nested']['val'] );
	}

	// ─── utf8_encode_recursive ─────────────────────────────────────────

	/**
	 * Tests utf8_encode_recursive with string input.
	 */
	public function test_utf8_encode_recursive_string() {
		$result = PUM_Utils_Array::utf8_encode_recursive( 'hello' );
		$this->assertIsString( $result );
	}

	/**
	 * Tests utf8_encode_recursive with nested array.
	 */
	public function test_utf8_encode_recursive_array() {
		$input  = [ 'a' => 'hello', 'b' => [ 'c' => 'world' ] ];
		$result = PUM_Utils_Array::utf8_encode_recursive( $input );
		$this->assertIsArray( $result );
		$this->assertIsString( $result['a'] );
		$this->assertIsString( $result['b']['c'] );
	}

	/**
	 * Tests utf8_encode_recursive with non-string, non-array returns unchanged.
	 */
	public function test_utf8_encode_recursive_integer() {
		$result = PUM_Utils_Array::utf8_encode_recursive( 42 );
		$this->assertSame( 42, $result );
	}

	// ─── maybe_json_attr ───────────────────────────────────────────────

	/**
	 * Tests maybe_json_attr with array returns JSON string.
	 */
	public function test_maybe_json_attr_array() {
		$result = PUM_Utils_Array::maybe_json_attr( [ 'key' => 'val' ] );
		$this->assertIsString( $result );
		$decoded = json_decode( $result, true );
		$this->assertSame( 'val', $decoded['key'] );
	}

	/**
	 * Tests maybe_json_attr with object returns JSON string.
	 */
	public function test_maybe_json_attr_object() {
		$obj      = new stdClass();
		$obj->key = 'val';
		$result   = PUM_Utils_Array::maybe_json_attr( $obj );
		$this->assertIsString( $result );
		$decoded = json_decode( $result, true );
		$this->assertSame( 'val', $decoded['key'] );
	}

	/**
	 * Tests maybe_json_attr with encode flag escapes HTML.
	 */
	public function test_maybe_json_attr_encoded() {
		$result = PUM_Utils_Array::maybe_json_attr( [ 'key' => 'val' ], true );
		$this->assertIsString( $result );
		// Should have escaped characters.
		$this->assertStringContainsString( '&quot;', $result );
	}

	/**
	 * Tests maybe_json_attr with scalar returns scalar unchanged.
	 */
	public function test_maybe_json_attr_scalar() {
		$this->assertSame( 'hello', PUM_Utils_Array::maybe_json_attr( 'hello' ) );
		$this->assertSame( 42, PUM_Utils_Array::maybe_json_attr( 42 ) );
		$this->assertTrue( PUM_Utils_Array::maybe_json_attr( true ) );
	}

	// ─── sort_by_sort ──────────────────────────────────────────────────

	/**
	 * Tests sort_by_sort comparator directly.
	 */
	public function test_sort_by_sort() {
		$this->assertSame( -1, PUM_Utils_Array::sort_by_sort( [ 'sort' => 1 ], [ 'sort' => 2 ] ) <=> 0 ? -1 : 1 );
		$this->assertLessThan( 0, PUM_Utils_Array::sort_by_sort( [ 'sort' => 1 ], [ 'sort' => 10 ] ) );
		$this->assertGreaterThan( 0, PUM_Utils_Array::sort_by_sort( [ 'sort' => 10 ], [ 'sort' => 1 ] ) );
		$this->assertSame( 0, PUM_Utils_Array::sort_by_sort( [ 'sort' => 5 ], [ 'sort' => 5 ] ) );
	}

	// ─── allowed_keys edge cases ───────────────────────────────────────

	/**
	 * Tests allowed_keys with empty allowed list returns empty.
	 */
	public function test_allowed_keys_empty_allowed() {
		$returned = PUM_Utils_Array::allowed_keys( [ 'a' => 1 ], [] );
		$this->assertEmpty( $returned );
	}

	/**
	 * Tests allowed_keys with non-existent keys returns empty.
	 */
	public function test_allowed_keys_nonexistent() {
		$returned = PUM_Utils_Array::allowed_keys( [ 'a' => 1 ], [ 'x', 'y' ] );
		$this->assertEmpty( $returned );
	}

	// ─── remove_keys edge cases ────────────────────────────────────────

	/**
	 * Tests remove_keys with non-existent key does nothing.
	 */
	public function test_remove_keys_nonexistent() {
		$test     = [ 'a' => 1 ];
		$returned = PUM_Utils_Array::remove_keys( $test, 'missing' );
		$this->assertCount( 1, $returned );
		$this->assertSame( 1, $returned['a'] );
	}

	/**
	 * Tests remove_keys removing all keys results in empty array.
	 */
	public function test_remove_keys_all() {
		$test     = [ 'a' => 1, 'b' => 2 ];
		$returned = PUM_Utils_Array::remove_keys( $test, [ 'a', 'b' ] );
		$this->assertEmpty( $returned );
	}
}
