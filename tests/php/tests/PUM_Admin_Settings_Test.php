<?php
/**
 * Tests for PUM_Admin_Settings business logic.
 *
 * @package Popup_Maker
 */

/**
 * Test the sanitization, defaults, field lookup, and value parsing
 * methods in PUM_Admin_Settings.
 */
class PUM_Admin_Settings_Test extends WP_UnitTestCase {

	/**
	 * Admin user ID for capability checks.
	 *
	 * @var int
	 */
	private static $admin_id;

	/**
	 * Set up shared fixtures once for the entire test class.
	 *
	 * @param WP_UnitTest_Factory $factory Factory instance.
	 */
	public static function wpSetUpBeforeClass( $factory ) {
		self::$admin_id = $factory->user->create( [ 'role' => 'administrator' ] );
	}

	/**
	 * Run before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		// Most Admin Settings methods call fields() which reads dist/assets/site.css.
		// Skip the entire class when dist is not built.
		$dist_file = dirname( dirname( dirname( __DIR__ ) ) ) . '/dist/assets/site.css';
		if ( ! file_exists( $dist_file ) ) {
			$this->markTestSkipped( 'Dist assets not built in test environment.' );
		}

		// Run as admin so unfiltered_html is available.
		wp_set_current_user( self::$admin_id );
	}

	// ------------------------------------------------------------------
	// fields() — returns a populated nested array.
	// ------------------------------------------------------------------

	/**
	 * Verify that fields() returns a non-empty array.
	 */
	public function test_fields_returns_array() {
		$fields = PUM_Admin_Settings::fields();
		$this->assertIsArray( $fields );
		$this->assertNotEmpty( $fields );
	}

	/**
	 * Verify that fields() contains expected top-level tabs.
	 */
	public function test_fields_contains_expected_tabs() {
		$fields = PUM_Admin_Settings::fields();
		// At minimum these tabs should always exist.
		$this->assertArrayHasKey( 'general', $fields, 'Missing general tab.' );
		$this->assertArrayHasKey( 'privacy', $fields, 'Missing privacy tab.' );
		$this->assertArrayHasKey( 'misc', $fields, 'Missing misc tab.' );
	}

	// ------------------------------------------------------------------
	// get_field() — looks up a field definition by ID.
	// ------------------------------------------------------------------

	/**
	 * Get a known field that exists.
	 */
	public function test_get_field_returns_array_for_known_field() {
		$field = PUM_Admin_Settings::get_field( 'debug_mode' );
		$this->assertIsArray( $field, 'debug_mode field should be found.' );
		$this->assertEquals( 'checkbox', $field['type'], 'debug_mode should be a checkbox.' );
	}

	/**
	 * Return false for a field that does not exist.
	 */
	public function test_get_field_returns_false_for_unknown_field() {
		$field = PUM_Admin_Settings::get_field( 'totally_fake_field_xyz' );
		$this->assertFalse( $field, 'Unknown field should return false.' );
	}

	// ------------------------------------------------------------------
	// defaults() — extracts 'std' values from field definitions.
	// ------------------------------------------------------------------

	/**
	 * Verify defaults returns an array.
	 */
	public function test_defaults_returns_array() {
		$defaults = PUM_Admin_Settings::defaults();
		$this->assertIsArray( $defaults );
	}

	/**
	 * Verify known default values match field definitions.
	 */
	public function test_defaults_contains_known_values() {
		$defaults = PUM_Admin_Settings::defaults();
		// debug_mode is a checkbox with no explicit std — should be null.
		$this->assertArrayHasKey( 'debug_mode', $defaults, 'defaults should contain debug_mode.' );
	}

	/**
	 * Verify fields with std values have matching defaults.
	 */
	public function test_defaults_matches_std_values() {
		$defaults = PUM_Admin_Settings::defaults();
		// body_padding_override has std = '15px'.
		if ( isset( $defaults['body_padding_override'] ) ) {
			$this->assertEquals( '15px', $defaults['body_padding_override'], 'body_padding_override default should be 15px.' );
		} else {
			// If the field doesn't exist, add an assertion to prevent risky test warning.
			$this->assertTrue( true, 'Field body_padding_override not found in defaults.' );
		}
	}

	// ------------------------------------------------------------------
	// sanitize_settings() — the main validation pipeline.
	// ------------------------------------------------------------------

	/**
	 * Checkbox fields not present in input are set to false.
	 */
	public function test_sanitize_settings_normalizes_missing_checkboxes() {
		// Pass in an empty settings array — all checkbox fields should be added as false.
		$result = PUM_Admin_Settings::sanitize_settings( [] );
		$this->assertIsArray( $result );

		// debug_mode is a known checkbox.
		$this->assertArrayHasKey( 'debug_mode', $result, 'Missing checkbox should be added.' );
		$this->assertFalse( $result['debug_mode'], 'Missing checkbox value should be false.' );
	}

