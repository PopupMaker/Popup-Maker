<?php
/**
 * Tests for PUM_Conditions registry class.
 *
 * @package Popup_Maker
 */

/**
 * Test PUM_Conditions registry.
 */
class PUM_Conditions_Test extends WP_UnitTestCase {

	/**
	 * Instance under test.
	 *
	 * @var PUM_Conditions
	 */
	private $conditions;

	/**
	 * Set up each test with a fresh instance.
	 */
	public function setUp(): void {
		parent::setUp();
		// Reset singleton so each test is independent.
		PUM_Conditions::$instance = null;
		$this->conditions         = new PUM_Conditions();
	}

	/**
	 * Clean up after each test.
	 */
	public function tearDown(): void {
		PUM_Conditions::$instance = null;
		parent::tearDown();
	}

	// ─── Singleton ─────────────────────────────────────────────────────

	/**
	 * Test instance returns the same object.
	 */
	public function test_instance_returns_singleton() {
		$a = PUM_Conditions::instance();
		$b = PUM_Conditions::instance();
		$this->assertSame( $a, $b, 'instance() should return the same object.' );
	}

	/**
	 * Test init calls instance.
	 */
	public function test_init_creates_instance() {
		PUM_Conditions::init();
		$this->assertInstanceOf( PUM_Conditions::class, PUM_Conditions::$instance );
	}

	// ─── add_condition / get_condition ──────────────────────────────────

	/**
	 * Test adding and retrieving a single condition.
	 */
	public function test_add_condition_and_get_condition() {
		$this->conditions->conditions = [];

		$this->conditions->add_condition( [
			'id'       => 'test_cond',
			'name'     => 'Test Condition',
			'group'    => 'General',
			'callback' => 'is_home',
		] );

		$result = $this->conditions->get_condition( 'test_cond' );
		$this->assertNotNull( $result, 'Condition should be retrievable.' );
		$this->assertEquals( 'Test Condition', $result['name'] );
		$this->assertEquals( 'General', $result['group'] );
		$this->assertEquals( 'is_home', $result['callback'] );
	}

	/**
	 * Test default values are merged for a condition.
	 */
	public function test_add_condition_merges_defaults() {
		$this->conditions->conditions = [];

		$this->conditions->add_condition( [
			'id'   => 'minimal_cond',
			'name' => 'Minimal',
		] );

		$result = $this->conditions->get_condition( 'minimal_cond' );
		$this->assertNotNull( $result );
		$this->assertEquals( 10, $result['priority'], 'Default priority should be 10.' );
		$this->assertNull( $result['callback'], 'Default callback should be null.' );
		$this->assertEmpty( $result['fields'], 'Default fields should be empty.' );
		$this->assertFalse( $result['advanced'], 'Default advanced should be false.' );
		$this->assertEquals( '', $result['group'], 'Default group should be empty.' );
	}

	/**
	 * Test adding a condition without an id is ignored.
	 */
	public function test_add_condition_without_id_is_ignored() {
		$this->conditions->conditions = [];

		$this->conditions->add_condition( [
			'name' => 'No ID Condition',
		] );

		$this->assertEmpty( $this->conditions->conditions, 'Condition without id should not be added.' );
	}

	/**
	 * Test duplicate condition ids are not overwritten.
	 */
	public function test_add_condition_does_not_overwrite_existing() {
		$this->conditions->conditions = [];

		$this->conditions->add_condition( [
			'id'   => 'dup_cond',
			'name' => 'First',
		] );

		$this->conditions->add_condition( [
			'id'   => 'dup_cond',
			'name' => 'Second',
		] );

		$result = $this->conditions->get_condition( 'dup_cond' );
		$this->assertEquals( 'First', $result['name'], 'First registered condition should win.' );
	}

	/**
	 * Test get_condition returns null for unknown condition.
	 */
	public function test_get_condition_returns_null_for_unknown() {
		$this->conditions->conditions = [];
		$this->assertNull( $this->conditions->get_condition( 'nonexistent' ) );
	}

	// ─── add_conditions (batch) ────────────────────────────────────────

