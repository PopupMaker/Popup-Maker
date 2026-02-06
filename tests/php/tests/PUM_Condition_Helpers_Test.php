<?php
/**
 * Tests for PopupMaker namespaced condition helper functions.
 *
 * @package Popup_Maker
 */

/**
 * Test PopupMaker\condition-helpers.php functions.
 */
class PUM_Condition_Helpers_Test extends WP_UnitTestCase {

	// ─── test_more_less_than ────────────────────────────────────────────

	/**
	 * Test value above more_than threshold passes.
	 */
	public function test_more_less_than_value_above_mt() {
		$this->assertTrue( \PopupMaker\test_more_less_than( 5, 3, false ) );
	}

	/**
	 * Test value below more_than threshold fails.
	 */
	public function test_more_less_than_value_below_mt() {
		$this->assertFalse( \PopupMaker\test_more_less_than( 2, 3, false ) );
	}

	/**
	 * Test value equal to more_than threshold fails (strict greater-than).
	 */
	public function test_more_less_than_value_equal_mt() {
		$this->assertFalse( \PopupMaker\test_more_less_than( 3, 3, false ) );
	}

	/**
	 * Test value below less_than threshold passes.
	 */
	public function test_more_less_than_value_below_lt() {
		$this->assertTrue( \PopupMaker\test_more_less_than( 5, false, 10 ) );
	}

	/**
	 * Test value above less_than threshold fails.
	 */
	public function test_more_less_than_value_above_lt() {
		$this->assertFalse( \PopupMaker\test_more_less_than( 15, false, 10 ) );
	}

	/**
	 * Test value equal to less_than threshold fails (strict less-than).
	 */
	public function test_more_less_than_value_equal_lt() {
		$this->assertFalse( \PopupMaker\test_more_less_than( 10, false, 10 ) );
	}

	/**
	 * Test value between both bounds passes.
	 */
	public function test_more_less_than_value_between_both() {
		$this->assertTrue( \PopupMaker\test_more_less_than( 5, 3, 10 ) );
	}

	/**
	 * Test value below more_than when both bounds set.
	 */
	public function test_more_less_than_value_below_mt_with_both() {
		$this->assertFalse( \PopupMaker\test_more_less_than( 1, 3, 10 ) );
	}

	/**
	 * Test value above less_than when both bounds set.
	 */
	public function test_more_less_than_value_above_lt_with_both() {
		$this->assertFalse( \PopupMaker\test_more_less_than( 15, 3, 10 ) );
	}

	/**
	 * Test that zero bounds return default (absint(0) = 0 = falsy).
	 */
	public function test_more_less_than_zero_bounds_return_default() {
		$this->assertFalse( \PopupMaker\test_more_less_than( 5, 0, 0 ) );
		$this->assertTrue( \PopupMaker\test_more_less_than( 5, 0, 0, true ) );
	}

	/**
	 * Test no bounds returns default value.
	 */
	public function test_more_less_than_no_bounds_return_default() {
		$this->assertFalse( \PopupMaker\test_more_less_than( 5, false, false ) );
		$this->assertTrue( \PopupMaker\test_more_less_than( 5, false, false, true ) );
	}

	// ─── test_list_matches ──────────────────────────────────────────────

	/**
	 * Test require_any with matching item passes.
	 */
	public function test_list_matches_any_found() {
		$this->assertTrue( \PopupMaker\test_list_matches( [ 1, 2, 3 ], [ 2 ], false ) );
	}

	/**
	 * Test require_any with no matching item fails.
	 */
	public function test_list_matches_any_not_found() {
		$this->assertFalse( \PopupMaker\test_list_matches( [ 1, 2, 3 ], [ 4 ], false ) );
	}

	/**
	 * Test require_all with all selected present passes.
	 */
	public function test_list_matches_all_present() {
		$this->assertTrue( \PopupMaker\test_list_matches( [ 1, 2, 3 ], [ 1, 2 ], true ) );
	}

	/**
	 * Test require_all with one selected missing fails.
	 */
	public function test_list_matches_all_one_missing() {
		$this->assertFalse( \PopupMaker\test_list_matches( [ 1, 2, 3 ], [ 1, 4 ], true ) );
	}

	/**
	 * Test require_all fails when selected exceeds items count.
	 */
	public function test_list_matches_all_selected_exceeds_items() {
		$this->assertFalse( \PopupMaker\test_list_matches( [ 1 ], [ 1, 2, 3 ], true ) );
	}

