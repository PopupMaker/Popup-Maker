<?php
/**
 * Tests for PUM_Utils_Fields.
 *
 * @package Popup_Maker
 */

/**
 * Test PUM_Utils_Fields methods.
 */
class PUM_Utils_Fields_Test extends WP_UnitTestCase {

	// ─── is_field() ────────────────────────────────────────────────────

	/**
	 * Test array with type key is a field.
	 */
	public function test_is_field_with_type() {
		$this->assertTrue( PUM_Utils_Fields::is_field( [ 'type' => 'text' ] ) );
	}

	/**
	 * Test array with label but no type is a field.
	 */
	public function test_is_field_with_label_no_type() {
		$this->assertTrue( PUM_Utils_Fields::is_field( [ 'label' => 'Name' ] ) );
	}

	/**
	 * Test array with desc but no type is a field.
	 */
	public function test_is_field_with_desc_no_type() {
		$this->assertTrue( PUM_Utils_Fields::is_field( [ 'desc' => 'Description' ] ) );
	}

	/**
	 * Test array with type and label is a field.
	 */
	public function test_is_field_with_type_and_label() {
		$this->assertTrue( PUM_Utils_Fields::is_field( [
			'type'  => 'select',
			'label' => 'Choose',
		] ) );
	}

	/**
	 * Test empty array is not a field.
	 */
	public function test_is_field_empty_array() {
		$this->assertFalse( PUM_Utils_Fields::is_field( [] ) );
	}

	/**
	 * Test no arguments defaults to not a field.
	 */
	public function test_is_field_default() {
		$this->assertFalse( PUM_Utils_Fields::is_field() );
	}

	/**
	 * Test nested sections are not fields.
	 */
	public function test_is_field_nested_section() {
		$section = [
			'field_one' => [ 'type' => 'text', 'label' => 'One' ],
			'field_two' => [ 'type' => 'number', 'label' => 'Two' ],
		];
		$this->assertFalse( PUM_Utils_Fields::is_field( $section ) );
	}

	/**
	 * Test type must be string to be considered a field.
	 */
	public function test_is_field_type_non_string() {
		$this->assertFalse( PUM_Utils_Fields::is_field( [ 'type' => 123 ] ) );
	}

	// ─── is_section() ──────────────────────────────────────────────────

	/**
	 * Test section is the inverse of field.
	 */
	public function test_is_section_true() {
		$this->assertTrue( PUM_Utils_Fields::is_section( [] ) );
	}

	/**
	 * Test field array is not a section.
	 */
	public function test_is_section_false_for_field() {
		$this->assertFalse( PUM_Utils_Fields::is_section( [ 'type' => 'text' ] ) );
	}

	/**
	 * Test nested fields array is a section.
	 */
	public function test_is_section_with_nested_fields() {
		$section = [
			'name'  => [ 'type' => 'text' ],
			'email' => [ 'type' => 'email' ],
		];
		$this->assertTrue( PUM_Utils_Fields::is_section( $section ) );
	}

	// ─── parse_field() ─────────────────────────────────────────────────

	/**
	 * Test parse_field fills defaults.
	 */
	public function test_parse_field_fills_defaults() {
		$result = PUM_Utils_Fields::parse_field( [ 'type' => 'text' ] );

		$this->assertEquals( 'text', $result['type'] );
		$this->assertEquals( 'main', $result['section'] );
		$this->assertEquals( '', $result['label'] );
		$this->assertEquals( '', $result['desc'] );
		$this->assertNull( $result['id'] );
		$this->assertNull( $result['std'] );
		$this->assertEquals( 'regular', $result['size'] );
		$this->assertIsArray( $result['options'] );
		$this->assertEquals( 5, $result['rows'] );
		$this->assertEquals( 50, $result['cols'] );
		$this->assertEquals( 0, $result['min'] );
		$this->assertEquals( 50, $result['max'] );
		$this->assertFalse( $result['force_minmax'] );
		$this->assertEquals( 1, $result['step'] );
		$this->assertFalse( $result['readonly'] );
		$this->assertFalse( $result['required'] );
		$this->assertFalse( $result['disabled'] );
		$this->assertEquals( 10, $result['priority'] );
		$this->assertFalse( $result['private'] );
	}