	/**
	 * Test adding multiple conditions at once with string keys.
	 */
	public function test_add_conditions_batch_with_string_keys() {
		$this->conditions->conditions = [];

		$this->conditions->add_conditions( [
			'cond_a' => [
				'name'  => 'Condition A',
				'group' => 'General',
			],
			'cond_b' => [
				'name'  => 'Condition B',
				'group' => 'Pages',
			],
		] );

		$this->assertNotNull( $this->conditions->get_condition( 'cond_a' ) );
		$this->assertNotNull( $this->conditions->get_condition( 'cond_b' ) );
	}

	/**
	 * Test add_conditions assigns key as id when id is missing.
	 */
	public function test_add_conditions_uses_key_as_id() {
		$this->conditions->conditions = [];

		$this->conditions->add_conditions( [
			'my_key' => [
				'name' => 'Keyed Condition',
			],
		] );

		$result = $this->conditions->get_condition( 'my_key' );
		$this->assertNotNull( $result );
		$this->assertEquals( 'my_key', $result['id'] );
	}

	/**
	 * Test add_conditions does not assign key as id when key is numeric.
	 */
	public function test_add_conditions_numeric_key_does_not_become_id() {
		$this->conditions->conditions = [];

		$this->conditions->add_conditions( [
			0 => [
				'name' => 'No ID',
			],
		] );

		// Numeric key should not be used as id, so the condition should be skipped.
		$this->assertEmpty( $this->conditions->conditions );
	}

	// ─── get_conditions (lazy loading) ─────────────────────────────────

	/**
	 * Test get_conditions triggers registration when not yet set.
	 */
	public function test_get_conditions_auto_registers() {
		// conditions property is not set initially on a fresh instance.
		$conditions = $this->conditions->get_conditions();
		$this->assertIsArray( $conditions );
		$this->assertNotEmpty( $conditions, 'Should auto-register built-in conditions.' );
	}

	/**
	 * Test built-in conditions include general entries.
	 */
	public function test_builtin_conditions_include_general() {
		$conditions = $this->conditions->get_conditions();
		$this->assertArrayHasKey( 'is_front_page', $conditions, 'Should include Home Page condition.' );
		$this->assertArrayHasKey( 'is_home', $conditions, 'Should include Blog Index condition.' );
		$this->assertArrayHasKey( 'is_search', $conditions, 'Should include Search Result Page condition.' );
		$this->assertArrayHasKey( 'is_404', $conditions, 'Should include 404 Error Page condition.' );
	}

	/**
	 * Test built-in conditions include post type conditions.
	 */
	public function test_builtin_conditions_include_post_types() {
		$conditions = $this->conditions->get_conditions();
		// Post and page are built-in public types.
		$this->assertArrayHasKey( 'post_all', $conditions, 'Should include All Posts condition.' );
		$this->assertArrayHasKey( 'page_all', $conditions, 'Should include All Pages condition.' );
		$this->assertArrayHasKey( 'post_selected', $conditions, 'Should include Posts: Selected condition.' );
		$this->assertArrayHasKey( 'page_selected', $conditions, 'Should include Pages: Selected condition.' );
	}

	/**
	 * Test built-in conditions include taxonomy conditions.
	 */
	public function test_builtin_conditions_include_taxonomies() {
		$conditions = $this->conditions->get_conditions();
		$this->assertArrayHasKey( 'tax_category_all', $conditions, 'Should include Categories: All condition.' );
		$this->assertArrayHasKey( 'tax_post_tag_all', $conditions, 'Should include Tags: All condition.' );
	}

	// ─── Condition sort order ──────────────────────────────────────────

	/**
	 * Test condition_sort_order returns array with expected keys.
	 */
	public function test_condition_sort_order_returns_array() {
		$order = $this->conditions->condition_sort_order();
		$this->assertIsArray( $order );
		$this->assertArrayHasKey( 'General', $order );
		$this->assertArrayHasKey( 'Pages', $order );
		$this->assertArrayHasKey( 'Posts', $order );
	}

	/**
	 * Test General group has the lowest sort priority.
	 */
	public function test_condition_sort_order_general_is_first() {
		$order = $this->conditions->condition_sort_order();
		$this->assertEquals( 1, $order['General'] );
	}

	/**
	 * Test condition_sort_order is cached after first call.
	 */
	public function test_condition_sort_order_is_cached() {
		$first  = $this->conditions->condition_sort_order();
		$second = $this->conditions->condition_sort_order();
		$this->assertSame( $first, $second, 'Sort order should be cached.' );
	}

	// ─── sort_condition_groups ─────────────────────────────────────────

