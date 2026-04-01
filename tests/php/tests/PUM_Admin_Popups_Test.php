<?php
/**
 * Tests for PUM_Admin_Popups business logic.
 *
 * @package Popup_Maker
 */

/**
 * Test the sanitization, defaults, fill_missing_defaults,
 * handle_bulk_actions, and sort_columns methods in PUM_Admin_Popups.
 */
class PUM_Admin_Popups_Test extends WP_UnitTestCase {

	/**
	 * Admin user ID.
	 *
	 * @var int
	 */
	private static $admin_id;

	/**
	 * Set up shared fixtures once.
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
		wp_set_current_user( self::$admin_id );
	}

	// ------------------------------------------------------------------
	// fields() — returns the popup settings field definitions.
	// ------------------------------------------------------------------

	/**
	 * Verify that fields() returns a non-empty array with expected tabs.
	 */
	public function test_fields_returns_populated_array() {
		$fields = PUM_Admin_Popups::fields();
		$this->assertIsArray( $fields );
		$this->assertNotEmpty( $fields );
	}

	/**
	 * Verify expected popup setting tabs exist.
	 */
	public function test_fields_contains_expected_tabs() {
		$fields = PUM_Admin_Popups::fields();
		$expected_tabs = [ 'display', 'close', 'triggers', 'targeting', 'advanced' ];
		foreach ( $expected_tabs as $tab ) {
			$this->assertArrayHasKey( $tab, $fields, "Missing $tab tab." );
		}
	}

	// ------------------------------------------------------------------
	// get_field() — field lookup by ID.
	// ------------------------------------------------------------------

	/**
	 * Known field is found and has correct type.
	 */
	public function test_get_field_known_field() {
		$field = PUM_Admin_Popups::get_field( 'animation_type' );
		$this->assertIsArray( $field, 'animation_type should be found.' );
		$this->assertEquals( 'select', $field['type'], 'animation_type should be a select.' );
	}

	/**
	 * Unknown field returns false.
	 */
	public function test_get_field_unknown_returns_false() {
		$result = PUM_Admin_Popups::get_field( 'nonexistent_field_abc' );
		$this->assertFalse( $result );
	}

	/**
	 * Checkbox field is found correctly.
	 */
	public function test_get_field_checkbox_type() {
		$field = PUM_Admin_Popups::get_field( 'disable_on_mobile' );
		$this->assertIsArray( $field );
		$this->assertEquals( 'checkbox', $field['type'] );
	}

	/**
	 * Measure field is found correctly.
	 */
	public function test_get_field_measure_type() {
		$field = PUM_Admin_Popups::get_field( 'responsive_min_width' );
		$this->assertIsArray( $field );
		$this->assertEquals( 'measure', $field['type'] );
	}

	// ------------------------------------------------------------------
	// defaults() — extracts std values from popup field definitions.
	// ------------------------------------------------------------------

	/**
	 * Defaults returns a non-empty array.
	 */
	public function test_defaults_returns_array() {
		$defaults = PUM_Admin_Popups::defaults();
		$this->assertIsArray( $defaults );
		$this->assertNotEmpty( $defaults );
	}

	/**
	 * Known default values are present.
	 */
	public function test_defaults_contains_known_values() {
		$defaults = PUM_Admin_Popups::defaults();

		// animation_type has std = 'fade'.
		$this->assertArrayHasKey( 'animation_type', $defaults );
		$this->assertEquals( 'fade', $defaults['animation_type'], 'animation_type default should be fade.' );

		// size has std = 'medium'.
		$this->assertArrayHasKey( 'size', $defaults );
		$this->assertEquals( 'medium', $defaults['size'], 'size default should be medium.' );

		// animation_speed has std = 350.
		$this->assertArrayHasKey( 'animation_speed', $defaults );
		$this->assertEquals( 350, $defaults['animation_speed'], 'animation_speed default should be 350.' );
	}

	/**
	 * Checkbox fields default to false when no std is set.
	 */
	public function test_defaults_checkbox_without_std() {
		$defaults = PUM_Admin_Popups::defaults();

		// disable_on_mobile has no std value.
		$this->assertArrayHasKey( 'disable_on_mobile', $defaults );
		$this->assertFalse( $defaults['disable_on_mobile'], 'Checkbox without std should default to false.' );
	}