	/**
	 * Test parse_field preserves provided values.
	 */
	public function test_parse_field_preserves_values() {
		$field = [
			'type'     => 'number',
			'label'    => 'Age',
			'min'      => 18,
			'max'      => 120,
			'required' => true,
		];
		$result = PUM_Utils_Fields::parse_field( $field );

		$this->assertEquals( 'number', $result['type'] );
		$this->assertEquals( 'Age', $result['label'] );
		$this->assertEquals( 18, $result['min'] );
		$this->assertEquals( 120, $result['max'] );
		$this->assertTrue( $result['required'] );
	}

	/**
	 * Test parse_field includes units map.
	 */
	public function test_parse_field_default_units() {
		$result = PUM_Utils_Fields::parse_field( [] );
		$this->assertArrayHasKey( 'px', $result['units'] );
		$this->assertArrayHasKey( '%', $result['units'] );
		$this->assertArrayHasKey( 'em', $result['units'] );
		$this->assertArrayHasKey( 'rem', $result['units'] );
	}

	/**
	 * Test parse_field default type is text.
	 */
	public function test_parse_field_default_type() {
		$result = PUM_Utils_Fields::parse_field( [] );
		$this->assertEquals( 'text', $result['type'] );
	}

	// ─── flatten_fields_array() ────────────────────────────────────────

	/**
	 * Test flat fields stay flat.
	 */
	public function test_flatten_already_flat() {
		$fields = [
			'name'  => [ 'type' => 'text', 'label' => 'Name' ],
			'email' => [ 'type' => 'email', 'label' => 'Email' ],
		];

		$result = PUM_Utils_Fields::flatten_fields_array( $fields );

		$this->assertArrayHasKey( 'name', $result );
		$this->assertArrayHasKey( 'email', $result );
		$this->assertCount( 2, $result );
	}

	/**
	 * Test two-level nesting (tabs > fields).
	 */
	public function test_flatten_two_levels() {
		$fields = [
			'general' => [
				'name'  => [ 'type' => 'text', 'label' => 'Name' ],
				'email' => [ 'type' => 'email', 'label' => 'Email' ],
			],
		];

		$result = PUM_Utils_Fields::flatten_fields_array( $fields );

		$this->assertArrayHasKey( 'name', $result );
		$this->assertArrayHasKey( 'email', $result );
		$this->assertCount( 2, $result );
	}

	/**
	 * Test three-level nesting (tabs > sections > fields).
	 */
	public function test_flatten_three_levels() {
		$fields = [
			'display' => [
				'main' => [
					'width'  => [ 'type' => 'number', 'label' => 'Width' ],
					'height' => [ 'type' => 'number', 'label' => 'Height' ],
				],
			],
		];

		$result = PUM_Utils_Fields::flatten_fields_array( $fields );

		$this->assertArrayHasKey( 'width', $result );
		$this->assertArrayHasKey( 'height', $result );
		$this->assertCount( 2, $result );
	}

	/**
	 * Test mixed nesting levels.
	 */
	public function test_flatten_mixed_nesting() {
		$fields = [
			// Top-level field.
			'top_field' => [ 'type' => 'text', 'label' => 'Top' ],
			// Tab with direct fields.
			'general'   => [
				'name' => [ 'type' => 'text', 'label' => 'Name' ],
			],
		];

		$result = PUM_Utils_Fields::flatten_fields_array( $fields );

		$this->assertArrayHasKey( 'top_field', $result );
		$this->assertArrayHasKey( 'name', $result );
	}

	/**
	 * Test empty array returns empty.
	 */
	public function test_flatten_empty() {
		$result = PUM_Utils_Fields::flatten_fields_array( [] );
		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}

	// ─── get_field() ───────────────────────────────────────────────────

	/**
	 * Test getting a field from flat structure.
	 */
	public function test_get_field_flat() {
		$fields = [
			'name' => [ 'type' => 'text', 'label' => 'Name' ],
		];

		$result = PUM_Utils_Fields::get_field( $fields, 'name' );

		$this->assertIsArray( $result );
		$this->assertEquals( 'text', $result['type'] );
	}

	/**
	 * Test getting a field from nested structure.
	 */
	public function test_get_field_nested() {
		$fields = [
			'general' => [
				'name' => [ 'type' => 'text', 'label' => 'Name' ],
			],
		];

		$result = PUM_Utils_Fields::get_field( $fields, 'name' );

		$this->assertIsArray( $result );
		$this->assertEquals( 'text', $result['type'] );
	}

	/**
	 * Test getting nonexistent field returns false.
	 */
	public function test_get_field_not_found() {
		$fields = [
			'name' => [ 'type' => 'text', 'label' => 'Name' ],
		];

		$this->assertFalse( PUM_Utils_Fields::get_field( $fields, 'nonexistent' ) );
	}