	/**
	 * Test empty selected always returns false.
	 */
	public function test_list_matches_empty_selected() {
		$this->assertFalse( \PopupMaker\test_list_matches( [ 1, 2, 3 ], [], false ) );
		$this->assertFalse( \PopupMaker\test_list_matches( [ 1, 2, 3 ], [], true ) );
	}

	/**
	 * Test empty items with non-empty selected returns false.
	 */
	public function test_list_matches_empty_items() {
		$this->assertFalse( \PopupMaker\test_list_matches( [], [ 1 ], false ) );
	}

	/**
	 * Test require_any with multiple matches finds first.
	 */
	public function test_list_matches_any_multiple_matches() {
		$this->assertTrue( \PopupMaker\test_list_matches( [ 1, 2, 3 ], [ 1, 2 ], false ) );
	}

	/**
	 * Test require_all with exact match passes.
	 */
	public function test_list_matches_all_exact_match() {
		$this->assertTrue( \PopupMaker\test_list_matches( [ 1, 2, 3 ], [ 1, 2, 3 ], true ) );
	}

	// ─── test_items_match ───────────────────────────────────────────────

	/**
	 * Test require_all with all items matching passes.
	 */
	public function test_items_match_all_true() {
		$result = \PopupMaker\test_items_match(
			[ 1, 2, 3 ],
			function () {
				return true;
			},
			true
		);
		$this->assertTrue( $result );
	}

	/**
	 * Test require_all with no items matching fails.
	 */
	public function test_items_match_all_false() {
		$result = \PopupMaker\test_items_match(
			[ 1, 2, 3 ],
			function () {
				return false;
			},
			true
		);
		$this->assertFalse( $result );
	}

	/**
	 * Test require_any with one item matching passes.
	 */
	public function test_items_match_any_partial() {
		$result = \PopupMaker\test_items_match(
			[ 1, 2, 3 ],
			function ( $item ) {
				// Only even numbers match.
				return 0 === $item % 2;
			},
			false
		);
		$this->assertTrue( $result );
	}

	/**
	 * Test require_all with partial match fails.
	 */
	public function test_items_match_all_partial_fails() {
		$result = \PopupMaker\test_items_match(
			[ 1, 2, 3 ],
			function ( $item ) {
				// Only even numbers match.
				return 0 === $item % 2;
			},
			true
		);
		$this->assertFalse( $result );
	}

	/**
	 * Test require_any with no items matching fails.
	 */
	public function test_items_match_any_none() {
		$result = \PopupMaker\test_items_match(
			[ 1, 3, 5 ],
			function ( $item ) {
				// Only even numbers match.
				return 0 === $item % 2;
			},
			false
		);
		$this->assertFalse( $result );
	}

	/**
	 * Test empty items returns false.
	 */
	public function test_items_match_empty_items() {
		$result = \PopupMaker\test_items_match(
			[],
			function () {
				return true;
			},
			false
		);
		$this->assertFalse( $result );
	}

	/**
	 * Test non-callable check_fn returns false.
	 */
	public function test_items_match_non_callable() {
		$result = \PopupMaker\test_items_match( [ 1, 2 ], 'not_a_real_function', false );
		$this->assertFalse( $result );
	}

	/**
	 * Test callback receives the correct item value.
	 */
	public function test_items_match_callback_receives_item() {
		$received = [];

		\PopupMaker\test_items_match(
			[ 'a', 'b', 'c' ],
			function ( $item ) use ( &$received ) {
				$received[] = $item;
				return false;
			},
			false
		);

		$this->assertEquals( [ 'a', 'b', 'c' ], $received );
	}

	// ─── Field config functions ─────────────────────────────────────────

	/**
	 * Test get_require_all_field returns checkbox config.
	 */
	public function test_get_require_all_field_structure() {
		$field = \PopupMaker\get_require_all_field();

		$this->assertIsArray( $field );
		$this->assertEquals( 'checkbox', $field['type'] );
		$this->assertArrayHasKey( 'label', $field );
	}

	/**
	 * Test get_morethan_field returns number config.
	 */
	public function test_get_morethan_field_structure() {
		$field = \PopupMaker\get_morethan_field();

		$this->assertIsArray( $field );
		$this->assertEquals( 'number', $field['type'] );
		$this->assertArrayHasKey( 'label', $field );
		$this->assertEquals( 0, $field['std'] );
	}

	/**
	 * Test get_lessthan_field returns number config.
	 */
	public function test_get_lessthan_field_structure() {
		$field = \PopupMaker\get_lessthan_field();

		$this->assertIsArray( $field );
		$this->assertEquals( 'number', $field['type'] );
		$this->assertArrayHasKey( 'label', $field );
		$this->assertEquals( 0, $field['std'] );
	}
}
