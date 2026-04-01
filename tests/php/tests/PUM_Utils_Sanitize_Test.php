<?php
/**
 * Tests for PUM_Utils_Sanitize.
 *
 * @package Popup_Maker
 */

/**
 * Test PUM_Utils_Sanitize methods.
 */
class PUM_Utils_Sanitize_Test extends WP_UnitTestCase {

	// ─── text() ────────────────────────────────────────────────────────

	/**
	 * Test sanitizing a normal string.
	 */
	public function test_text_normal_string() {
		$this->assertEquals( 'hello world', PUM_Utils_Sanitize::text( 'hello world' ) );
	}

	/**
	 * Test that HTML tags are stripped.
	 */
	public function test_text_strips_html() {
		$this->assertEquals( '', PUM_Utils_Sanitize::text( '<script>alert</script>' ) );
	}

	/**
	 * Test empty string returns empty.
	 */
	public function test_text_empty_string() {
		$this->assertEquals( '', PUM_Utils_Sanitize::text( '' ) );
	}

	/**
	 * Test default parameter returns empty string.
	 */
	public function test_text_default_parameter() {
		$this->assertEquals( '', PUM_Utils_Sanitize::text() );
	}

	/**
	 * Test that special characters are handled.
	 */
	public function test_text_special_characters() {
		$this->assertEquals( 'test & value', PUM_Utils_Sanitize::text( 'test & value' ) );
	}

	/**
	 * Test that octets are removed.
	 */
	public function test_text_strips_octets() {
		$this->assertEquals( 'test', PUM_Utils_Sanitize::text( 'test%0a' ) );
	}

	/**
	 * Test that extra whitespace is trimmed.
	 */
	public function test_text_trims_whitespace() {
		$this->assertEquals( 'hello world', PUM_Utils_Sanitize::text( '  hello world  ' ) );
	}

	/**
	 * Test that newlines are removed.
	 */
	public function test_text_removes_newlines() {
		$result = PUM_Utils_Sanitize::text( "hello\nworld" );
		$this->assertStringNotContainsString( "\n", $result );
	}

	/**
	 * Test args parameter is accepted without error.
	 */
	public function test_text_accepts_args_parameter() {
		$this->assertEquals( 'test', PUM_Utils_Sanitize::text( 'test', [ 'key' => 'val' ] ) );
	}

	// ─── checkbox() ────────────────────────────────────────────────────

	/**
	 * Test checkbox returns 1 for integer 1.
	 */
	public function test_checkbox_integer_one() {
		$this->assertSame( 1, PUM_Utils_Sanitize::checkbox( 1 ) );
	}

	/**
	 * Test checkbox returns 1 for string "1".
	 */
	public function test_checkbox_string_one() {
		$this->assertSame( 1, PUM_Utils_Sanitize::checkbox( '1' ) );
	}

	/**
	 * Test checkbox returns 0 for integer 0.
	 */
	public function test_checkbox_integer_zero() {
		$this->assertSame( 0, PUM_Utils_Sanitize::checkbox( 0 ) );
	}

	/**
	 * Test checkbox returns 0 for null.
	 */
	public function test_checkbox_null() {
		$this->assertSame( 0, PUM_Utils_Sanitize::checkbox( null ) );
	}

	/**
	 * Test checkbox default parameter returns 0.
	 */
	public function test_checkbox_default() {
		$this->assertSame( 0, PUM_Utils_Sanitize::checkbox() );
	}

	/**
	 * Test checkbox returns 0 for empty string.
	 */
	public function test_checkbox_empty_string() {
		$this->assertSame( 0, PUM_Utils_Sanitize::checkbox( '' ) );
	}

	/**
	 * Test checkbox returns 0 for string "yes".
	 */
	public function test_checkbox_string_yes() {
		// intval('yes') === 0, so this returns 0.
		$this->assertSame( 0, PUM_Utils_Sanitize::checkbox( 'yes' ) );
	}