	/**
	 * Test getting field from empty array returns false.
	 */
	public function test_get_field_empty_array() {
		$this->assertFalse( PUM_Utils_Fields::get_field( [], 'field' ) );
	}

	// ─── get_field_default_values() ────────────────────────────────────

	/**
	 * Test extracting defaults from flat fields.
	 */
	public function test_get_field_default_values() {
		$fields = [
			'name'    => [ 'type' => 'text', 'std' => 'John' ],
			'age'     => [ 'type' => 'number', 'std' => 25 ],
			'agree'   => [ 'type' => 'checkbox', 'std' => 1 ],
			'no_std'  => [ 'type' => 'text' ],
		];

		$result = PUM_Utils_Fields::get_field_default_values( $fields );

		$this->assertEquals( 'John', $result['name'] );
		$this->assertEquals( 25, $result['age'] );
		$this->assertEquals( 1, $result['agree'] );
		$this->assertNull( $result['no_std'] );
	}

	/**
	 * Test checkbox with empty std defaults to false.
	 */
	public function test_get_field_default_values_checkbox_empty_std() {
		$fields = [
			'check' => [ 'type' => 'checkbox' ],
		];

		$result = PUM_Utils_Fields::get_field_default_values( $fields );

		$this->assertFalse( $result['check'] );
	}

	/**
	 * Test checkbox with falsy std defaults to false.
	 */
	public function test_get_field_default_values_checkbox_falsy_std() {
		$fields = [
			'check' => [ 'type' => 'checkbox', 'std' => 0 ],
		];

		$result = PUM_Utils_Fields::get_field_default_values( $fields );

		// empty(0) is true, so returns false.
		$this->assertFalse( $result['check'] );
	}

	/**
	 * Test empty fields returns empty array.
	 */
	public function test_get_field_default_values_empty() {
		$result = PUM_Utils_Fields::get_field_default_values( [] );
		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}

	// ─── get_form_default_values() ─────────────────────────────────────

	/**
	 * Test form defaults flattens and extracts defaults.
	 */
	public function test_get_form_default_values_nested() {
		$fields = [
			'general' => [
				'name' => [ 'type' => 'text', 'std' => 'Default' ],
			],
		];

		$result = PUM_Utils_Fields::get_form_default_values( $fields );

		$this->assertArrayHasKey( 'name', $result );
		$this->assertEquals( 'Default', $result['name'] );
	}

	/**
	 * Test form defaults with empty input.
	 */
	public function test_get_form_default_values_empty() {
		$result = PUM_Utils_Fields::get_form_default_values( [] );
		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}

	// ─── sanitize_field() ──────────────────────────────────────────────

	/**
	 * Test sanitize_field with text type.
	 */
	public function test_sanitize_field_text_type() {
		$field  = [ 'type' => 'text' ];
		$result = PUM_Utils_Fields::sanitize_field( $field, '<b>bold</b>' );

		$this->assertEquals( 'bold', $result );
	}

	/**
	 * Test sanitize_field with checkbox type.
	 */
	public function test_sanitize_field_checkbox_type() {
		$field = [ 'type' => 'checkbox' ];

		$this->assertSame( 1, PUM_Utils_Fields::sanitize_field( $field, 1 ) );
		$this->assertSame( 0, PUM_Utils_Fields::sanitize_field( $field, 0 ) );
	}

	/**
	 * Test sanitize_field with no type defaults to text.
	 */
	public function test_sanitize_field_no_type() {
		$field  = [];
		$result = PUM_Utils_Fields::sanitize_field( $field, '<em>test</em>' );
		$this->assertEquals( 'test', $result );
	}

	/**
	 * Test sanitize_field with measure type.
	 */
	public function test_sanitize_field_measure() {
		$field  = [ 'type' => 'measure', 'id' => 'width' ];
		$values = [ 'width_unit' => 'px' ];
		$result = PUM_Utils_Fields::sanitize_field( $field, '100', [], $values );

		$this->assertEquals( '100px', $result );
	}

	/**
	 * Test sanitize_field applies general filter.
	 */
	public function test_sanitize_field_applies_general_filter() {
		$filter_called = false;
		$callback      = function ( $value ) use ( &$filter_called ) {
			$filter_called = true;
			return $value;
		};

		add_filter( 'pum_settings_sanitize', $callback );
		PUM_Utils_Fields::sanitize_field( [ 'type' => 'text' ], 'test' );
		remove_filter( 'pum_settings_sanitize', $callback );

		$this->assertTrue( $filter_called );
	}

