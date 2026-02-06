<?php
/**
 * Tests for PUM_Triggers registry class.
 *
 * @package Popup_Maker
 */

/**
 * Test PUM_Triggers registry.
 */
class PUM_Triggers_Test extends WP_UnitTestCase {

	/**
	 * Instance under test.
	 *
	 * @var PUM_Triggers
	 */
	private $triggers;

	/**
	 * Set up each test with a fresh instance.
	 */
	public function setUp(): void {
		parent::setUp();
		// Reset singleton so each test is independent.
		PUM_Triggers::$instance = null;
		$this->triggers         = new PUM_Triggers();
	}

	/**
	 * Clean up after each test.
	 */
	public function tearDown(): void {
		PUM_Triggers::$instance = null;
		parent::tearDown();
	}

	// ─── Singleton ─────────────────────────────────────────────────────

	/**
	 * Test instance returns the same object.
	 */
	public function test_instance_returns_singleton() {
		$a = PUM_Triggers::instance();
		$b = PUM_Triggers::instance();
		$this->assertSame( $a, $b, 'instance() should return the same object.' );
	}

	/**
	 * Test init calls instance without error.
	 */
	public function test_init_creates_instance() {
		PUM_Triggers::init();
		$this->assertInstanceOf( PUM_Triggers::class, PUM_Triggers::$instance );
	}

	// ─── add_trigger / get_trigger ─────────────────────────────────────

	/**
	 * Test adding and retrieving a single trigger.
	 */
	public function test_add_trigger_and_get_trigger() {
		$this->triggers->triggers = [];

		$this->triggers->add_trigger( [
			'id'     => 'test_trigger',
			'name'   => 'Test Trigger',
			'fields' => [
				'general' => [],
			],
		] );

		$result = $this->triggers->get_trigger( 'test_trigger' );
		$this->assertNotNull( $result, 'Trigger should be retrievable.' );
		$this->assertEquals( 'Test Trigger', $result['name'] );
	}

	/**
	 * Test default values are merged for a trigger.
	 */
	public function test_add_trigger_merges_defaults() {
		$this->triggers->triggers = [];

		$this->triggers->add_trigger( [
			'id'     => 'minimal_trigger',
			'name'   => 'Minimal',
			'fields' => [
				'general' => [],
			],
		] );

		$result = $this->triggers->get_trigger( 'minimal_trigger' );
		$this->assertNotNull( $result );
		$this->assertEquals( 10, $result['priority'], 'Default priority should be 10.' );
		$this->assertArrayHasKey( 'tabs', $result, 'Should have tabs.' );
	}

	/**
	 * Test modal_title is auto-generated from name when empty.
	 */
	public function test_add_trigger_auto_generates_modal_title() {
		$this->triggers->triggers = [];

		$this->triggers->add_trigger( [
			'id'     => 'auto_title_trigger',
			'name'   => 'Scroll Down',
			'fields' => [
				'general' => [],
			],
		] );

		$result = $this->triggers->get_trigger( 'auto_title_trigger' );
		$this->assertStringContainsString( 'Scroll Down', $result['modal_title'] );
	}

	/**
	 * Test adding a trigger without id is ignored.
	 */
	public function test_add_trigger_without_id_is_ignored() {
		$this->triggers->triggers = [];

		$this->triggers->add_trigger( [
			'name' => 'No ID Trigger',
		] );

		$this->assertEmpty( $this->triggers->triggers, 'Trigger without id should not be added.' );
	}

	/**
	 * Test duplicate trigger ids are not overwritten.
	 */
	public function test_add_trigger_does_not_overwrite_existing() {
		$this->triggers->triggers = [];

		$this->triggers->add_trigger( [
			'id'     => 'dup_trigger',
			'name'   => 'First',
			'fields' => [ 'general' => [] ],
		] );

		$this->triggers->add_trigger( [
			'id'     => 'dup_trigger',
			'name'   => 'Second',
			'fields' => [ 'general' => [] ],
		] );

		$result = $this->triggers->get_trigger( 'dup_trigger' );
		$this->assertEquals( 'First', $result['name'], 'First registered trigger should win.' );
	}

	/**
	 * Test get_trigger returns null for unknown trigger.
	 */
	public function test_get_trigger_returns_null_for_unknown() {
		$this->triggers->triggers = [];
		$this->assertNull( $this->triggers->get_trigger( 'nonexistent' ) );
	}

	/**
	 * Test cookie fields section is removed but cookie_name is added.
	 */
	public function test_add_trigger_removes_cookie_tab_adds_cookie_name() {
		$this->triggers->triggers = [];

		$this->triggers->add_trigger( [
			'id'     => 'cookie_test',
			'name'   => 'Cookie Test',
			'fields' => [
				'general' => [],
				'cookie'  => [
					'some_field' => [ 'type' => 'text' ],
				],
			],
		] );

		$result = $this->triggers->get_trigger( 'cookie_test' );
		// The cookie tab should be removed.
		$this->assertArrayNotHasKey( 'cookie', $result['fields'], 'Cookie tab should be removed.' );
		// cookie_name should be auto-added to general.
		$this->assertArrayHasKey( 'cookie_name', $result['fields']['general'], 'cookie_name should be added.' );
	}

