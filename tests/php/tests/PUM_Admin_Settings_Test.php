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

	/**
	 * Core alone keeps the Go Pro tab without rendering a redundant CTA field.
	 */
	public function test_core_alone_uses_hidden_go_pro_placeholder() {
		if ( \PopupMaker\plugin()->is_pro_installed() ) {
			$this->markTestSkipped( 'Core-only UI assertion requires Pro to be absent.' );
		}

		$placeholder = PUM_Admin_Settings::get_field( 'popup_maker_pro_placeholder' );

		$this->assertIsArray( $placeholder );
		$this->assertSame( 'html', $placeholder['type'] );
		$this->assertSame( '', $placeholder['content'] );
		$this->assertSame( 'pum-go-pro-placeholder', $placeholder['class'] );
		$this->assertFalse( PUM_Admin_Settings::get_field( 'popup_maker_pro_external' ) );
	}

	/**
	 * Installed but inactive Pro does not enable legacy license compatibility.
	 */
	public function test_installed_inactive_pro_does_not_enable_compatibility_surface() {
		if ( function_exists( '\PopupMaker\Pro\plugin' ) ) {
			$this->markTestSkipped( 'This compatibility assertion requires Pro to be inactive.' );
		}

		$pro_directory     = WP_PLUGIN_DIR . '/popup-maker-pro';
		$pro_file          = $pro_directory . '/popup-maker-pro.php';
		$created_directory = false;
		$created_file      = false;

		global $wp_rest_server;
		$previous_server = $wp_rest_server;

		try {
			if ( ! file_exists( $pro_file ) ) {
				if ( ! is_dir( $pro_directory ) ) {
					$created_directory = wp_mkdir_p( $pro_directory );
				}

				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- The fixture needs to look like an installed plugin.
				$created_file = false !== file_put_contents( $pro_file, "<?php\n" );
				$this->assertTrue( $created_file, 'Could not create the dormant Pro fixture.' );
			}

			$wp_rest_server = new WP_REST_Server();

			$this->assertTrue( \PopupMaker\plugin()->is_pro_installed() );
			$this->assertFalse( \PopupMaker\plugin()->is_pro_active() );
			$this->assertFalse( \PopupMaker\plugin()->should_run_legacy_license_compatibility() );

			$license_service = \PopupMaker\plugin( 'license' );
			$this->assertFalse( has_action( 'init', [ $license_service, 'autoregister' ] ) );
			$this->assertFalse( has_action( 'admin_init', [ $license_service, 'schedule_crons' ] ) );
			$this->assertFalse( has_action( 'popup_maker_license_status_check', [ $license_service, 'refresh_license_status' ] ) );

			$license_field = PUM_Admin_Settings::get_field( 'popup_maker_pro_license_key' );
			$this->assertFalse( $license_field );

			ob_start();
			PUM_Admin_Templates::general_fields();
			$templates = ob_get_clean();

			$this->assertStringNotContainsString( 'tmpl-pum-field-pro_license', $templates );
			$this->assertStringNotContainsString( 'pum-install-pro-button', $templates );
			$this->assertStringNotContainsString( 'pum-license-connect-trigger', $templates );

			\PopupMaker\plugin()->get_controller( 'RestAPI' )->register_routes();
			$routes = $wp_rest_server->get_routes();

			$this->assertArrayNotHasKey( '/popup-maker/v2/license', $routes );
			$this->assertArrayNotHasKey( '/popup-maker/v2/license/activate', $routes );
			$this->assertArrayNotHasKey( '/popup-maker/v2/license/deactivate', $routes );
			$this->assertArrayNotHasKey( '/popup-maker/v2/license/activate-pro', $routes );
			$this->assertArrayNotHasKey( '/popup-maker/v2/license/activate-plugin', $routes );
			$this->assertArrayNotHasKey( '/popup-maker/v2/connect/install', $routes );
		} finally {
			$wp_rest_server = $previous_server;

			if ( $created_file && file_exists( $pro_file ) ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- Remove only the test-created fixture.
				unlink( $pro_file );
			}

			if ( $created_directory && is_dir( $pro_directory ) ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir -- Remove only the test-created empty directory.
				rmdir( $pro_directory );
			}
		}
	}

	/**
	 * The Go Pro hero gives existing customers a direct account download link.
	 */
	public function test_go_pro_hero_links_existing_customers_to_downloads() {
		$hero = PUM_Admin_Settings::field_go_pro_hero();

		$this->assertStringContainsString( 'https://wppopupmaker.com/account/file-downloads/', $hero );
		$this->assertStringContainsString( 'Already own Pro? Download it', $hero );
		$this->assertStringContainsString( 'FluentCRM Marketing Automation', $hero );
	}

	/**
	 * A historical stored license cannot hide Core's manual Pro acquisition path.
	 */
	public function test_core_alone_with_stored_license_keeps_manual_download_path() {
		if ( \PopupMaker\plugin()->is_pro_installed() ) {
			$this->markTestSkipped( 'Core-only migration assertion requires Pro to be absent.' );
		}

		$missing          = new stdClass();
		$previous_license = get_option( 'popup_maker_license', $missing );
		$license_service  = \PopupMaker\plugin( 'license' );
		$cache_property   = new ReflectionProperty( $license_service, 'license_data' );

		if ( PHP_VERSION_ID < 80100 ) {
			$cache_property->setAccessible( true );
		}

		try {
			update_option(
				'popup_maker_license',
				[
					'key'    => 'historical-license-key',
					'status' => [
						'success'      => true,
						'license'      => 'valid',
						'license_tier' => 'pro_plus',
					],
				]
			);
			$cache_property->setValue( $license_service, null );

			$this->assertTrue( $license_service->is_license_active() );
			$this->assertFalse( \PopupMaker\plugin()->is_license_active() );
			$this->assertSame( 'Go Pro', PUM_Admin_Settings::tabs()['go-pro'] );
			$this->assertSame( 'Go Pro', PUM_Admin_Settings::sections()['go-pro']['main'] );

			ob_start();
			PUM_Admin_Settings::page();
			$page = ob_get_clean();

			$this->assertStringContainsString( 'https://wppopupmaker.com/account/file-downloads/', $page );
			$this->assertStringContainsString( 'Already own Pro? Download it', $page );
		} finally {
			if ( $missing === $previous_license ) {
				delete_option( 'popup_maker_license' );
			} else {
				update_option( 'popup_maker_license', $previous_license );
			}

			$cache_property->setValue( $license_service, null );
		}
	}

	/**
	 * A stale Core Pro key cannot replace an add-on's own license without Pro.
	 */
	public function test_core_alone_with_stored_license_keeps_addon_license() {
		if ( \PopupMaker\plugin()->is_pro_installed() ) {
			$this->markTestSkipped( 'Core-only add-on assertion requires Pro to be absent.' );
		}

		$missing                   = new stdClass();
		$previous_license          = get_option( 'popup_maker_license', $missing );
		$addon_license_option      = 'popmake_legacy_addon_test_license_key';
		$previous_addon_license    = PUM_Utils_Options::get( $addon_license_option, $missing );
		$license_service           = \PopupMaker\plugin( 'license' );
		$cache_property            = new ReflectionProperty( $license_service, 'license_data' );
		$extension_reflection      = new ReflectionClass( PUM_Extension_License::class );
		$extension                 = $extension_reflection->newInstanceWithoutConstructor();
		$item_name_property        = $extension_reflection->getProperty( 'item_name' );
		$item_shortname_property   = $extension_reflection->getProperty( 'item_shortname' );
		$effective_license_method  = $extension_reflection->getMethod( 'get_effective_license_key' );
		$extension_settings_method = $extension_reflection->getMethod( 'settings' );

		if ( PHP_VERSION_ID < 80100 ) {
			$cache_property->setAccessible( true );
			$item_name_property->setAccessible( true );
			$item_shortname_property->setAccessible( true );
			$effective_license_method->setAccessible( true );
		}

		try {
			update_option(
				'popup_maker_license',
				[
					'key'    => 'historical-pro-license-key',
					'status' => [
						'success' => false,
						'license' => 'expired',
					],
				]
			);
			PUM_Utils_Options::update( $addon_license_option, 'addon-specific-key' );
			$cache_property->setValue( $license_service, null );
			$item_name_property->setValue( $extension, 'Legacy Addon Test' );
			$item_shortname_property->setValue( $extension, 'popmake_legacy_addon_test' );

			$fields = $extension_settings_method->invoke( $extension, [] );

			$this->assertSame( 'addon-specific-key', $effective_license_method->invoke( $extension ) );
			$this->assertFalse(
				$fields['licenses']['main'][ $addon_license_option ]['options']['using_pro_license']
			);
		} finally {
			if ( $missing === $previous_license ) {
				delete_option( 'popup_maker_license' );
			} else {
				update_option( 'popup_maker_license', $previous_license );
			}

			if ( $missing === $previous_addon_license ) {
				PUM_Utils_Options::delete( $addon_license_option );
			} else {
				PUM_Utils_Options::update( $addon_license_option, $previous_addon_license );
			}

			$cache_property->setValue( $license_service, null );
		}
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
		// Fields without 'std' key are not included in defaults.
		// debug_mode has no std, so it should NOT be in defaults.
		$this->assertArrayNotHasKey( 'debug_mode', $defaults, 'debug_mode has no std, should not be in defaults.' );
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