	/**
	 * Triggers default to empty array.
	 */
	public function test_defaults_triggers_empty_array() {
		$defaults = PUM_Admin_Popups::defaults();
		$this->assertArrayHasKey( 'triggers', $defaults );
		$this->assertEquals( [], $defaults['triggers'], 'triggers default should be empty array.' );
	}

	// ------------------------------------------------------------------
	// fill_missing_defaults() — smart default filling.
	// ------------------------------------------------------------------

	/**
	 * Empty settings get filled with defaults (except checkboxes/multicheck).
	 */
	public function test_fill_missing_defaults_adds_missing_values() {
		$result = PUM_Admin_Popups::fill_missing_defaults( [] );
		$this->assertIsArray( $result );

		// Non-checkbox fields should be filled.
		$this->assertArrayHasKey( 'animation_type', $result, 'animation_type should be filled.' );
		$this->assertEquals( 'fade', $result['animation_type'] );

		$this->assertArrayHasKey( 'size', $result, 'size should be filled.' );
		$this->assertEquals( 'medium', $result['size'] );
	}

	/**
	 * Checkbox fields are excluded from fill (unset = false by design).
	 */
	public function test_fill_missing_defaults_excludes_checkboxes() {
		$result = PUM_Admin_Popups::fill_missing_defaults( [] );

		// Checkboxes should NOT be filled in because their absence means false.
		$this->assertArrayNotHasKey( 'disable_on_mobile', $result, 'Checkbox fields should be excluded.' );
		$this->assertArrayNotHasKey( 'disable_on_tablet', $result, 'Checkbox fields should be excluded.' );
		$this->assertArrayNotHasKey( 'overlay_disabled', $result, 'Checkbox fields should be excluded.' );
	}

	/**
	 * Existing values are not overwritten.
	 */
	public function test_fill_missing_defaults_preserves_existing() {
		$input = [
			'animation_type' => 'slide',
			'size'           => 'large',
		];

		$result = PUM_Admin_Popups::fill_missing_defaults( $input );

		$this->assertEquals( 'slide', $result['animation_type'], 'Existing value should not be overwritten.' );
		$this->assertEquals( 'large', $result['size'], 'Existing value should not be overwritten.' );
	}

	/**
	 * Fields set to explicit falsy values (0, '', false) are preserved.
	 */
	public function test_fill_missing_defaults_preserves_falsy_values() {
		$input = [
			'animation_speed' => 0,
		];

		$result = PUM_Admin_Popups::fill_missing_defaults( $input );

		// isset(0) = true, so it should be preserved.
		$this->assertSame( 0, $result['animation_speed'], 'Falsy existing value should be preserved.' );
	}

	// ------------------------------------------------------------------
	// parse_values() — deprecated wrapper for defaults + fill.
	// ------------------------------------------------------------------

	/**
	 * Empty input returns defaults.
	 */
	public function test_parse_values_empty_returns_defaults() {
		$result = PUM_Admin_Popups::parse_values( [] );
		$defaults = PUM_Admin_Popups::defaults();
		$this->assertEquals( $defaults, $result, 'Empty parse_values should return defaults.' );
	}

	/**
	 * Non-empty input gets fill_missing_defaults applied.
	 */
	public function test_parse_values_fills_missing() {
		$input = [ 'size' => 'large' ];
		$result = PUM_Admin_Popups::parse_values( $input );

		$this->assertEquals( 'large', $result['size'] );
		// Missing non-checkbox fields should be filled.
		$this->assertArrayHasKey( 'animation_type', $result );
	}

	// ------------------------------------------------------------------
	// sanitize_settings() — popup settings validation.
	// ------------------------------------------------------------------

	/**
	 * Returns an array for empty input.
	 */
	public function test_sanitize_settings_returns_array() {
		$result = PUM_Admin_Popups::sanitize_settings( [] );
		$this->assertIsArray( $result );
	}

	/**
	 * Missing checkbox fields are added as false.
	 */
	public function test_sanitize_settings_adds_missing_checkboxes() {
		$result = PUM_Admin_Popups::sanitize_settings( [] );

		// Known checkbox fields should be added as false.
		$this->assertArrayHasKey( 'disable_on_mobile', $result );
		$this->assertFalse( $result['disable_on_mobile'], 'Missing checkbox should be false.' );

		$this->assertArrayHasKey( 'close_on_overlay_click', $result );
		$this->assertFalse( $result['close_on_overlay_click'], 'Missing checkbox should be false.' );
	}