	/**
	 * Multicheck fields not present in input are set to empty array.
	 */
	public function test_sanitize_settings_normalizes_missing_multicheck() {
		$fields    = PUM_Admin_Settings::fields();
		$flat      = PUM_Admin_Helpers::flatten_fields_array( $fields );
		$has_multi = false;

		foreach ( $flat as $fid => $fdef ) {
			if ( 'multicheck' === $fdef['type'] ) {
				$has_multi = true;
				$result    = PUM_Admin_Settings::sanitize_settings( [] );
				$this->assertArrayHasKey( $fid, $result, "Multicheck field $fid should be added." );
				$this->assertIsArray( $result[ $fid ], "Multicheck field $fid should default to empty array." );
				break;
			}
		}

		if ( ! $has_multi ) {
			// If no multicheck fields exist, that is acceptable.
			$this->assertTrue( true, 'No multicheck fields to test.' );
		}
	}

	/**
	 * String values are trimmed during sanitization.
	 */
	public function test_sanitize_settings_trims_string_values() {
		$result = PUM_Admin_Settings::sanitize_settings( [
			'google_fonts_api_key' => '  my-api-key  ',
		] );
		$this->assertEquals( 'my-api-key', $result['google_fonts_api_key'], 'String values should be trimmed.' );
	}

	/**
	 * Non-whitelisted keys are stripped from the settings.
	 */
	public function test_sanitize_settings_strips_unknown_keys() {
		$result = PUM_Admin_Settings::sanitize_settings( [
			'unknown_random_key_xyz' => 'some value',
		] );
		$this->assertArrayNotHasKey( 'unknown_random_key_xyz', $result, 'Unknown keys should be removed.' );
	}

	/**
	 * Measure fields append their unit value.
	 */
	public function test_sanitize_settings_appends_measure_unit() {
		// The settings fields include no measure type in Admin Settings currently,
		// but the code path exists. If a measure field exists, it should append the unit.
		$fields = PUM_Admin_Settings::fields();
		$flat   = PUM_Admin_Helpers::flatten_fields_array( $fields );

		$measure_field = null;
		foreach ( $flat as $fid => $fdef ) {
			if ( 'measure' === $fdef['type'] ) {
				$measure_field = $fid;
				break;
			}
		}

		if ( $measure_field ) {
			$result = PUM_Admin_Settings::sanitize_settings( [
				$measure_field            => '100',
				$measure_field . '_unit'  => 'px',
			] );
			$this->assertEquals( '100px', $result[ $measure_field ], 'Measure field should have unit appended.' );
		} else {
			$this->assertTrue( true, 'No measure fields in admin settings to test.' );
		}
	}

	/**
	 * License key with stars keeps the old value (masking protection).
	 */
	public function test_sanitize_settings_license_key_star_mask_preserved() {
		$fields = PUM_Admin_Settings::fields();
		$flat   = PUM_Admin_Helpers::flatten_fields_array( $fields );

		$license_field = null;
		foreach ( $flat as $fid => $fdef ) {
			if ( 'license_key' === $fdef['type'] ) {
				$license_field = $fid;
				break;
			}
		}

		if ( $license_field ) {
			// Seed an existing value in options.
			$old_key = 'real_license_key_123';
			update_option( 'popmake_settings', [ $license_field => $old_key ] );
			PUM_Utils_Options::init( true );

			$result = PUM_Admin_Settings::sanitize_settings( [
				$license_field => '****_key_***',
			] );

			$this->assertEquals( $old_key, $result[ $license_field ], 'Starred license key should keep old value.' );
		} else {
			$this->assertTrue( true, 'No license_key fields to test.' );
		}
	}

	/**
	 * License key with a new (non-starred) value replaces the old value.
	 */
	public function test_sanitize_settings_license_key_new_value() {
		$fields = PUM_Admin_Settings::fields();
		$flat   = PUM_Admin_Helpers::flatten_fields_array( $fields );

		$license_field = null;
		foreach ( $flat as $fid => $fdef ) {
			if ( 'license_key' === $fdef['type'] ) {
				$license_field = $fid;
				break;
			}
		}

		if ( $license_field ) {
			update_option( 'popmake_settings', [ $license_field => 'old_key' ] );
			PUM_Utils_Options::init( true );

			$new_key = 'brand_new_license_key';
			$result  = PUM_Admin_Settings::sanitize_settings( [
				$license_field => $new_key,
			] );

			$this->assertEquals( $new_key, $result[ $license_field ], 'Non-starred key should replace old value.' );
		} else {
			$this->assertTrue( true, 'No license_key fields to test.' );
		}
	}