	/**
	 * Test sanitize_field with custom type filter.
	 */
	public function test_sanitize_field_custom_type_filter() {
		$callback = function ( $value ) {
			return 'filtered_' . $value;
		};

		add_filter( 'pum_custom_sanitize', $callback );
		$result = PUM_Utils_Fields::sanitize_field( [ 'type' => 'custom' ], 'input' );
		remove_filter( 'pum_custom_sanitize', $callback );

		$this->assertEquals( 'filtered_input', $result );
	}

	// ─── sanitize_fields() ─────────────────────────────────────────────

	/**
	 * Test sanitize_fields with matching field definitions.
	 */
	public function test_sanitize_fields_with_definitions() {
		$values = [
			'name'  => '<b>John</b>',
			'check' => 1,
		];
		$fields = [
			'name'  => [ 'type' => 'text', 'label' => 'Name' ],
			'check' => [ 'type' => 'checkbox', 'label' => 'Agree' ],
		];

		$result = PUM_Utils_Fields::sanitize_fields( $values, $fields );

		$this->assertEquals( 'John', $result['name'] );
		$this->assertSame( 1, $result['check'] );
	}

	/**
	 * Test sanitize_fields with undefined field falls back to sanitize_text_field.
	 */
	public function test_sanitize_fields_undefined_field() {
		$values = [ 'unknown' => '<script>alert(1)</script>' ];
		$result = PUM_Utils_Fields::sanitize_fields( $values, [] );

		$this->assertEquals( '', $result['unknown'] );
	}

	/**
	 * Test sanitize_fields with non-string value and no field def.
	 */
	public function test_sanitize_fields_non_string_value() {
		$values = [ 'count' => 42 ];
		$result = PUM_Utils_Fields::sanitize_fields( $values, [] );

		$this->assertEquals( 42, $result['count'] );
	}

	/**
	 * Test sanitize_fields preserves all keys.
	 */
	public function test_sanitize_fields_preserves_keys() {
		$values = [
			'a' => 'alpha',
			'b' => 'beta',
			'c' => 'gamma',
		];

		$result = PUM_Utils_Fields::sanitize_fields( $values, [] );

		$this->assertArrayHasKey( 'a', $result );
		$this->assertArrayHasKey( 'b', $result );
		$this->assertArrayHasKey( 'c', $result );
	}

	/**
	 * Test sanitize_fields with nested field definitions.
	 */
	public function test_sanitize_fields_with_nested_field_defs() {
		$values = [ 'title' => '<h1>Hello</h1>' ];
		$fields = [
			'general' => [
				'title' => [ 'type' => 'text', 'label' => 'Title' ],
			],
		];

		$result = PUM_Utils_Fields::sanitize_fields( $values, $fields );

		$this->assertEquals( 'Hello', $result['title'] );
	}

	// ─── parse_fields() ────────────────────────────────────────────────

	/**
	 * Test parse_fields adds defaults and sorts.
	 */
	public function test_parse_fields_adds_defaults() {
		$fields = [
			'name' => [
				'type'  => 'text',
				'label' => 'Name',
			],
		];

		$result = PUM_Utils_Fields::parse_fields( $fields, '%s' );

		$this->assertArrayHasKey( 'name', $result );
		$this->assertEquals( 'text', $result['name']['type'] );
		$this->assertEquals( 'Name', $result['name']['label'] );
		// Defaults should be filled.
		$this->assertEquals( 'main', $result['name']['section'] );
		$this->assertEquals( 10, $result['name']['priority'] );
	}

	/**
	 * Test parse_fields sets field id from key.
	 */
	public function test_parse_fields_sets_id_from_key() {
		$fields = [
			'my_field' => [
				'type'  => 'text',
				'label' => 'My Field',
			],
		];

		$result = PUM_Utils_Fields::parse_fields( $fields, '%s' );

		$this->assertEquals( 'my_field', $result['my_field']['id'] );
	}

	/**
	 * Test parse_fields sets name from format.
	 */
	public function test_parse_fields_sets_name_format() {
		$fields = [
			'color' => [
				'type'  => 'text',
				'label' => 'Color',
			],
		];

		$result = PUM_Utils_Fields::parse_fields( $fields, 'settings[%s]' );

		$this->assertEquals( 'settings[color]', $result['color']['name'] );
	}