	/**
	 * String values are sanitized and trimmed.
	 */
	public function test_sanitize_settings_trims_strings() {
		$result = PUM_Admin_Popups::sanitize_settings( [
			'close_text' => '  Close Me  ',
		] );
		$this->assertEquals( 'Close Me', $result['close_text'], 'String values should be trimmed.' );
	}

	/**
	 * Unknown keys are stripped from settings.
	 */
	public function test_sanitize_settings_strips_unknown_keys() {
		$result = PUM_Admin_Popups::sanitize_settings( [
			'bogus_key_xyz' => 'whatever',
		] );
		$this->assertArrayNotHasKey( 'bogus_key_xyz', $result, 'Unknown keys should be removed.' );
	}

	/**
	 * Measure fields append their unit value.
	 */
	public function test_sanitize_settings_measure_appends_unit() {
		$result = PUM_Admin_Popups::sanitize_settings( [
			'responsive_min_width'      => '50',
			'responsive_min_width_unit' => '%',
		] );

		$this->assertEquals( '50%', $result['responsive_min_width'], 'Measure should have unit appended.' );
	}

	/**
	 * Numeric values pass through correctly.
	 */
	public function test_sanitize_settings_numeric_passthrough() {
		$result = PUM_Admin_Popups::sanitize_settings( [
			'zindex' => 1999999999,
		] );
		$this->assertEquals( 1999999999, $result['zindex'], 'Numeric values should pass through.' );
	}

	/**
	 * Non-string non-whitelisted values are still stripped.
	 */
	public function test_sanitize_settings_strips_non_whitelisted_arrays() {
		$result = PUM_Admin_Popups::sanitize_settings( [
			'fake_array_field' => [ 'a', 'b' ],
		] );
		$this->assertArrayNotHasKey( 'fake_array_field', $result, 'Non-whitelisted array keys should be stripped.' );
	}

	// ------------------------------------------------------------------
	// sanitize_meta() — recursive JSON decode for triggers/conditions.
	// ------------------------------------------------------------------

	/**
	 * Empty input returns empty array.
	 */
	public function test_sanitize_meta_empty() {
		$result = PUM_Admin_Popups::sanitize_meta( [] );
		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}

	/**
	 * Nested arrays are recursively processed.
	 */
	public function test_sanitize_meta_recursive_arrays() {
		$input = [
			'level1' => [
				'level2' => 'plain string',
			],
		];
		$result = PUM_Admin_Popups::sanitize_meta( $input );
		$this->assertEquals( 'plain string', $result['level1']['level2'] );
	}

	/**
	 * JSON strings are decoded in meta.
	 */
	public function test_sanitize_meta_json_decoded() {
		$obj  = (object) [ 'type' => 'click_open', 'settings' => [ 'delay' => 0 ] ];
		$json = wp_json_encode( $obj );

		$input  = [ 0 => addslashes( $json ) ];
		$result = PUM_Admin_Popups::sanitize_meta( $input );

		$this->assertIsArray( $result[0] );
		$this->assertEquals( 'click_open', $result[0]['type'] );
	}

	/**
	 * Non-JSON strings remain in the meta array.
	 */
	public function test_sanitize_meta_plain_strings_unchanged() {
		$input  = [ 'key' => 'just a plain string' ];
		$result = PUM_Admin_Popups::sanitize_meta( $input );
		// json_decode('just a plain string') returns null (not object/array), so the value stays as-is.
		$this->assertEquals( 'just a plain string', $result['key'] );
	}

	// ------------------------------------------------------------------
	// handle_bulk_actions() — bulk enable/disable toggle logic.
	// ------------------------------------------------------------------

	/**
	 * Non-PUM actions return redirect URL unchanged.
	 */
	public function test_handle_bulk_actions_ignores_non_pum_actions() {
		$url    = 'https://example.com/wp-admin/edit.php';
		$result = PUM_Admin_Popups::handle_bulk_actions( $url, 'trash', [ 1, 2 ] );
		$this->assertEquals( $url, $result, 'Non-PUM action should return URL unchanged.' );
	}