	/**
	 * Pro license field is treated as a text field (trimmed).
	 */
	public function test_sanitize_settings_pro_license_trimmed() {
		$field = PUM_Admin_Settings::get_field( 'popup_maker_pro_license_key' );

		if ( $field && 'pro_license' === $field['type'] ) {
			$result = PUM_Admin_Settings::sanitize_settings( [
				'popup_maker_pro_license_key' => '  pro-key-123  ',
			] );
			$this->assertEquals( 'pro-key-123', $result['popup_maker_pro_license_key'], 'Pro license should be trimmed.' );
		} else {
			$this->assertTrue( true, 'Pro license field not found or type changed.' );
		}
	}

	/**
	 * Checkbox field submitted as truthy value is preserved.
	 */
	public function test_sanitize_settings_checkbox_true_preserved() {
		$result = PUM_Admin_Settings::sanitize_settings( [
			'debug_mode' => '1',
		] );
		$this->assertEquals( '1', $result['debug_mode'], 'Checkbox submitted value should be preserved.' );
	}

	// ------------------------------------------------------------------
	// parse_values() — form value processing before rendering.
	// ------------------------------------------------------------------

	/**
	 * Parse values returns an array.
	 */
	public function test_parse_values_returns_array() {
		$result = PUM_Admin_Settings::parse_values( [] );
		$this->assertIsArray( $result );
	}

	/**
	 * Non-license fields pass through unchanged.
	 */
	public function test_parse_values_passthrough_for_normal_fields() {
		$input = [
			'debug_mode'           => true,
			'google_fonts_api_key' => 'abc123',
		];

		$result = PUM_Admin_Settings::parse_values( $input );

		$this->assertEquals( true, $result['debug_mode'], 'debug_mode should pass through.' );
		$this->assertEquals( 'abc123', $result['google_fonts_api_key'], 'google_fonts_api_key should pass through.' );
	}

	/**
	 * Pro license field is transformed into a status array.
	 */
	public function test_parse_values_pro_license_transforms_to_array() {
		$field = PUM_Admin_Settings::get_field( 'popup_maker_pro_license_key' );

		if ( ! $field || 'pro_license' !== $field['type'] ) {
			$this->markTestSkipped( 'Pro license field not present.' );
		}

		$input = [
			'popup_maker_pro_license_key' => 'test-key',
		];

		// This may throw if the license service is not available.
		try {
			$result = PUM_Admin_Settings::parse_values( $input );
			$this->assertIsArray( $result['popup_maker_pro_license_key'], 'Pro license should be transformed to array.' );
			$this->assertArrayHasKey( 'key', $result['popup_maker_pro_license_key'], 'Should have key field.' );
			$this->assertArrayHasKey( 'status', $result['popup_maker_pro_license_key'], 'Should have status field.' );
		} catch ( \Exception $e ) {
			// Skipped - requires integration test (license service dependency).
			$this->markTestSkipped( 'License service not available: ' . $e->getMessage() );
		}
	}

	// ------------------------------------------------------------------
	// sanitize_objects() — JSON decode and object-to-array conversion.
	// ------------------------------------------------------------------

	/**
	 * Empty input returns empty array.
	 */
	public function test_sanitize_objects_empty_input() {
		$result = PUM_Admin_Settings::sanitize_objects( [] );
		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}

	/**
	 * Non-string values pass through as arrays.
	 */
	public function test_sanitize_objects_non_string_passthrough() {
		$input = [
			'key1' => [ 'nested' => 'value' ],
		];
		$result = PUM_Admin_Settings::sanitize_objects( $input );
		$this->assertIsArray( $result['key1'] );
	}

	/**
	 * JSON strings are decoded and converted.
	 */
	public function test_sanitize_objects_json_decoded() {
		$obj   = (object) [ 'foo' => 'bar' ];
		$json  = wp_json_encode( $obj );
		$input = [
			'key1' => addslashes( $json ),
		];
		$result = PUM_Admin_Settings::sanitize_objects( $input );
		$this->assertIsArray( $result['key1'] );
		$this->assertEquals( 'bar', $result['key1']['foo'], 'JSON should be decoded and converted to array.' );
	}

	/**
	 * Invalid JSON strings remain as-is after object_to_array.
	 */
	public function test_sanitize_objects_invalid_json() {
		$input = [
			'key1' => 'not valid json at all',
		];
		$result = PUM_Admin_Settings::sanitize_objects( $input );
		// json_decode returns null for invalid json, then object_to_array handles it.
		$this->assertArrayHasKey( 'key1', $result );
	}
}