	/**
	 * Test sort_condition_groups returns -1 when a < b.
	 */
	public function test_sort_condition_groups_lower_first() {
		$result = $this->conditions->sort_condition_groups( 'General', 'Pages' );
		$this->assertEquals( -1, $result, 'General (1) should sort before Pages (5).' );
	}

	/**
	 * Test sort_condition_groups returns 1 when a > b.
	 */
	public function test_sort_condition_groups_higher_last() {
		$result = $this->conditions->sort_condition_groups( 'Pages', 'General' );
		$this->assertEquals( 1, $result, 'Pages (5) should sort after General (1).' );
	}

	/**
	 * Test sort_condition_groups returns -1 or 1 even when equal priority.
	 *
	 * The sort function uses strcmp fallback for equal priorities, so it doesn't return 0.
	 */
	public function test_sort_condition_groups_equal() {
		$result = $this->conditions->sort_condition_groups( 'Pages', 'Posts' );
		// Pages and Posts have the same priority (5), but sort uses string comparison as fallback.
		// 'Pages' < 'Posts' alphabetically, so expect -1.
		$this->assertEquals( -1, $result, 'Equal priority items use alphabetical comparison.' );
	}

	/**
	 * Test sort_condition_groups defaults to 10 for unknown groups.
	 */
	public function test_sort_condition_groups_unknown_defaults_to_10() {
		// Unknown group defaults to 10, same as custom post types.
		$result = $this->conditions->sort_condition_groups( 'General', 'SomeUnknownGroup' );
		// General = 1, Unknown = 10, so General < Unknown.
		$this->assertEquals( -1, $result );
	}

	// ─── get_conditions_by_group ────────────────────────────────────────

	/**
	 * Test get_conditions_by_group returns grouped array.
	 */
	public function test_get_conditions_by_group_returns_groups() {
		$groups = $this->conditions->get_conditions_by_group();
		$this->assertIsArray( $groups );
		$this->assertNotEmpty( $groups );

		// Each group key should be a string, each value an array of conditions.
		foreach ( $groups as $group_name => $conditions ) {
			$this->assertIsString( $group_name );
			$this->assertIsArray( $conditions );
		}
	}

	/**
	 * Test get_conditions_by_group includes General group.
	 */
	public function test_get_conditions_by_group_has_general() {
		$groups = $this->conditions->get_conditions_by_group();
		$this->assertArrayHasKey( 'General', $groups );
		$this->assertArrayHasKey( 'is_front_page', $groups['General'] );
	}

	/**
	 * Test groups are sorted by sort order.
	 */
	public function test_get_conditions_by_group_is_sorted() {
		$groups     = $this->conditions->get_conditions_by_group();
		$group_keys = array_keys( $groups );

		// General should come before Pages/Posts.
		$general_pos = array_search( 'General', $group_keys, true );
		$this->assertNotFalse( $general_pos, 'General group should exist.' );

		// General should be near the front.
		$this->assertLessThanOrEqual( 1, $general_pos, 'General group should be first or second.' );
	}

	// ─── dropdown_list ─────────────────────────────────────────────────

	/**
	 * Test dropdown_list returns grouped id => name pairs.
	 */
	public function test_dropdown_list_structure() {
		$list = $this->conditions->dropdown_list();
		$this->assertIsArray( $list );
		$this->assertNotEmpty( $list );

		// Each top-level key is a group, each value is array of id => name.
		foreach ( $list as $group => $conditions ) {
			$this->assertIsString( $group );
			$this->assertIsArray( $conditions );
			foreach ( $conditions as $id => $name ) {
				$this->assertIsString( $name, 'Condition name should be a string.' );
			}
		}
	}

	/**
	 * Test dropdown_list includes front page condition under General.
	 */
	public function test_dropdown_list_includes_front_page() {
		$list = $this->conditions->dropdown_list();
		$this->assertArrayHasKey( 'General', $list );
		$this->assertArrayHasKey( 'is_front_page', $list['General'] );
	}

	// ─── generate_post_type_conditions ──────────────────────────────────

	/**
	 * Test post type conditions are generated for built-in post types.
	 */
	public function test_generate_post_type_conditions_includes_post() {
		$conditions = $this->conditions->generate_post_type_conditions();
		$this->assertArrayHasKey( 'post_all', $conditions );
		$this->assertArrayHasKey( 'post_selected', $conditions );
		$this->assertArrayHasKey( 'post_ID', $conditions );
	}