	/**
	 * Enable action adds query args to redirect URL.
	 */
	public function test_handle_bulk_actions_enable_adds_query_args() {
		// Create a published popup.
		$popup_id = $this->factory->post->create( [
			'post_type'   => 'popup',
			'post_status' => 'publish',
		] );

		$url    = 'https://example.com/wp-admin/edit.php?post_type=popup';
		$result = PUM_Admin_Popups::handle_bulk_actions( $url, 'pum_enable', [ $popup_id ] );

		$this->assertStringContainsString( 'pum_bulk_action=pum_enable', $result );
		$this->assertStringContainsString( 'pum_bulk_count=', $result );
	}

	/**
	 * Disable action adds query args to redirect URL.
	 */
	public function test_handle_bulk_actions_disable_adds_query_args() {
		$popup_id = $this->factory->post->create( [
			'post_type'   => 'popup',
			'post_status' => 'publish',
		] );

		$url    = 'https://example.com/wp-admin/edit.php?post_type=popup';
		$result = PUM_Admin_Popups::handle_bulk_actions( $url, 'pum_disable', [ $popup_id ] );

		$this->assertStringContainsString( 'pum_bulk_action=pum_disable', $result );
	}

	/**
	 * Draft popups are skipped during bulk enable.
	 */
	public function test_handle_bulk_actions_skips_draft_popups() {
		$draft_id = $this->factory->post->create( [
			'post_type'   => 'popup',
			'post_status' => 'draft',
		] );

		$url    = 'https://example.com/wp-admin/edit.php?post_type=popup';
		$result = PUM_Admin_Popups::handle_bulk_actions( $url, 'pum_enable', [ $draft_id ] );

		// Count should be 0 and skipped should be 1.
		$this->assertStringContainsString( 'pum_bulk_count=0', $result, 'Draft popups should not be enabled.' );
		$this->assertStringContainsString( 'pum_bulk_skipped=1', $result, 'Draft popups should be counted as skipped.' );
	}

	/**
	 * Multiple popups are processed correctly.
	 */
	public function test_handle_bulk_actions_multiple_popups() {
		$pub1   = $this->factory->post->create( [ 'post_type' => 'popup', 'post_status' => 'publish' ] );
		$pub2   = $this->factory->post->create( [ 'post_type' => 'popup', 'post_status' => 'publish' ] );
		$draft1 = $this->factory->post->create( [ 'post_type' => 'popup', 'post_status' => 'draft' ] );

		$url    = 'https://example.com/wp-admin/edit.php?post_type=popup';
		$result = PUM_Admin_Popups::handle_bulk_actions( $url, 'pum_enable', [ $pub1, $pub2, $draft1 ] );

		$this->assertStringContainsString( 'pum_bulk_count=2', $result, 'Two published popups should be enabled.' );
		$this->assertStringContainsString( 'pum_bulk_skipped=1', $result, 'One draft should be skipped.' );
	}

	/**
	 * Empty post IDs results in zero counts.
	 */
	public function test_handle_bulk_actions_empty_post_ids() {
		$url    = 'https://example.com/wp-admin/edit.php?post_type=popup';
		$result = PUM_Admin_Popups::handle_bulk_actions( $url, 'pum_enable', [] );

		$this->assertStringContainsString( 'pum_bulk_count=0', $result );
		$this->assertStringContainsString( 'pum_bulk_skipped=0', $result );
	}

	// ------------------------------------------------------------------
	// sort_columns() — WP_Query orderby modification.
	// ------------------------------------------------------------------

	/**
	 * Non-popup post type is not modified.
	 */
	public function test_sort_columns_ignores_non_popup() {
		$vars = [
			'post_type' => 'post',
			'orderby'   => 'popup_title',
		];
		$result = PUM_Admin_Popups::sort_columns( $vars );
		$this->assertArrayNotHasKey( 'meta_key', $result, 'Non-popup type should not be modified.' );
	}

	/**
	 * Popup title sorting sets correct meta_key and orderby.
	 */
	public function test_sort_columns_popup_title() {
		$vars = [
			'post_type' => 'popup',
			'orderby'   => 'popup_title',
		];
		$result = PUM_Admin_Popups::sort_columns( $vars );

		$this->assertEquals( 'popup_title', $result['meta_key'], 'meta_key should be popup_title.' );
		$this->assertEquals( 'meta_value', $result['orderby'], 'orderby should be meta_value for text.' );
	}