	// ─── add_triggers (batch) ──────────────────────────────────────────

	/**
	 * Test adding multiple triggers at once.
	 */
	public function test_add_triggers_batch() {
		$this->triggers->triggers = [];

		$this->triggers->add_triggers( [
			'trigger_a' => [
				'name'   => 'Trigger A',
				'fields' => [ 'general' => [] ],
			],
			'trigger_b' => [
				'name'   => 'Trigger B',
				'fields' => [ 'general' => [] ],
			],
		] );

		$this->assertNotNull( $this->triggers->get_trigger( 'trigger_a' ) );
		$this->assertNotNull( $this->triggers->get_trigger( 'trigger_b' ) );
	}

	/**
	 * Test add_triggers assigns key as id when id is missing.
	 */
	public function test_add_triggers_uses_key_as_id() {
		$this->triggers->triggers = [];

		$this->triggers->add_triggers( [
			'my_key' => [
				'name'   => 'Keyed Trigger',
				'fields' => [ 'general' => [] ],
			],
		] );

		$result = $this->triggers->get_trigger( 'my_key' );
		$this->assertNotNull( $result );
		$this->assertEquals( 'my_key', $result['id'] );
	}

	/**
	 * Test add_triggers with numeric key does not set id.
	 */
	public function test_add_triggers_numeric_key_does_not_become_id() {
		$this->triggers->triggers = [];

		$this->triggers->add_triggers( [
			0 => [
				'name'   => 'No ID',
				'fields' => [ 'general' => [] ],
			],
		] );

		$this->assertEmpty( $this->triggers->triggers );
	}

	// ─── get_triggers (lazy loading) ───────────────────────────────────

	/**
	 * Test get_triggers triggers registration when not yet set.
	 */
	public function test_get_triggers_auto_registers() {
		$triggers = $this->triggers->get_triggers();
		$this->assertIsArray( $triggers );
		$this->assertNotEmpty( $triggers, 'Should auto-register built-in triggers.' );
	}

	/**
	 * Test default registered triggers include expected types.
	 */
	public function test_default_triggers_include_expected() {
		$triggers = $this->triggers->get_triggers();

		$this->assertArrayHasKey( 'click_open', $triggers, 'Should include Click Open.' );
		$this->assertArrayHasKey( 'auto_open', $triggers, 'Should include Time Delay / Auto Open.' );
		$this->assertArrayHasKey( 'form_submission', $triggers, 'Should include Form Submission.' );
	}

	/**
	 * Test each default trigger has a name field.
	 */
	public function test_default_triggers_all_have_names() {
		$triggers = $this->triggers->get_triggers();

		foreach ( $triggers as $id => $trigger ) {
			$this->assertNotEmpty( $trigger['name'], "Trigger '{$id}' should have a name." );
		}
	}

	/**
	 * Test click_open trigger has expected field structure.
	 */
	public function test_click_open_trigger_fields() {
		$triggers = $this->triggers->get_triggers();
		$click    = $triggers['click_open'];

		$this->assertArrayHasKey( 'fields', $click );
		$this->assertArrayHasKey( 'general', $click['fields'] );
		// Should have extra_selectors and cookie_name fields in general.
		$this->assertArrayHasKey( 'extra_selectors', $click['fields']['general'] );
	}

	/**
	 * Test auto_open trigger has delay field.
	 */
	public function test_auto_open_trigger_has_delay() {
		$triggers = $this->triggers->get_triggers();
		$auto     = $triggers['auto_open'];

		$this->assertArrayHasKey( 'fields', $auto );
		$this->assertArrayHasKey( 'general', $auto['fields'] );
		$this->assertArrayHasKey( 'delay', $auto['fields']['general'] );
	}

	// ─── dropdown_list ─────────────────────────────────────────────────

	/**
	 * Test dropdown_list returns id => name pairs.
	 */
	public function test_dropdown_list_structure() {
		$list = $this->triggers->dropdown_list();
		$this->assertIsArray( $list );
		$this->assertNotEmpty( $list );

		foreach ( $list as $id => $name ) {
			$this->assertIsString( $name, "Trigger '$id' name should be a string." );
		}
	}

	/**
	 * Test dropdown_list includes all default triggers.
	 */
	public function test_dropdown_list_includes_defaults() {
		$list = $this->triggers->dropdown_list();
		$this->assertArrayHasKey( 'click_open', $list );
		$this->assertArrayHasKey( 'auto_open', $list );
		$this->assertArrayHasKey( 'form_submission', $list );
	}

	// ─── cookie_fields / cookie_field ──────────────────────────────────

	/**
	 * Test cookie_fields returns array with cookie_name key.
	 */
	public function test_cookie_fields_structure() {
		$fields = $this->triggers->cookie_fields();
		$this->assertIsArray( $fields );
		$this->assertArrayHasKey( 'cookie_name', $fields );
	}