	/**
	 * Test post type conditions are generated for pages (hierarchical).
	 */
	public function test_generate_post_type_conditions_includes_page() {
		$conditions = $this->conditions->generate_post_type_conditions();
		$this->assertArrayHasKey( 'page_all', $conditions );
		$this->assertArrayHasKey( 'page_selected', $conditions );
		$this->assertArrayHasKey( 'page_ID', $conditions );
		// Pages are hierarchical, so should have children and ancestors.
		$this->assertArrayHasKey( 'page_children', $conditions );
		$this->assertArrayHasKey( 'page_ancestors', $conditions );
	}

	/**
	 * Test popup post type is excluded.
	 */
	public function test_generate_post_type_conditions_excludes_popup() {
		$conditions = $this->conditions->generate_post_type_conditions();
		$this->assertArrayNotHasKey( 'popup_all', $conditions );
		$this->assertArrayNotHasKey( 'popup_theme_all', $conditions );
	}

	/**
	 * Test condition structure contains required keys.
	 */
	public function test_generate_post_type_condition_structure() {
		$conditions = $this->conditions->generate_post_type_conditions();
		$cond       = $conditions['post_all'];

		$this->assertArrayHasKey( 'group', $cond );
		$this->assertArrayHasKey( 'name', $cond );
		$this->assertArrayHasKey( 'callback', $cond );
		$this->assertEquals( [ 'PUM_ConditionCallbacks', 'post_type' ], $cond['callback'] );
	}

	/**
	 * Test post_selected condition has postselect field.
	 */
	public function test_generate_post_type_conditions_selected_has_fields() {
		$conditions = $this->conditions->generate_post_type_conditions();
		$cond       = $conditions['post_selected'];

		$this->assertArrayHasKey( 'fields', $cond );
		$this->assertArrayHasKey( 'selected', $cond['fields'] );
		$this->assertEquals( 'postselect', $cond['fields']['selected']['type'] );
		$this->assertTrue( $cond['fields']['selected']['multiple'] );
	}

	/**
	 * Test post_ID condition has text field.
	 */
	public function test_generate_post_type_conditions_id_has_text_field() {
		$conditions = $this->conditions->generate_post_type_conditions();
		$cond       = $conditions['post_ID'];

		$this->assertArrayHasKey( 'fields', $cond );
		$this->assertArrayHasKey( 'selected', $cond['fields'] );
		$this->assertEquals( 'text', $cond['fields']['selected']['type'] );
	}

	// ─── generate_post_type_tax_conditions ──────────────────────────────

	/**
	 * Test taxonomy conditions are generated for posts with categories.
	 */
	public function test_generate_post_type_tax_conditions_for_post() {
		$conditions = $this->conditions->generate_post_type_tax_conditions( 'post' );
		$this->assertIsArray( $conditions );
		// Posts should have category and tag taxonomy conditions.
		$this->assertArrayHasKey( 'post_w_category', $conditions );
		$this->assertArrayHasKey( 'post_w_post_tag', $conditions );
	}

	/**
	 * Test taxonomy condition structure.
	 */
	public function test_generate_post_type_tax_condition_structure() {
		$conditions = $this->conditions->generate_post_type_tax_conditions( 'post' );
		$cond       = $conditions['post_w_category'];

		$this->assertArrayHasKey( 'group', $cond );
		$this->assertArrayHasKey( 'name', $cond );
		$this->assertArrayHasKey( 'fields', $cond );
		$this->assertArrayHasKey( 'callback', $cond );
		$this->assertEquals( [ 'PUM_ConditionCallbacks', 'post_type_tax' ], $cond['callback'] );
		$this->assertEquals( 'taxonomyselect', $cond['fields']['selected']['type'] );
	}

	// ─── generate_taxonomy_conditions ───────────────────────────────────

	/**
	 * Test taxonomy conditions are generated for public taxonomies.
	 */
	public function test_generate_taxonomy_conditions() {
		$conditions = $this->conditions->generate_taxonomy_conditions();
		$this->assertIsArray( $conditions );
		// Category and post_tag are built-in public taxonomies.
		$this->assertArrayHasKey( 'tax_category_all', $conditions );
		$this->assertArrayHasKey( 'tax_category_selected', $conditions );
		$this->assertArrayHasKey( 'tax_category_ID', $conditions );
		$this->assertArrayHasKey( 'tax_post_tag_all', $conditions );
		$this->assertArrayHasKey( 'tax_post_tag_selected', $conditions );
		$this->assertArrayHasKey( 'tax_post_tag_ID', $conditions );
	}

