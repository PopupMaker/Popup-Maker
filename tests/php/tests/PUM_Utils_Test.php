<?php
/**
 * Tests for PopupMaker namespaced utility functions.
 *
 * @package Popup_Maker
 */

/**
 * Test PopupMaker\utils.php functions.
 */
class PUM_Utils_Test extends WP_UnitTestCase {

	// ─── camel_case_to_snake_case ───────────────────────────────────────

	/**
	 * Test basic camelCase conversion.
	 */
	public function test_camel_case_to_snake_case_basic() {
		$this->assertEquals( 'hello_world', \PopupMaker\camel_case_to_snake_case( 'helloWorld' ) );
	}

	/**
	 * Test single word stays lowercase.
	 */
	public function test_camel_case_to_snake_case_single_word() {
		$this->assertEquals( 'hello', \PopupMaker\camel_case_to_snake_case( 'hello' ) );
	}

	/**
	 * Test multiple humps produce correct underscores.
	 */
	public function test_camel_case_to_snake_case_multiple_humps() {
		$this->assertEquals( 'my_long_variable_name', \PopupMaker\camel_case_to_snake_case( 'myLongVariableName' ) );
	}

	/**
	 * Test leading uppercase is lowered without extra underscore.
	 */
	public function test_camel_case_to_snake_case_leading_uppercase() {
		$this->assertEquals( 'hello_world', \PopupMaker\camel_case_to_snake_case( 'HelloWorld' ) );
	}

	/**
	 * Test empty string returns empty.
	 */
	public function test_camel_case_to_snake_case_empty_string() {
		$this->assertEquals( '', \PopupMaker\camel_case_to_snake_case( '' ) );
	}

	/**
	 * Test already snake_case stays the same.
	 */
	public function test_camel_case_to_snake_case_already_snake() {
		$this->assertEquals( 'already_snake', \PopupMaker\camel_case_to_snake_case( 'already_snake' ) );
	}

	// ─── snake_case_to_camel_case ───────────────────────────────────────

	/**
	 * Test basic snake_case conversion.
	 */
	public function test_snake_case_to_camel_case_basic() {
		$this->assertEquals( 'helloWorld', \PopupMaker\snake_case_to_camel_case( 'hello_world' ) );
	}

	/**
	 * Test single word stays the same.
	 */
	public function test_snake_case_to_camel_case_single_word() {
		$this->assertEquals( 'hello', \PopupMaker\snake_case_to_camel_case( 'hello' ) );
	}

	/**
	 * Test multiple underscores produce correct humps.
	 */
	public function test_snake_case_to_camel_case_multiple_underscores() {
		$this->assertEquals( 'myLongVariableName', \PopupMaker\snake_case_to_camel_case( 'my_long_variable_name' ) );
	}

	/**
	 * Test empty string returns empty.
	 */
	public function test_snake_case_to_camel_case_empty_string() {
		$this->assertEquals( '', \PopupMaker\snake_case_to_camel_case( '' ) );
	}

	/**
	 * Test round-trip from camel to snake and back.
	 */
	public function test_case_conversion_round_trip() {
		$original = 'myTestVariable';
		$snake    = \PopupMaker\camel_case_to_snake_case( $original );
		$camel    = \PopupMaker\snake_case_to_camel_case( $snake );
		$this->assertEquals( $original, $camel );
	}

	// ─── fetch_key_from_array ───────────────────────────────────────────

	/**
	 * Test simple flat key lookup.
	 */
	public function test_fetch_key_from_array_simple() {
		$data = [ 'foo' => 'bar' ];
		$this->assertEquals( 'bar', \PopupMaker\fetch_key_from_array( 'foo', $data ) );
	}

	/**
	 * Test dot-notation traversal.
	 */
	public function test_fetch_key_from_array_dot_notation() {
		$data = [
			'level1' => [
				'level2' => 'deep_value',
			],
		];
		$this->assertEquals( 'deep_value', \PopupMaker\fetch_key_from_array( 'level1.level2', $data ) );
	}

	/**
	 * Test missing key returns null.
	 */
	public function test_fetch_key_from_array_missing_key() {
		$data = [ 'foo' => 'bar' ];
		$this->assertNull( \PopupMaker\fetch_key_from_array( 'missing', $data ) );
	}

	/**
	 * Test missing nested key returns null.
	 */
	public function test_fetch_key_from_array_missing_nested_key() {
		$data = [ 'foo' => [ 'bar' => 'baz' ] ];
		$this->assertNull( \PopupMaker\fetch_key_from_array( 'foo.missing', $data ) );
	}

	/**
	 * Test snake_case key_case conversion.
	 */
	public function test_fetch_key_from_array_snake_case_conversion() {
		$data = [ 'hello_world' => 'found' ];
		$this->assertEquals( 'found', \PopupMaker\fetch_key_from_array( 'helloWorld', $data, 'snake_case' ) );
	}

	/**
	 * Test camelCase key_case conversion.
	 */
	public function test_fetch_key_from_array_camel_case_conversion() {
		$data = [ 'helloWorld' => 'found' ];
		$this->assertEquals( 'found', \PopupMaker\fetch_key_from_array( 'hello_world', $data, 'camelCase' ) );
	}

	/**
	 * Test that falsy value (0, empty string) returns null due to loose check.
	 */
	public function test_fetch_key_from_array_falsy_value_returns_null() {
		// The function uses `$data ? $data : null` so falsy values return null.
		$data = [ 'zero' => 0 ];
		$this->assertNull( \PopupMaker\fetch_key_from_array( 'zero', $data ) );

		$data_empty = [ 'empty' => '' ];
		$this->assertNull( \PopupMaker\fetch_key_from_array( 'empty', $data_empty ) );
	}

	// ─── generate_uuid ──────────────────────────────────────────────────

	/**
	 * Test UUID returns a non-empty string.
	 */
	public function test_generate_uuid_returns_string() {
		$uuid = \PopupMaker\generate_uuid();
		$this->assertIsString( $uuid );
		$this->assertNotEmpty( $uuid );
	}

	/**
	 * Test UUID with prefix starts with the prefix.
	 */
	public function test_generate_uuid_with_prefix() {
		$uuid = \PopupMaker\generate_uuid( 'pum_' );
		$this->assertStringStartsWith( 'pum_', $uuid );
	}

	/**
	 * Test two UUIDs are unique.
	 */
	public function test_generate_uuid_uniqueness() {
		$uuid1 = \PopupMaker\generate_uuid();
		// Tiny sleep to ensure microtime differs.
		usleep( 1000 );
		$uuid2 = \PopupMaker\generate_uuid();
		$this->assertNotEquals( $uuid1, $uuid2 );
	}

	/**
	 * Test custom random length affects output length.
	 */
	public function test_generate_uuid_custom_random_length() {
		$short = \PopupMaker\generate_uuid( '', 2 );
		$long  = \PopupMaker\generate_uuid( '', 10 );
		// Longer random_length should produce a longer string.
		$this->assertGreaterThan( strlen( $short ), strlen( $long ) );
	}

	/**
	 * Test UUID contains only URL-safe characters.
	 */
	public function test_generate_uuid_url_safe_chars() {
		$uuid = \PopupMaker\generate_uuid();
		$this->assertMatchesRegularExpression( '/^[a-zA-Z0-9]+$/', $uuid );
	}
}