	/**
	 * Test checkbox returns 0 for boolean true.
	 */
	public function test_checkbox_boolean_true() {
		// intval(true) === 1.
		$this->assertSame( 1, PUM_Utils_Sanitize::checkbox( true ) );
	}

	/**
	 * Test checkbox returns 0 for boolean false.
	 */
	public function test_checkbox_boolean_false() {
		$this->assertSame( 0, PUM_Utils_Sanitize::checkbox( false ) );
	}

	/**
	 * Test checkbox returns 0 for integer 2.
	 */
	public function test_checkbox_integer_two() {
		$this->assertSame( 0, PUM_Utils_Sanitize::checkbox( 2 ) );
	}

	/**
	 * Test checkbox returns 0 for negative value.
	 */
	public function test_checkbox_negative() {
		$this->assertSame( 0, PUM_Utils_Sanitize::checkbox( -1 ) );
	}

	/**
	 * Test checkbox accepts args parameter without error.
	 */
	public function test_checkbox_accepts_args() {
		$this->assertSame( 1, PUM_Utils_Sanitize::checkbox( 1, [ 'key' => 'val' ] ) );
	}

	// ─── measure() ─────────────────────────────────────────────────────

	/**
	 * Test measure with plain numeric value.
	 */
	public function test_measure_plain_value() {
		$this->assertEquals( '100', PUM_Utils_Sanitize::measure( '100' ) );
	}

	/**
	 * Test measure appends unit from values array.
	 */
	public function test_measure_appends_unit() {
		$result = PUM_Utils_Sanitize::measure(
			'50',
			[ 'id' => 'width' ],
			[],
			[ 'width_unit' => 'px' ]
		);
		$this->assertEquals( '50px', $result );
	}

	/**
	 * Test measure with percentage unit.
	 */
	public function test_measure_percentage_unit() {
		$result = PUM_Utils_Sanitize::measure(
			'75',
			[ 'id' => 'height' ],
			[],
			[ 'height_unit' => '%' ]
		);
		$this->assertEquals( '75%', $result );
	}

	/**
	 * Test measure with em unit.
	 */
	public function test_measure_em_unit() {
		$result = PUM_Utils_Sanitize::measure(
			'2',
			[ 'id' => 'font_size' ],
			[],
			[ 'font_size_unit' => 'em' ]
		);
		$this->assertEquals( '2em', $result );
	}

	/**
	 * Test measure without id in args does not append unit.
	 */
	public function test_measure_no_id_in_args() {
		$result = PUM_Utils_Sanitize::measure(
			'100',
			[],
			[],
			[ 'width_unit' => 'px' ]
		);
		$this->assertEquals( '100', $result );
	}

	/**
	 * Test measure with missing unit key in values.
	 */
	public function test_measure_missing_unit_in_values() {
		$result = PUM_Utils_Sanitize::measure(
			'100',
			[ 'id' => 'width' ],
			[],
			[]
		);
		$this->assertEquals( '100', $result );
	}

	/**
	 * Test measure empty value returns empty.
	 */
	public function test_measure_empty_value() {
		$this->assertEquals( '', PUM_Utils_Sanitize::measure( '' ) );
	}

	/**
	 * Test measure default returns empty.
	 */
	public function test_measure_default() {
		$this->assertEquals( '', PUM_Utils_Sanitize::measure() );
	}

	/**
	 * Test measure sanitizes HTML in value.
	 */
	public function test_measure_sanitizes_html() {
		$result = PUM_Utils_Sanitize::measure( '<b>100</b>' );
		$this->assertEquals( '100', $result );
	}

	/**
	 * Test return types are consistent.
	 */
	public function test_return_types() {
		$this->assertIsString( PUM_Utils_Sanitize::text( 'hello' ) );
		$this->assertIsInt( PUM_Utils_Sanitize::checkbox( 1 ) );
		$this->assertIsInt( PUM_Utils_Sanitize::checkbox( 0 ) );
		$this->assertIsString( PUM_Utils_Sanitize::measure( '10' ) );
	}
}