	/**
	 * Enabled column sorting sets numeric orderby.
	 */
	public function test_sort_columns_enabled() {
		$vars = [
			'post_type' => 'popup',
			'orderby'   => 'enabled',
		];
		$result = PUM_Admin_Popups::sort_columns( $vars );

		$this->assertEquals( 'enabled', $result['meta_key'] );
		$this->assertEquals( 'meta_value_num', $result['orderby'], 'Enabled should sort numerically.' );
	}

	/**
	 * Views column sorting sets open count meta_key.
	 */
	public function test_sort_columns_views() {
		// This only activates when popup-analytics extension is NOT active.
		if ( function_exists( 'pum_extension_enabled' ) && pum_extension_enabled( 'popup-analytics' ) ) {
			$this->markTestSkipped( 'popup-analytics extension is active.' );
		}

		$vars = [
			'post_type' => 'popup',
			'orderby'   => 'views',
		];
		$result = PUM_Admin_Popups::sort_columns( $vars );

		$this->assertEquals( 'popup_open_count', $result['meta_key'] );
		$this->assertEquals( 'meta_value_num', $result['orderby'] );
	}

	/**
	 * Conversions column sorting sets conversion count meta_key.
	 */
	public function test_sort_columns_conversions() {
		if ( function_exists( 'pum_extension_enabled' ) && pum_extension_enabled( 'popup-analytics' ) ) {
			$this->markTestSkipped( 'popup-analytics extension is active.' );
		}

		$vars = [
			'post_type' => 'popup',
			'orderby'   => 'conversions',
		];
		$result = PUM_Admin_Popups::sort_columns( $vars );

		$this->assertEquals( 'popup_conversion_count', $result['meta_key'] );
		$this->assertEquals( 'meta_value_num', $result['orderby'] );
	}

	/**
	 * Without orderby set, vars pass through unchanged.
	 */
	public function test_sort_columns_no_orderby() {
		$vars = [
			'post_type' => 'popup',
		];
		$result = PUM_Admin_Popups::sort_columns( $vars );
		$this->assertArrayNotHasKey( 'meta_key', $result, 'No orderby should mean no meta_key added.' );
	}

	/**
	 * Unknown orderby value passes through without modification.
	 */
	public function test_sort_columns_unknown_orderby() {
		$vars = [
			'post_type' => 'popup',
			'orderby'   => 'random_unknown',
		];
		$result = PUM_Admin_Popups::sort_columns( $vars );
		$this->assertArrayNotHasKey( 'meta_key', $result, 'Unknown orderby should not add meta_key.' );
		$this->assertEquals( 'random_unknown', $result['orderby'], 'Unknown orderby value should be preserved.' );
	}

	// ------------------------------------------------------------------
	// register_bulk_actions() — adds enable/disable bulk actions.
	// ------------------------------------------------------------------

	/**
	 * Verify bulk actions are registered.
	 */
	public function test_register_bulk_actions() {
		$actions = PUM_Admin_Popups::register_bulk_actions( [] );
		$this->assertArrayHasKey( 'pum_enable', $actions, 'Enable action should be registered.' );
		$this->assertArrayHasKey( 'pum_disable', $actions, 'Disable action should be registered.' );
	}

	/**
	 * Existing bulk actions are preserved.
	 */
	public function test_register_bulk_actions_preserves_existing() {
		$existing = [ 'edit' => 'Edit', 'trash' => 'Move to Trash' ];
		$result   = PUM_Admin_Popups::register_bulk_actions( $existing );

		$this->assertArrayHasKey( 'edit', $result, 'Existing actions should be preserved.' );
		$this->assertArrayHasKey( 'trash', $result, 'Existing actions should be preserved.' );
		$this->assertArrayHasKey( 'pum_enable', $result );
		$this->assertArrayHasKey( 'pum_disable', $result );
	}

	// ------------------------------------------------------------------
	// sortable_columns() — registers sortable column definitions.
	// ------------------------------------------------------------------

	/**
	 * Expected columns are marked as sortable.
	 */
	public function test_sortable_columns() {
		$result = PUM_Admin_Popups::sortable_columns( [] );
		$this->assertArrayHasKey( 'popup_title', $result );
		$this->assertArrayHasKey( 'enabled', $result );
		$this->assertArrayHasKey( 'views', $result );
		$this->assertArrayHasKey( 'conversions', $result );
	}
}