	/**
	 * Test taxonomy condition uses taxonomy callback.
	 */
	public function test_generate_taxonomy_condition_callback() {
		$conditions = $this->conditions->generate_taxonomy_conditions();
		$this->assertEquals(
			[ 'PUM_ConditionCallbacks', 'taxonomy' ],
			$conditions['tax_category_all']['callback']
		);
	}

	/**
	 * Test taxonomy selected condition has taxonomyselect field.
	 */
	public function test_generate_taxonomy_condition_selected_field() {
		$conditions = $this->conditions->generate_taxonomy_conditions();
		$cond       = $conditions['tax_category_selected'];

		$this->assertArrayHasKey( 'fields', $cond );
		$this->assertArrayHasKey( 'selected', $cond['fields'] );
		$this->assertEquals( 'taxonomyselect', $cond['fields']['selected']['type'] );
		$this->assertEquals( 'category', $cond['fields']['selected']['taxonomy'] );
	}

	/**
	 * Test taxonomy ID condition has text field.
	 */
	public function test_generate_taxonomy_condition_id_field() {
		$conditions = $this->conditions->generate_taxonomy_conditions();
		$cond       = $conditions['tax_category_ID'];

		$this->assertEquals( 'text', $cond['fields']['selected']['type'] );
	}

	// ─── allowed_user_roles ────────────────────────────────────────────

	/**
	 * Test allowed_user_roles returns array of roles.
	 */
	public function test_allowed_user_roles_returns_roles() {
		$roles = PUM_Conditions::allowed_user_roles();
		$this->assertIsArray( $roles );
		// In a WP test environment, roles should include administrator.
		if ( ! empty( $roles ) ) {
			$this->assertArrayHasKey( 'administrator', $roles );
		}
	}

	// ─── Filter integration ────────────────────────────────────────────

	/**
	 * Test pum_registered_conditions filter can add conditions.
	 */
	public function test_pum_registered_conditions_filter() {
		add_filter( 'pum_registered_conditions', function ( $conditions ) {
			$conditions['custom_test'] = [
				'group'    => 'Custom',
				'name'     => 'Custom Test Condition',
				'callback' => '__return_true',
			];
			return $conditions;
		} );

		$conditions = $this->conditions->get_conditions();
		$this->assertArrayHasKey( 'custom_test', $conditions );
		$this->assertEquals( 'Custom Test Condition', $conditions['custom_test']['name'] );

		// Clean up.
		remove_all_filters( 'pum_registered_conditions' );
	}

	/**
	 * Test deprecated pum_get_conditions filter still works.
	 */
	public function test_deprecated_pum_get_conditions_filter() {
		add_filter( 'pum_get_conditions', function ( $conditions ) {
			$conditions['legacy_cond'] = [
				'labels' => [
					'name' => 'Legacy Condition',
				],
				'group'  => 'Legacy',
			];
			return $conditions;
		} );

		$conditions = $this->conditions->get_conditions();
		$this->assertArrayHasKey( 'legacy_cond', $conditions );
		// The labels->name should be promoted to name.
		$this->assertEquals( 'Legacy Condition', $conditions['legacy_cond']['name'] );

		// Clean up.
		remove_all_filters( 'pum_get_conditions' );
	}

	/**
	 * Test deprecated filter does not overwrite existing conditions.
	 */
	public function test_deprecated_filter_does_not_overwrite_existing() {
		add_filter( 'pum_get_conditions', function ( $conditions ) {
			// Try to overwrite is_front_page from the new filter.
			$conditions['is_front_page'] = [
				'name'  => 'Overwritten Front Page',
				'group' => 'Override',
			];
			return $conditions;
		} );

		$conditions = $this->conditions->get_conditions();
		// Should keep the original from pum_registered_conditions.
		$this->assertNotEquals( 'Overwritten Front Page', $conditions['is_front_page']['name'] );

		// Clean up.
		remove_all_filters( 'pum_get_conditions' );
	}
}