	/**
	 * Test cookie_field returns expected field definition.
	 */
	public function test_cookie_field_definition() {
		$field = $this->triggers->cookie_field();
		$this->assertIsArray( $field );
		$this->assertEquals( 'select', $field['type'] );
		$this->assertTrue( $field['multiple'] );
		$this->assertTrue( $field['as_array'] );
		$this->assertTrue( $field['select2'] );
		$this->assertEquals( 99, $field['priority'] );
		$this->assertArrayHasKey( 'add_new', $field['options'] );
	}

	// ─── get_tabs ──────────────────────────────────────────────────────

	/**
	 * Test get_tabs returns expected tabs.
	 */
	public function test_get_tabs() {
		$tabs = $this->triggers->get_tabs();
		$this->assertIsArray( $tabs );
		$this->assertArrayHasKey( 'general', $tabs );
		$this->assertArrayHasKey( 'cookie', $tabs );
		$this->assertArrayHasKey( 'advanced', $tabs );
	}

	// ─── get_labels ────────────────────────────────────────────────────

	/**
	 * Test get_labels returns array.
	 */
	public function test_get_labels_returns_array() {
		$labels = $this->triggers->get_labels();
		$this->assertIsArray( $labels );
	}

	// ─── validate_trigger (deprecated) ─────────────────────────────────

	/**
	 * Test deprecated validate_trigger returns settings unchanged.
	 */
	public function test_validate_trigger_returns_settings() {
		$settings = [ 'delay' => 500, 'extra_selectors' => '.btn' ];
		$result   = $this->triggers->validate_trigger( 'click_open', $settings );
		$this->assertSame( $settings, $result, 'Deprecated method should return settings as-is.' );
	}

	// ─── Filter integration ────────────────────────────────────────────

	/**
	 * Test pum_registered_triggers filter can add triggers.
	 */
	public function test_pum_registered_triggers_filter() {
		add_filter( 'pum_registered_triggers', function ( $triggers ) {
			$triggers['custom_trigger'] = [
				'name'   => 'Custom Trigger',
				'fields' => [
					'general' => [
						'custom_field' => [
							'type'  => 'text',
							'label' => 'Custom Field',
						],
					],
				],
			];
			return $triggers;
		} );

		$triggers = $this->triggers->get_triggers();
		$this->assertArrayHasKey( 'custom_trigger', $triggers );
		$this->assertEquals( 'Custom Trigger', $triggers['custom_trigger']['name'] );

		// Clean up.
		remove_all_filters( 'pum_registered_triggers' );
	}

	/**
	 * Test deprecated pum_get_triggers filter still works.
	 */
	public function test_deprecated_pum_get_triggers_filter() {
		add_filter( 'pum_get_triggers', function ( $triggers ) {
			$triggers['legacy_trigger'] = [
				'name'   => 'Legacy Trigger',
				'fields' => [
					'general' => [],
				],
			];
			return $triggers;
		} );

		$triggers = $this->triggers->get_triggers();
		$this->assertArrayHasKey( 'legacy_trigger', $triggers );

		// Clean up.
		remove_all_filters( 'pum_get_triggers' );
	}

	/**
	 * Test deprecated filter does not overwrite existing triggers.
	 */
	public function test_deprecated_filter_does_not_overwrite_existing() {
		add_filter( 'pum_get_triggers', function ( $triggers ) {
			$triggers['click_open'] = [
				'name' => 'Overwritten Click',
			];
			return $triggers;
		} );

		$triggers = $this->triggers->get_triggers();
		$this->assertNotEquals( 'Overwritten Click', $triggers['click_open']['name'] );

		// Clean up.
		remove_all_filters( 'pum_get_triggers' );
	}

	/**
	 * Test pum_trigger_cookie_fields filter can modify cookie fields.
	 */
	public function test_pum_trigger_cookie_fields_filter() {
		add_filter( 'pum_trigger_cookie_fields', function ( $fields ) {
			$fields['extra_cookie'] = [
				'type'  => 'text',
				'label' => 'Extra',
			];
			return $fields;
		} );

		$fields = $this->triggers->cookie_fields();
		$this->assertArrayHasKey( 'extra_cookie', $fields );

		// Clean up.
		remove_all_filters( 'pum_trigger_cookie_fields' );
	}

	/**
	 * Test pum_trigger_cookie_field filter can modify cookie field.
	 */
	public function test_pum_trigger_cookie_field_filter() {
		add_filter( 'pum_trigger_cookie_field', function ( $field ) {
			$field['label'] = 'Modified Label';
			return $field;
		} );

		$field = $this->triggers->cookie_field();
		$this->assertEquals( 'Modified Label', $field['label'] );

		// Clean up.
		remove_all_filters( 'pum_trigger_cookie_field' );
	}

	/**
	 * Test pum_get_trigger_tabs filter can modify tabs.
	 */
	public function test_pum_get_trigger_tabs_filter() {
		add_filter( 'pum_get_trigger_tabs', function ( $tabs ) {
			$tabs['custom_tab'] = 'Custom Tab';
			return $tabs;
		} );

		$tabs = $this->triggers->get_tabs();
		$this->assertArrayHasKey( 'custom_tab', $tabs );

		// Clean up.
		remove_all_filters( 'pum_get_trigger_tabs' );
	}
}