	/**
	 * Test parse_fields skips non-field entries.
	 */
	public function test_parse_fields_skips_non_fields() {
		$fields = [
			'a_field'   => [ 'type' => 'text', 'label' => 'Field' ],
			'a_section' => [
				'sub_field' => [ 'type' => 'text', 'label' => 'Sub' ],
			],
		];

		$result = PUM_Utils_Fields::parse_fields( $fields, '%s' );

		// The field should be parsed.
		$this->assertArrayHasKey( 'a_field', $result );
		// The section should be passed through as-is (not a field).
		$this->assertArrayHasKey( 'a_section', $result );
	}

	/**
	 * Test parse_fields with empty array.
	 */
	public function test_parse_fields_empty() {
		$result = PUM_Utils_Fields::parse_fields( [], '%s' );
		$this->assertIsArray( $result );
	}

	/**
	 * Test parse_fields sorts by priority.
	 */
	public function test_parse_fields_sorts_by_priority() {
		$fields = [
			'low'  => [ 'type' => 'text', 'label' => 'Low', 'priority' => 20 ],
			'high' => [ 'type' => 'text', 'label' => 'High', 'priority' => 5 ],
		];

		$result = PUM_Utils_Fields::parse_fields( $fields, '%s' );
		$keys   = array_keys( $result );

		$this->assertEquals( 'high', $keys[0] );
		$this->assertEquals( 'low', $keys[1] );
	}

	/**
	 * Test parse_fields remaps numeric key to field id.
	 */
	public function test_parse_fields_remaps_numeric_key() {
		$fields = [
			0 => [ 'type' => 'text', 'label' => 'Test', 'id' => 'my_field' ],
		];

		$result = PUM_Utils_Fields::parse_fields( $fields, '%s' );

		$this->assertArrayHasKey( 'my_field', $result );
	}

	// ─── parse_tab_fields() ────────────────────────────────────────────

	/**
	 * Test parse_tab_fields without sections.
	 */
	public function test_parse_tab_fields_no_sections() {
		$fields = [
			'general' => [
				'name' => [ 'type' => 'text', 'label' => 'Name' ],
			],
		];

		$result = PUM_Utils_Fields::parse_tab_fields( $fields );

		$this->assertArrayHasKey( 'general', $result );
		$this->assertArrayHasKey( 'name', $result['general'] );
		// Should have parse_field defaults applied.
		$this->assertEquals( 'main', $result['general']['name']['section'] );
	}

	/**
	 * Test parse_tab_fields with sections enabled.
	 */
	public function test_parse_tab_fields_with_sections() {
		$fields = [
			'display' => [
				'sizing' => [
					'width' => [ 'type' => 'number', 'label' => 'Width' ],
				],
			],
		];

		$result = PUM_Utils_Fields::parse_tab_fields( $fields, [ 'has_sections' => true ] );

		$this->assertArrayHasKey( 'display', $result );
		$this->assertArrayHasKey( 'sizing', $result['display'] );
		$this->assertArrayHasKey( 'width', $result['display']['sizing'] );
	}

	/**
	 * Test parse_tab_fields with custom name format.
	 */
	public function test_parse_tab_fields_custom_name() {
		$fields = [
			'general' => [
				'title' => [ 'type' => 'text', 'label' => 'Title' ],
			],
		];

		$result = PUM_Utils_Fields::parse_tab_fields( $fields, [ 'name' => 'popup[%s]' ] );

		$this->assertEquals( 'popup[title]', $result['general']['title']['name'] );
	}

	// ─── render_field() ────────────────────────────────────────────────

	/**
	 * Test render_field does not fatal on unknown type.
	 */
	public function test_render_field_no_fatal_on_unknown_type() {
		// Should not throw or fatal.
		ob_start();
		PUM_Utils_Fields::render_field( [ 'type' => 'nonexistent_type_xyz' ] );
		$output = ob_get_clean();

		// Just verify no fatal error occurred.
		$this->assertTrue( true );
	}

	/**
	 * Test render_field calls action hook for custom types.
	 */
	public function test_render_field_custom_action_hook() {
		$hook_called = false;
		$callback    = function ( $args ) use ( &$hook_called ) {
			$hook_called = true;
		};

		add_action( 'pum_my_custom_type_field', $callback );
		ob_start();
		PUM_Utils_Fields::render_field( [ 'type' => 'my_custom_type' ] );
		ob_get_clean();
		remove_action( 'pum_my_custom_type_field', $callback );

		$this->assertTrue( $hook_called );
	}
}
